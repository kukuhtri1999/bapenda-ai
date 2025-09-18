<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\OpenAIService;

class TestNewRagSystem extends Command
{
  /**
   * The name and signature of the console command.
   */
  protected $signature = 'rag:test-new {query* : The query to test}';

  /**
   * The console command description.
   */
  protected $description = 'Test the new RAG system with Pinecone and OpenAI embeddings';

  /**
   * Execute the console command.
   */
  public function handle()
  {
    $query = implode(' ', $this->argument('query'));

    if (empty($query)) {
      $this->error('Please provide a query to test');
      return 1;
    }

    $this->info("Testing RAG system with query: {$query}");
    $this->newLine();

    $openAIService = app(OpenAIService::class);

    $messages = [
      ['role' => 'user', 'content' => $query]
    ];

    $start = microtime(true);
    $result = $openAIService->generateCustomerServiceResponse($messages);
    $duration = round((microtime(true) - $start) * 1000);

    $this->info("Response generated in {$duration}ms");
    $this->newLine();

    $this->info('=== RESULT ===');
    $this->line('Success: ' . ($result['success'] ? 'Yes' : 'No'));
    $this->line('Knowledge Used: ' . ($result['knowledge_used'] ?? 0));

    if (isset($result['usage'])) {
      $usage = $result['usage'];
      $this->line('Token Usage: ' . json_encode($usage));
    }

    $this->newLine();
    $this->info('=== RESPONSE ===');
    $this->line($result['message'] ?? 'No message');

    if (!$result['success']) {
      return 1;
    }

    return 0;
  }
}
