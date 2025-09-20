<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\EmbeddingService;
use App\Services\RichContentProcessor;

class TestTextFormatting extends Command
{
  protected $signature = 'test:text-formatting';
  protected $description = 'Test text formatting improvements for vector database and AI responses';

  public function handle()
  {
    $this->info('🎨 Testing Text Formatting Improvements...');

    // Test HTML content with various formatting issues
    $testHtml = '<p>Untuk   mengetahui tarif  pajak kendaraan   bermotor(PKB)Anda dapat melakukan pengecekan online.</p>
        <p>Berikut langkah-langkahnya :</p>
        <ul>
        <li><strong>STNK asli</strong>kendaraan</li>
        <li><strong>KTP</strong> pemilik  kendaraan</li>
        </ul>
        <p>Kunjungi website <a href="https://info.dipendajatim.go.id/index.php?page=info_pkb">Portal PKB</a></p>
        <p>Hubungi Nomor123untuk  bantuan.</p>';

    $this->info('Original HTML:');
    $this->info('=' . str_repeat('=', 60));
    $this->line($testHtml);
    $this->info('=' . str_repeat('=', 60));

    // Test embedding service text cleaning
    $embeddingService = app(EmbeddingService::class);
    $cleanedText = $this->callPrivateMethod($embeddingService, 'cleanText', [$testHtml]);

    $this->info('');
    $this->info('After EmbeddingService cleaning:');
    $this->info('=' . str_repeat('=', 60));
    $this->line($cleanedText);
    $this->info('=' . str_repeat('=', 60));

    // Test rich content processor
    $richContentProcessor = app(RichContentProcessor::class);
    $richContentData = $richContentProcessor->extractRichContent($testHtml);

    $this->info('');
    $this->info('Rich Content Analysis:');
    $this->info("- Has Rich Content: " . ($richContentData['has_rich_content'] ? 'Yes' : 'No'));
    $this->info("- Elements Found: " . count($richContentData['elements']));

    foreach ($richContentData['elements'] as $element) {
      $this->info("  - {$element['type']}: " .
        ($element['type'] === 'link' ? $element['url'] : ($element['type'] === 'list' ? count($element['items']) . ' items' : ($element['type'] === 'header' ? $element['text'] : 'N/A'))));
    }

    $this->info('');
    $this->info('Formatted for AI Response:');
    $this->info('=' . str_repeat('=', 60));
    $formattedForAI = $richContentProcessor->formatForAIResponse($richContentData);
    $this->line($formattedForAI);
    $this->info('=' . str_repeat('=', 60));

    // Test chunking with improved text
    $chunks = $embeddingService->chunkText($cleanedText, 200, 50);

    $this->info('');
    $this->info('Text Chunking Results:');
    $this->info("- Number of chunks: " . count($chunks));

    foreach ($chunks as $i => $chunk) {
      $this->info("Chunk " . ($i + 1) . " (" . strlen($chunk) . " chars):");
      $this->line('  ' . substr($chunk, 0, 100) . (strlen($chunk) > 100 ? '...' : ''));
    }

    $this->info('');
    $this->info('🎉 Text Formatting Test Complete! 🎉');
  }

  /**
   * Helper method to call private methods for testing
   */
  private function callPrivateMethod($object, $methodName, $parameters = [])
  {
    $reflection = new \ReflectionClass($object);
    $method = $reflection->getMethod($methodName);
    $method->setAccessible(true);

    return $method->invokeArgs($object, $parameters);
  }
}
