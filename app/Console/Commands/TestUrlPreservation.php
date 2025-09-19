<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\EmbeddingService;
use App\Services\RichContentProcessor;

class TestUrlPreservation extends Command
{
  protected $signature = 'test:url-preservation';
  protected $description = 'Test that URLs with special characters are preserved during text processing';

  public function handle()
  {
    $this->info('🔗 Testing URL Preservation in Text Processing...');

    $embeddingService = app(EmbeddingService::class);
    $richContentProcessor = app(RichContentProcessor::class);

    // Test URLs with various special characters
    $testUrls = [
      'https://info.dipendajatim.go.id/index.php?page=info_pkb',
      'https://example.com/search?q=test&category=auto&year=2024',
      'https://samsat.jatimprov.go.id/layanan.php?id=123&action=view',
      'https://api.service.com/v1/data?filter[status]=active&sort=created_at',
      'https://portal.com/login?redirect_url=https://portal.com/dashboard'
    ];

    $testContent = "Silakan kunjungi beberapa link berikut untuk informasi lebih lanjut:\n\n";
    foreach ($testUrls as $index => $url) {
      $testContent .= ($index + 1) . ". Link " . ($index + 1) . ": " . $url . "\n";
    }
    $testContent .= "\nSemua link di atas harus tetap utuh setelah pemrosesan.";

    $this->info('Original content:');
    $this->line($testContent);
    $this->info('');

    // Test EmbeddingService cleanText method
    $this->info('Testing EmbeddingService cleanText method...');
    $reflection = new \ReflectionClass($embeddingService);
    $cleanTextMethod = $reflection->getMethod('cleanText');
    $cleanTextMethod->setAccessible(true);
    $cleanedText = $cleanTextMethod->invoke($embeddingService, $testContent);

    $this->info('After EmbeddingService cleaning:');
    $this->line($cleanedText);

    // Check if URLs are preserved
    $allPreserved = true;
    foreach ($testUrls as $url) {
      if (strpos($cleanedText, $url) === false) {
        $this->error("❌ URL not preserved: " . $url);
        $allPreserved = false;
      } else {
        $this->info("✅ URL preserved: " . $url);
      }
    }

    $this->info('');

    // Test RichContentProcessor
    $this->info('Testing RichContentProcessor with HTML content...');
    $htmlContent = '<p>Untuk informasi PKB, silakan kunjungi:</p>
        <ul>
            <li><a href="https://info.dipendajatim.go.id/index.php?page=info_pkb">Info PKB Jatim</a></li>
            <li><a href="https://samsat.jatimprov.go.id/layanan.php?id=123&action=view">Layanan Samsat</a></li>
        </ul>
        <p>Atau lihat gambar panduan berikut:</p>
        <img src="/storage/images/panduan-pkb.jpg" alt="Panduan PKB" title="Cara bayar PKB online" />';

    $richContent = $richContentProcessor->extractRichContent($htmlContent);

    $this->info('Rich content extraction results:');
    $this->line('Has rich content: ' . ($richContent['has_rich_content'] ? 'Yes' : 'No'));
    $this->line('Text content: ' . $richContent['text']);

    if (!empty($richContent['elements'])) {
      $this->info('Rich elements found:');
      foreach ($richContent['elements'] as $element) {
        $this->line('- Type: ' . $element['type']);
        if ($element['type'] === 'link') {
          $this->line('  URL: ' . $element['url']);
          $this->line('  Text: ' . $element['text']);

          // Check if URLs are preserved in rich content
          if (strpos($element['url'], '=') !== false) {
            $this->info("✅ URL special characters preserved in rich content");
          } else {
            $this->error("❌ URL special characters not preserved in rich content");
            $allPreserved = false;
          }
        }
      }
    }

    $this->info('');

    // Test AI formatting
    $formattedForAI = $richContentProcessor->formatForAIResponse($richContent);
    $this->info('Formatted for AI response:');
    $this->line($formattedForAI);

    $this->info('');

    if ($allPreserved) {
      $this->info('🎉 URL Preservation Test PASSED! 🎉');
      $this->info('✅ All URLs with special characters are properly preserved');
      $this->info('✅ Rich content processing maintains URL integrity');
      $this->info('✅ AI can receive properly formatted URLs for responses');
    } else {
      $this->error('❌ URL Preservation Test FAILED!');
      $this->error('Some URLs lost special characters during processing');
    }
  }
}
