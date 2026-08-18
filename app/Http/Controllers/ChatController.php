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

        $greetingMessage = null;
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
            // generate greeting but do NOT persist it; we'll return it as a
            // virtual assistant message in the response so the frontend shows
            // the greeting without storing assistant starter messages in DB.
            $greetingMessage = $this->openAIService->generateGreeting();
        }

        $chat->load(['messages' => function ($query) {
            $query->orderBy('sent_at', 'asc');
        }]);

        // Format messages for client: include virtual assistant messages
        // created from the `answer` column on user messages so the frontend
        // shows assistant replies even though we store them inline.
        $formatted = $this->formatMessagesForClient($chat->messages);

        // If we generated a greeting for a new chat, inject it as a virtual
        // assistant message at the start of the message list (do not persist).
        if (!empty($greetingMessage)) {
            $assistant = [
                'id' => 'assistant_greeting_' . uniqid(),
                'chat_id' => $chat->id,
                'role' => 'assistant',
                'content' => $greetingMessage,
                'answer' => null,
                'topic' => null,
                'sentiment' => null,
                'metadata' => null,
                'sent_at' => now()->format('Y-m-d H:i:s'),
                'created_at' => now()->format('Y-m-d H:i:s'),
            ];
            array_unshift($formatted, $assistant);
        }
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
            ->limit(10)
            ->get()
            ->reverse()
            ->flatMap(function ($msg) {
                $turns = [];
                if ($msg->role === 'context') {
                    $turns[] = [
                        'role' => 'system',
                        'content' => (string) $msg->content,
                    ];
                } elseif ($msg->role === 'user') {
                    $turns[] = [
                        'role' => 'user',
                        'content' => (string) $msg->content,
                    ];
                    if (!empty($msg->answer)) {
                        $turns[] = [
                            'role' => 'assistant',
                            'content' => (string) $msg->answer,
                        ];
                    }
                } elseif ($msg->role === 'assistant') {
                    $turns[] = [
                        'role' => 'assistant',
                        'content' => (string) $msg->content,
                    ];
                }
                return $turns;
            })
            ->values()
            ->toArray();

        // Add current user message to context only if not already present
        $currentContext = $recentMessages;
        $alreadyEndsWithSameUser = false;
        if (!empty($currentContext)) {
            $last = end($currentContext);
            if ($last && ($last['role'] ?? null) === 'user' && trim((string)$last['content']) === trim((string)$request->message)) {
                $alreadyEndsWithSameUser = true;
            }
        }
        if (!$alreadyEndsWithSameUser) {
            $currentContext[] = [
                'role' => 'user',
                'content' => (string) $request->message,
            ];
        }

        // Get AI response with session-specific context (track response time)
        $aiStartTime = microtime(true);
        $aiResponse = $this->openAIService->generateCustomerServiceResponse(
            $currentContext,
            "Session ID: {$request->session_id}, Current time: " . now('Asia/Jakarta')->format('Y-m-d H:i:s')
        );
        $aiResponseTime = round(microtime(true) - $aiStartTime, 2);

        // Fast heuristic classification for live chat (< 1ms, zero extra OpenAI API roundtrip)
        /** @var \App\Services\SalmaPromptService $salmaPrompt */
        $salmaPrompt = app(\App\Services\SalmaPromptService::class);
        $topic = $salmaPrompt->detectIntent($request->message);
        $sentiment = 'neutral';
        $userMsgLower = mb_strtolower($request->message);
        if (preg_match('/\b(terima kasih|makasih|mantap|bagus|hebat|ramah|cepat|keren|sangat membantu|helpful|senang|puas|alhamdulillah)\b/u', $userMsgLower)) {
            $sentiment = 'positive';
        } elseif (preg_match('/\b(kecewa|lambat|lama|rusak|tidak bisa|jengkel|error|salah|keluhan|antre|antrian|mahal|buruk|payah)\b/u', $userMsgLower)) {
            $sentiment = 'negative';
        }
        $confidence = 0.95;
        $snippet = mb_substr($request->message, 0, 150);

        if ($aiResponse['success']) {
            // Update existing user message with AI answer + topic/sentiment + response time
            $userMessage->answer = $aiResponse['message'];
            $userMessage->topic = $topic;
            $userMessage->sentiment = $sentiment;
            $userMessage->response_time_seconds = $aiResponseTime;
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

            $response = [
                'success' => true,
                'message' => $userMessage,
                'assistant_message' => $assistantMessage,
                'usage' => $aiResponse['usage'] ?? null,
            ];

            // Add debug information when RAG_DEBUG is enabled
            if (config('app.debug') && env('RAG_DEBUG', false)) {
                $response['debug_info'] = [
                    'classification' => [
                        'topic' => $topic,
                        'sentiment' => $sentiment,
                        'confidence' => $confidence,
                        'snippet' => $snippet,
                        'classification_success' => !empty($cls['success']),
                        'raw_classification' => $cls ?? null,
                    ],
                    'rag_processing' => $aiResponse['debug'] ?? null,
                    'pinecone_config' => [
                        'api_key_set' => !empty(config('services.pinecone.api_key')),
                        'index_name' => config('services.pinecone.index_name'),
                        'environment' => config('services.pinecone.environment'),
                    ],
                    'environment' => [
                        'app_env' => config('app.env'),
                        'openai_model' => config('services.openai.model'),
                        'rag_debug' => env('RAG_DEBUG'),
                    ]
                ];
            }

            return response()->json($response);
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

    /**
     * Convert markdown content to rich HTML
     */
    public function convertMarkdownToHtml(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'content' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid content provided'
            ], 400);
        }

        $richContentProcessor = app(\App\Services\RichContentProcessor::class);
        $htmlContent = $richContentProcessor->convertAIMarkdownToHTML($request->content);

        return response()->json([
            'success' => true,
            'html' => $htmlContent
        ]);
    }

    /**
     * End chat session and clear user session data
     */
    public function endChatSession(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'session_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            // Find and close the chat
            $chat = Chat::where('session_id', $request->session_id)->first();

            if ($chat) {
                $chat->update([
                    'status' => 'ended',
                    'ended_at' => now(),
                    'last_activity_at' => now()
                ]);
            }

            // Clear wajib pajak session data
            session()->forget(['wajib_pajak_data', 'chat_session_started']);

            return response()->json([
                'success' => true,
                'message' => 'Chat session ended successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengakhiri sesi chat'
            ], 500);
        }
    }

    /**
     * Submit chat feedback
     */
    public function submitFeedback(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'session_id' => 'required|string',
            'rating' => 'required|integer|min:1|max:5',
            'feedback_text' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            // Get wajib pajak data from session
            $wajibPajakData = session('wajib_pajak_data', []);

            // Get chat summary
            $chat = Chat::where('session_id', $request->session_id)->first();
            $chatSummary = null;

            if ($chat) {
                $messageCount = ChatMessage::where('chat_id', $chat->id)->count();
                $lastMessage = ChatMessage::where('chat_id', $chat->id)
                    ->orderBy('sent_at', 'desc')
                    ->first();

                $chatSummary = [
                    'total_messages' => $messageCount,
                    'chat_duration' => $chat->created_at->diffInMinutes($chat->updated_at ?? now()),
                    'last_message_at' => $lastMessage?->sent_at,
                    'chat_title' => $chat->title
                ];
            }

            // Store feedback
            \App\Models\ChatFeedback::create([
                'session_id' => $request->session_id,
                'nama' => $wajibPajakData['nama'] ?? null,
                'nopol' => $wajibPajakData['nopol'] ?? null,
                'nomer_wa' => $wajibPajakData['nomer_wa'] ?? null,
                'rating' => $request->rating,
                'feedback_text' => $request->feedback_text,
                'chat_summary' => $chatSummary,
                'chat_ended_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Terima kasih atas feedback Anda! Masukan Anda sangat berharga untuk meningkatkan layanan kami.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan feedback. Silakan coba lagi.'
            ], 500);
        }
    }

    /**
     * Stream AI chat response in real-time via Server-Sent Events (SSE).
     */
    public function streamMessage(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $request->validate([
            'session_id' => 'required|string',
            'message' => 'required|string|max:2000',
        ]);

        $chat = Chat::where('session_id', $request->session_id)->firstOrFail();

        $userMessage = ChatMessage::create([
            'chat_id' => $chat->id,
            'role' => 'user',
            'content' => $request->message,
            'sent_at' => now(),
        ]);

        $chat->updateLastActivity();

        $recentMessages = $chat->messages()
            ->whereIn('role', ['user', 'assistant'])
            ->orderBy('sent_at', 'desc')
            ->limit(10)
            ->get()
            ->reverse()
            ->flatMap(fn($m) => [
                ['role' => $m->role, 'content' => $m->content],
                ...(!empty($m->answer) ? [['role' => 'assistant', 'content' => $m->answer]] : [])
            ])
            ->values()
            ->toArray();

        return response()->stream(function () use ($recentMessages, $userMessage, $request) {
            $completeText = '';
            try {
                $completeText = $this->openAIService->generateCustomerServiceStream(
                    $recentMessages,
                    function ($chunk) {
                        echo "data: " . json_encode(['chunk' => $chunk]) . "\n\n";
                        if (ob_get_level() > 0) ob_flush();
                        flush();
                    },
                    "Session ID: {$request->session_id}"
                );

                $userMessage->update(['answer' => $completeText]);
                echo "data: " . json_encode(['done' => true, 'complete' => $completeText]) . "\n\n";
            } catch (\Throwable $e) {
                echo "data: " . json_encode(['error' => $e->getMessage()]) . "\n\n";
            }
            if (ob_get_level() > 0) ob_flush();
            flush();
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}
