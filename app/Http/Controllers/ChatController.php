<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\ChatMessage;
use App\Services\OpenAIService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ChatController extends Controller
{
    protected OpenAIService $openAIService;

    public function __construct(OpenAIService $openAIService)
    {
        $this->openAIService = $openAIService;
    }

    /**
     * Start a new chat session
     */
    public function startChat(Request $request): JsonResponse
    {
        $sessionId = $request->session_id ?? Str::uuid();

        // Check if chat already exists for this session
        $chat = Chat::where('session_id', $sessionId)->first();

        if (!$chat) {
            $chat = Chat::create([
                'session_id' => $sessionId,
                'user_id' => Auth::id(),
                'title' => 'Chat dengan Asisten Bapenda Samsat',
                'status' => 'active',
                'last_activity_at' => now(),
                'metadata' => [
                    'user_agent' => $request->userAgent(),
                    'ip_address' => $request->ip(),
                ]
            ]);

            // Add greeting message
            $greetingMessage = $this->openAIService->generateGreeting();

            ChatMessage::create([
                'chat_id' => $chat->id,
                'role' => 'assistant',
                'content' => $greetingMessage,
                'sent_at' => now(),
            ]);
        }

        $chat->load(['messages' => function ($query) {
            $query->orderBy('sent_at', 'asc');
        }]);

        // Format messages for client: include virtual assistant messages
        // created from the `answer` column on user messages so the frontend
        // shows assistant replies even though we store them inline.
        $formatted = $this->formatMessagesForClient($chat->messages);
        $chatArray = $chat->toArray();
        $chatArray['messages'] = $formatted;

        return response()->json([
            'success' => true,
            'chat' => $chatArray,
        ]);
    }

    /**
     * Send a message to the chat
     */
    public function sendMessage(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'session_id' => 'required|string',
            'message' => 'required|string|max:2000',
            'is_context' => 'sometimes|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        // Find or create chat
        $chat = Chat::where('session_id', $request->session_id)->first();

        if (!$chat) {
            return response()->json([
                'success' => false,
                'message' => 'Chat session not found. Please start a new chat.',
            ], 404);
        }

        $isContext = $request->boolean('is_context');

        // Save initial user message row (we'll update with AI answer + classifications)
        $userMessage = ChatMessage::create([
            'chat_id' => $chat->id,
            'role' => $isContext ? 'context' : 'user',
            'content' => $request->message,
            'sent_at' => now(),
            'metadata' => [
                'is_context' => $isContext
            ]
        ]);

        // Update chat activity
        $chat->updateLastActivity();

        // If it's just context, don't generate AI response
        if ($isContext) {
            return response()->json([
                'success' => true,
                'message' => 'Context saved successfully'
            ]);
        }

        // Get conversation history for context (specific to this session only)
        $recentMessages = $chat->messages()
            ->whereIn('role', ['user', 'assistant', 'context'])
            ->orderBy('sent_at', 'desc')
            ->limit(10) // Include more messages to capture context
            ->get()
            ->reverse()
            ->map(function ($msg) {
                return [
                    'role' => $msg->role === 'context' ? 'system' : $msg->role,
                    'content' => $msg->content,
                    'timestamp' => $msg->sent_at->toISOString()
                ];
            })
            ->toArray();

        // Add current user message to context
        $currentContext = array_merge($recentMessages, [
            [
                'role' => 'user',
                'content' => $request->message,
                'timestamp' => now()->toISOString()
            ]
        ]);

        // Get AI response with session-specific context
        $aiResponse = $this->openAIService->generateCustomerServiceResponse(
            $currentContext,
            "Session ID: {$request->session_id}, Current time: " . now()->format('Y-m-d H:i:s')
        );

        // Classify the user message quickly using classification endpoint (single-item)
        $topic = null;
        $sentiment = null;
        $confidence = null;
        $snippet = null;
        try {
            $labels = [];
            foreach (config('analytics.categories', []) as $k) {
                $labels[$k] = ucwords(str_replace('_', ' ', $k));
            }
            $cls = $this->openAIService->classifyChats([
                ['chat_id' => (string)$userMessage->id, 'text' => $request->message]
            ], $labels);
            if (!empty($cls['data'][0])) {
                $row = $cls['data'][0];
                $topic = $row['category'] ?? null;
                $sentiment = $row['sentiment'] ?? null;
                $confidence = $row['confidence'] ?? null;
                $snippet = $row['snippet'] ?? null;
            }
        } catch (\Throwable $e) {
            // non-fatal; leave nulls
        }

        if ($aiResponse['success']) {
            // Update existing user message with AI answer + topic/sentiment
            $userMessage->answer = $aiResponse['message'];
            $userMessage->topic = $topic;
            $userMessage->sentiment = $sentiment;
            $meta = $userMessage->metadata ?? [];
            $meta['tokens_used'] = $aiResponse['usage'] ?? null;
            $meta['model'] = config('services.openai.model');
            if ($confidence !== null) $meta['classification_confidence'] = $confidence;
            if ($snippet) $meta['classification_snippet'] = $snippet;
            $userMessage->metadata = $meta;
            $userMessage->save();

            // Build assistant message payload for immediate UI display. We do not
            // create a separate DB row for the assistant message in this flow
            // because assistant replies are stored inline in the user's row
            // (`answer`). Frontend expects `assistant_message` in the reply.
            $assistantMessage = [
                'role' => 'assistant',
                'content' => $aiResponse['message'],
                'sent_at' => now()->format('Y-m-d H:i:s'),
            ];

            return response()->json([
                'success' => true,
                'message' => $userMessage,
                'assistant_message' => $assistantMessage,
                'usage' => $aiResponse['usage'] ?? null,
            ]);
        } else {
            // Update message with error details
            $meta = $userMessage->metadata ?? [];
            $meta['error'] = true;
            $meta['error_details'] = $aiResponse['error'] ?? null;
            $userMessage->metadata = $meta;
            $userMessage->save();

            return response()->json([
                'success' => false,
                'message' => $userMessage,
                'error' => $aiResponse['error'] ?? 'Unknown error',
            ], 500);
        }
    }

    /**
     * Get chat history
     */
    public function getChatHistory(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'session_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $chat = Chat::where('session_id', $request->session_id)
            ->with(['messages' => function ($query) {
                $query->orderBy('sent_at', 'asc');
            }])
            ->first();

        if (!$chat) {
            return response()->json([
                'success' => false,
                'message' => 'Chat not found',
            ], 404);
        }

        // Format messages similarly to startChat so assistant answers saved
        // in `answer` are shown as assistant message bubbles on the client.
        $formatted = $this->formatMessagesForClient($chat->messages);
        $chatArray = $chat->toArray();
        $chatArray['messages'] = $formatted;

        return response()->json([
            'success' => true,
            'chat' => $chatArray,
        ]);
    }

    /**
     * Convert DB messages to a client-friendly array and inject virtual
     * assistant messages for any user message that has an `answer`.
     * This does not persist anything to the database.
     *
     * @param \Illuminate\Support\Collection|array $messages
     * @return array
     */
    private function formatMessagesForClient($messages): array
    {
        $out = [];
        foreach ($messages as $msg) {
            // Normalize message to array
            $m = $msg instanceof \Illuminate\Database\Eloquent\Model ? $msg->toArray() : (array) $msg;
            $out[] = $m;

            // If this is a user message with an inline answer, add a virtual
            // assistant message immediately after so the UI can render it.
            if ((isset($m['role']) && $m['role'] === 'user') && !empty($m['answer'])) {
                $assistant = [
                    'id' => 'assistant_' . ($m['id'] ?? uniqid()),
                    'chat_id' => $m['chat_id'] ?? null,
                    'role' => 'assistant',
                    'content' => $m['answer'],
                    'answer' => null,
                    'topic' => null,
                    'sentiment' => null,
                    'metadata' => null,
                    'sent_at' => isset($m['updated_at']) ? $m['updated_at'] : now()->format('Y-m-d H:i:s'),
                    'created_at' => isset($m['updated_at']) ? $m['updated_at'] : now()->format('Y-m-d H:i:s'),
                ];
                $out[] = $assistant;
            }
        }
        return $out;
    }

    /**
     * Close chat session
     */
    public function closeChat(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'session_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $chat = Chat::where('session_id', $request->session_id)->first();

        if (!$chat) {
            return response()->json([
                'success' => false,
                'message' => 'Chat not found',
            ], 404);
        }

        $chat->update(['status' => 'closed']);

        // Add closing message
        ChatMessage::create([
            'chat_id' => $chat->id,
            'role' => 'assistant',
            'content' => 'Terima kasih telah menggunakan layanan Customer Service Samsat Lamongan. Semoga informasi yang diberikan bermanfaat!',
            'sent_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Chat session closed successfully',
        ]);
    }
}
