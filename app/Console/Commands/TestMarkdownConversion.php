<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\RichContentProcessor;

class TestMarkdownConversion extends Command
{
  protected $signature = 'test:markdown-conversion';
  protected $description = 'Test markdown to HTML conversion';

  public function handle()
  {
    $this->info('🎨 Testing Markdown to HTML Conversion...');

    $richContentProcessor = app(RichContentProcessor::class);

    $testMarkdown = '## Panduan Pembayaran PKB Online

### Persyaratan Dokumen
Berikut adalah dokumen yang diperlukan:

1. **STNK asli** kendaraan yang masih berlaku
2. **KTP** pemilik kendaraan
3. **Nomor rekening** untuk pembayaran

### Langkah-langkah Pembayaran

1. Kunjungi website resmi [Portal PKB Jawa Timur](https://info.dipendajatim.go.id/index.php?page=info_pkb)
2. Masukkan nomor polisi kendaraan
3. Verifikasi data dan lakukan pembayaran

### Metode Pembayaran Tersedia
- Transfer bank
- E-wallet (OVO, GoPay, DANA)
- Minimarket (Alfamart, Indomaret)

Untuk bantuan, hubungi [Tim Support](https://samsat.jatimprov.go.id/layanan.php?id=help&category=support)

![Tutorial PKB](/storage/images/tutorial-pkb.jpg)

**Catatan penting**: Pastikan pembayaran dilakukan sebelum tanggal jatuh tempo.';

    $this->info('Original Markdown:');
    $this->info('=' . str_repeat('=', 50));
    $this->line($testMarkdown);
    $this->info('=' . str_repeat('=', 50));

    $this->info('');
    $this->info('Converting to HTML...');

    $htmlResult = $richContentProcessor->convertAIMarkdownToHTML($testMarkdown);

    $this->info('');
    $this->info('Converted HTML:');
    $this->info('=' . str_repeat('=', 50));
    $this->line($htmlResult);
    $this->info('=' . str_repeat('=', 50));

    $this->info('');
    $this->info('Analyzing HTML Content:');

    // Check for various HTML elements
    $hasH2 = preg_match('/<h2[^>]*>.*?<\/h2>/s', $htmlResult);
    $hasH3 = preg_match('/<h3[^>]*>.*?<\/h3>/s', $htmlResult);
    $hasOL = preg_match('/<ol[^>]*>.*?<\/ol>/s', $htmlResult);
    $hasUL = preg_match('/<ul[^>]*>.*?<\/ul>/s', $htmlResult);
    $hasLinks = preg_match('/<a[^>]*href=[^>]*>.*?<\/a>/s', $htmlResult);
    $hasImages = preg_match('/<img[^>]*src=[^>]*>/s', $htmlResult);
    $hasLightbox = preg_match('/kb-lightbox/', $htmlResult);
    $hasStrong = preg_match('/<strong[^>]*>.*?<\/strong>/s', $htmlResult);

    $this->info('✅ H2 Headers: ' . ($hasH2 ? 'Found' : 'Not found'));
    $this->info('✅ H3 Headers: ' . ($hasH3 ? 'Found' : 'Not found'));
    $this->info('✅ Ordered Lists: ' . ($hasOL ? 'Found' : 'Not found'));
    $this->info('✅ Unordered Lists: ' . ($hasUL ? 'Found' : 'Not found'));
    $this->info('✅ Links: ' . ($hasLinks ? 'Found' : 'Not found'));
    $this->info('✅ Images: ' . ($hasImages ? 'Found' : 'Not found'));
    $this->info('✅ Lightbox Support: ' . ($hasLightbox ? 'Found' : 'Not found'));
    $this->info('✅ Bold Text: ' . ($hasStrong ? 'Found' : 'Not found'));

    // Check URL preservation
    $originalUrl = 'https://info.dipendajatim.go.id/index.php?page=info_pkb';
    if (strpos($htmlResult, $originalUrl) !== false) {
      $this->info('✅ URL Preservation: URLs with special characters preserved');
    } else {
      $this->error('❌ URL Preservation: URLs may have been corrupted');
    }

    $this->info('');
    $this->info('🎉 Markdown Conversion Test Complete! 🎉');
  }
}
