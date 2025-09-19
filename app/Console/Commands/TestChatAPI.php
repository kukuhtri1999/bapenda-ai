<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TestChatAPI extends Command
{
  protected $signature = 'test:chat-api';
  protected $description = 'Test the chat API endpoint for markdown conversion';

  public function handle()
  {
    $this->info('🔥 Testing Chat API Markdown Conversion...');

    $testMarkdown = '## Panduan Pembayaran PKB Online

### Persyaratan Dokumen
Berikut adalah dokumen yang diperlukan:

1. **STNK asli** kendaraan yang masih berlaku
2. **KTP** pemilik kendaraan

### Langkah-langkah Pembayaran

1. Kunjungi website resmi [Portal PKB Jawa Timur](https://info.dipendajatim.go.id/index.php?page=info_pkb)
2. Masukkan nomor polisi kendaraan

![Tutorial PKB](/storage/images/tutorial-pkb.jpg)

**Catatan penting**: Pastikan pembayaran dilakukan sebelum tanggal jatuh tempo.';

    try {
      $response = Http::post('http://127.0.0.1:8000/api/chat/convert-markdown', [
        'content' => $testMarkdown
      ]);

      if ($response->successful()) {
        $this->info('✅ API call successful!');
        $result = $response->json();

        $this->info('');
        $this->info('Response:');
        $this->info('=' . str_repeat('=', 50));
        $this->line($result['html']);
        $this->info('=' . str_repeat('=', 50));

        // Check for important elements
        $html = $result['html'];
        $hasHeaders = strpos($html, '<h2') !== false;
        $hasLists = strpos($html, '<ol') !== false;
        $hasLinks = strpos($html, '<a href') !== false;
        $hasImages = strpos($html, '<img') !== false;
        $hasLightbox = strpos($html, 'kb-lightbox') !== false;

        $this->info('');
        $this->info('Analysis:');
        $this->info('✅ Headers: ' . ($hasHeaders ? 'Found' : 'Not found'));
        $this->info('✅ Lists: ' . ($hasLists ? 'Found' : 'Not found'));
        $this->info('✅ Links: ' . ($hasLinks ? 'Found' : 'Not found'));
        $this->info('✅ Images: ' . ($hasImages ? 'Found' : 'Not found'));
        $this->info('✅ Lightbox: ' . ($hasLightbox ? 'Found' : 'Not found'));
      } else {
        $this->error('❌ API call failed!');
        $this->error('Status: ' . $response->status());
        $this->error('Response: ' . $response->body());
      }
    } catch (\Exception $e) {
      $this->error('❌ Error: ' . $e->getMessage());
    }

    $this->info('');
    $this->info('🎉 Chat API Test Complete! 🎉');
  }
}
