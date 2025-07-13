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

        return response()->json([
            'success' => true,
            'chat' => $chat,
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

        // Save user message
        $userMessage = ChatMessage::create([
            'chat_id' => $chat->id,
            'role' => 'user',
            'content' => $request->message,
            'sent_at' => now(),
        ]);

        // Update chat activity
        $chat->updateLastActivity();

        // Get conversation history for context (specific to this session only)
        $recentMessages = $chat->messages()
            ->whereIn('role', ['user', 'assistant'])
            ->orderBy('sent_at', 'desc')
            ->limit(8) // Reduce to 8 for better context management
            ->get()
            ->reverse()
            ->map(function ($msg) {
                return [
                    'role' => $msg->role,
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

        if ($aiResponse['success']) {
            // Save AI response
            $assistantMessage = ChatMessage::create([
                'chat_id' => $chat->id,
                'role' => 'assistant',
                'content' => $aiResponse['message'],
                'sent_at' => now(),
                'metadata' => [
                    'tokens_used' => $aiResponse['usage'] ?? null,
                    'model' => config('services.openai.model'),
                ]
            ]);

            return response()->json([
                'success' => true,
                'user_message' => $userMessage,
                'assistant_message' => $assistantMessage,
                'usage' => $aiResponse['usage'] ?? null,
            ]);
        } else {
            // Save error response
            $errorMessage = ChatMessage::create([
                'chat_id' => $chat->id,
                'role' => 'assistant',
                'content' => $aiResponse['message'],
                'sent_at' => now(),
                'metadata' => [
                    'error' => true,
                    'error_details' => $aiResponse['error'] ?? null,
                ]
            ]);

            return response()->json([
                'success' => false,
                'user_message' => $userMessage,
                'assistant_message' => $errorMessage,
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

        return response()->json([
            'success' => true,
            'chat' => $chat,
        ]);
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
