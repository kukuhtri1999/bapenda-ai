<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\OpenAIService;
use Illuminate\Support\Facades\Log;

class TestJavaneseSupport extends Command
{
  protected $signature = 'test:javanese';
  protected $description = 'Test Javanese language support in AI chat';

  public function handle()
  {
    $this->info('Testing Javanese Language Support...');

    $openaiService = app(OpenAIService::class);

    // Test cases with Javanese queries
    $testQueries = [
      'Opo iki sistem pajak sing anyar?',
      'Piro pajak property kanggo omah?',
      'Carone cara ngurus pajak motor?',
      'Nek pengen lapor pajak online piye?',
      'Aku arep ngurus NPWP, dokumen opo wae sing dibutuhake?'
    ];

    foreach ($testQueries as $query) {
      $this->line("\n" . str_repeat('=', 50));
      $this->info("Testing: {$query}");
      $this->line(str_repeat('=', 50));

      $messages = [
        ['role' => 'user', 'content' => $query]
      ];

      $result = $openaiService->generateCustomerServiceResponse($messages);

      $this->info("Result structure: " . print_r(array_keys($result), true));

      if ($result['success']) {
        $message = $result['message'] ?? 'No message';
        $this->comment("Response: " . substr($message, 0, 200) . '...');

        if (isset($result['processing_time'])) {
          $this->line("Processing time: {$result['processing_time']}ms");
        }

        $knowledgeUsed = $result['knowledge_used'] ?? 0;
        $this->line("Knowledge used: {$knowledgeUsed} items");
      } else {
        $this->error("Failed: " . $result['message']);
      }
    }

    $this->info("\nJavanese language testing completed!");
    return 0;
  }
}
