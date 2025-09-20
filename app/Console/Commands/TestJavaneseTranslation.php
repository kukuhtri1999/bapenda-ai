<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\OpenAIService;

class TestJavaneseTranslation extends Command
{
  protected $signature = 'test:javanese-translation';
  protected $description = 'Test the enhanced Javanese translation system';

  public function handle()
  {
    $this->info('Testing Enhanced Javanese Translation System...');

    $openaiService = app(OpenAIService::class);

    // Use reflection to access the private method for testing
    $reflection = new \ReflectionClass($openaiService);
    $method = $reflection->getMethod('translateJavaneseQuery');
    $method->setAccessible(true);

    $testQueries = [
      'Opo iki sistem pajak sing anyar?',
      'Piro biaya perpanjang STNK motor?',
      'Carone cara ngurus pajak mobil?',
      'Nek pengen lapor pajak online piye carane?',
      'Dimana kantor samsat sing paling cedhek?',
      'Wis tutup durung samsat bengi iki?',
      'Berapa harga pajak motor tahun ini?', // Already Indonesian
      'What is the tax amount?' // English - should not be translated
    ];

    foreach ($testQueries as $query) {
      $this->line("\n" . str_repeat('=', 60));
      $this->info("Testing: {$query}");
      $this->line(str_repeat('=', 60));

      try {
        $result = $method->invoke($openaiService, $query);

        $this->line("Original: " . $result['original']);
        $this->line("Translated: " . $result['translated']);
        $this->line("Is Javanese: " . ($result['is_javanese'] ? 'Yes' : 'No'));
        $this->line("Method: " . ($result['translation_method'] ?? 'unknown'));

        if (!empty($result['detected_javanese'])) {
          $this->line("Detected terms: " . implode(', ', $result['detected_javanese']));
        }
      } catch (\Exception $e) {
        $this->error("Error testing query: " . $e->getMessage());
      }
    }

    $this->info("\nJavanese translation testing completed!");
    return 0;
  }
}
