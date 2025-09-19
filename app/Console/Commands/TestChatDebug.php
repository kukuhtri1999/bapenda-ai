<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\ChatController;
use Illuminate\Http\Request;
use App\Models\Chat;

class TestChatDebug extends Command
{
  protected $signature = 'test:chat-debug';
  protected $description = 'Test the chat API debug functionality';

  public function handle()
  {
    $this->info('Testing chat API debug functionality...');

    // Enable debug mode temporarily
    config(['app.debug' => true]);

    // Create a mock request
    $request = new Request([
      'message' => 'jadwal samsat keliling malam hari apa saja',
      'session_id' => 'test-session-123'
    ]);

    // Get or create a chat with the matching session_id
    $sessionId = 'test-session-123';
    $chat = Chat::where('session_id', $sessionId)->first();
    if (!$chat) {
      $chat = Chat::create([
        'session_id' => $sessionId,
        'title' => 'Test Chat',
        'status' => 'active',
        'metadata' => []
      ]);
    }

    $this->info("Using chat ID: {$chat->id}");
    $this->info("Test message: {$request->message}");

    try {
      // Call the chat controller with proper dependency injection
      $controller = app(ChatController::class);
      $response = $controller->sendMessage($request);

      $responseData = json_decode($response->getContent(), true);

      $this->info('=== Full API Response ===');
      $this->info(json_encode($responseData, JSON_PRETTY_PRINT));

      $this->info('=== API Response ===');
      $this->info("Success: " . ($responseData['success'] ? 'yes' : 'no'));

      if (isset($responseData['debug_info'])) {
        $this->info('=== Debug Information ===');
        $debug = $responseData['debug_info'];

        // Classification debug
        if (isset($debug['classification'])) {
          $cls = $debug['classification'];
          $this->info("Topic: " . ($cls['topic'] ?? 'null'));
          $this->info("Sentiment: " . ($cls['sentiment'] ?? 'null'));
          $this->info("Confidence: " . ($cls['confidence'] ?? 'null'));
          $this->info("Classification Success: " . ($cls['classification_success'] ? 'yes' : 'no'));
        }

        // RAG debug
        if (isset($debug['rag_processing'])) {
          $rag = $debug['rag_processing'];
          $this->info("Embedding Generated: " . ($rag['embedding_generated'] ?? 'no'));
          $this->info("Vector Results Count: " . ($rag['vector_search']['results_count'] ?? 0));
          $this->info("Vector Context Built: " . ($rag['vector_context_built'] ? 'yes' : 'no'));
          $this->info("Fallback to Keyword: " . ($rag['fallback_to_keyword_search'] ? 'yes' : 'no'));
          $this->info("Final Context Used: " . ($rag['final_context_used'] ? 'yes' : 'no'));
          $this->info("Final Context Length: " . ($rag['final_context_length'] ?? 0));
        }

        // Pinecone config
        if (isset($debug['pinecone_config'])) {
          $pc = $debug['pinecone_config'];
          $this->info("Pinecone API Key Set: " . ($pc['api_key_set'] ? 'yes' : 'no'));
          $this->info("Pinecone Index: " . ($pc['index_name'] ?? 'null'));
          $this->info("Pinecone Environment: " . ($pc['environment'] ?? 'null'));
        }

        $this->info('✅ Debug information is being returned in the API response!');
      } else {
        $this->error('❌ No debug information found in API response');
      }
    } catch (\Exception $e) {
      $this->error('Error testing chat API: ' . $e->getMessage());
      $this->error($e->getTraceAsString());
    }
  }
}
