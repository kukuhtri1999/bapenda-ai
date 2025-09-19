<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ChatMessage;
use App\Services\OpenAIService;

class TestChatClassification extends Command
{
  protected $signature = 'test:chat-classification';
  protected $description = 'Test that chat messages are properly classified and saved to database';

  public function handle()
  {
    $this->info('Testing chat classification...');

    // Create a test message
    $testMessage = "jadwal samsat keliling malam hari apa saja buka";

    $this->info("Test message: {$testMessage}");

    // Get classification
    $openAI = app(OpenAIService::class);
    $labels = [];
    foreach (config('analytics.categories', []) as $k) {
      $labels[$k] = ucwords(str_replace('_', ' ', $k));
    }

    $cls = $openAI->classifyChats([
      ['chat_id' => 'test-123', 'text' => $testMessage]
    ], $labels);

    $this->info('Classification result:');
    $this->info(json_encode($cls, JSON_PRETTY_PRINT));

    if (!empty($cls['data'][0])) {
      $row = $cls['data'][0];
      $topic = $row['category'] ?? null;
      $sentiment = $row['sentiment'] ?? null;
      $confidence = $row['confidence'] ?? null;

      $this->info("Topic: {$topic}");
      $this->info("Sentiment: {$sentiment}");
      $this->info("Confidence: {$confidence}");

      // Now create a real database entry to test saving
      $chatMessage = ChatMessage::create([
        'chat_id' => 1, // Use existing chat ID
        'role' => 'user',
        'content' => $testMessage,
        'answer' => 'Test response',
        'topic' => $topic,
        'sentiment' => $sentiment,
        'metadata' => [
          'classification_confidence' => $confidence,
          'test' => true
        ],
        'sent_at' => now()
      ]);

      $this->info("Saved message ID: {$chatMessage->id}");

      // Verify it was saved correctly
      $saved = ChatMessage::find($chatMessage->id);
      $this->info("Verified saved topic: {$saved->topic}");
      $this->info("Verified saved sentiment: {$saved->sentiment}");

      if ($saved->topic && $saved->sentiment) {
        $this->info('✅ Classification is working! Topic and sentiment are being saved correctly.');
      } else {
        $this->error('❌ Classification failed! Topic or sentiment not saved.');
      }
    } else {
      $this->error('❌ Classification failed! No results returned.');
    }
  }
}
