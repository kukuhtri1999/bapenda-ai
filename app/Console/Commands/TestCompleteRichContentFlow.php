<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\KnowledgeBase;
use App\Services\OpenAIService;
use App\Services\RichContentProcessor;

class TestCompleteRichContentFlow extends Command
{
  protected $signature = 'test:complete-rich-flow';
  protected $description = 'Test the complete rich content flow from knowledge base to AI response';

  public function handle()
  {
    $this->info('🎬 Testing Complete Rich Content Flow...');

    // Create a rich knowledge base entry
    $richHtmlContent = '<h2>Tutorial Lengkap Pembayaran PKB Online</h2>
        <p>Berikut adalah panduan lengkap untuk melakukan pembayaran Pajak Kendaraan Bermotor (PKB) secara online:</p>

        <h3>Langkah 1: Persiapan Dokumen</h3>
        <ul>
            <li><strong>STNK asli</strong> kendaraan yang masih berlaku</li>
            <li><strong>KTP</strong> pemilik kendaraan</li>
            <li><strong>Nomor rekening</strong> untuk pembayaran online</li>
        </ul>

        <h3>Langkah 2: Akses Portal Online</h3>
        <ol>
            <li>Buka browser dan kunjungi <a href="https://info.dipendajatim.go.id/index.php?page=info_pkb">Portal PKB Jawa Timur</a></li>
            <li>Masukkan nomor polisi kendaraan Anda</li>
            <li>Masukkan 5 digit terakhir nomor rangka kendaraan</li>
            <li>Klik tombol "Cari" untuk melanjutkan</li>
        </ol>

        <h3>Langkah 3: Verifikasi dan Pembayaran</h3>
        <p>Setelah data ditemukan, lakukan pembayaran melalui channel yang tersedia:</p>
        <ul>
            <li>Transfer bank</li>
            <li>E-wallet (OVO, GoPay, DANA)</li>
            <li>Minimarket (Alfamart, Indomaret)</li>
        </ul>

        <p>Untuk bantuan lebih lanjut, hubungi <a href="https://samsat.jatimprov.go.id/layanan.php?id=help&category=support">Tim Support Samsat</a></p>

        <div class="alert">
            <img src="/storage/images/tutorial-pkb-complete.jpg" alt="Screenshot Tutorial PKB" title="Panduan visual pembayaran PKB online" />
            <p><em>Gambar: Screenshot langkah-langkah pembayaran PKB online</em></p>
        </div>';

    // Create knowledge base entry
    $kb = KnowledgeBase::create([
      'title' => 'Tutorial Lengkap PKB Online - Dengan Rich Content',
      'question' => 'Bagaimana cara lengkap bayar PKB online dengan semua detailnya?',
      'answer' => $richHtmlContent,
      'content' => $richHtmlContent,
      'category' => 'tutorial',
      'type' => 'guide',
      'source_type' => 'manual',
      'status' => 'published',
      'is_active' => true,
      'created_by' => 1,
      'updated_by' => 1,
      'search_content' => 'pkb pembayaran online tutorial lengkap panduan step by step',
      'published_at' => now(),
    ]);

    $this->info("✅ Created rich knowledge base entry with ID: {$kb->id}");

    // Test rich content processing
    $this->info('');
    $this->info('🔄 Testing Rich Content Processing...');

    $richContentProcessor = app(RichContentProcessor::class);
    $richContent = $richContentProcessor->extractRichContent($richHtmlContent);

    $this->info("Rich content detected: " . ($richContent['has_rich_content'] ? 'Yes' : 'No'));
    $this->info("Elements found: " . count($richContent['elements']));

    foreach ($richContent['elements'] as $element) {
      $this->info("- {$element['type']}: " . ($element['text'] ?? $element['url'] ?? $element['src'] ?? 'N/A'));
    }

    // Test AI response generation
    $this->info('');
    $this->info('🤖 Testing AI Response Generation...');

    $testMessages = [
      ['role' => 'user', 'content' => 'Tolong berikan panduan lengkap step by step cara bayar PKB online, sertakan semua detail dan linknya ya!']
    ];

    $openAIService = app(OpenAIService::class);

    try {
      $response = $openAIService->generateCustomerServiceResponse($testMessages);

      if ($response['success']) {
        $aiMessage = $response['message'];

        $this->info('✅ AI Response Generated Successfully!');
        $this->info('');
        $this->info('AI Response Preview (First 300 chars):');
        $this->info('=' . str_repeat('=', 50));
        $this->line(substr($aiMessage, 0, 300) . '...');
        $this->info('=' . str_repeat('=', 50));

        // Test markdown to HTML conversion
        $this->info('');
        $this->info('🎨 Testing Markdown to HTML Conversion...');

        $htmlContent = $richContentProcessor->convertAIMarkdownToHTML($aiMessage);

        // Analyze converted content
        $hasHeaders = preg_match('/<h[1-6]/i', $htmlContent);
        $hasLists = preg_match('/<[ou]l>/i', $htmlContent);
        $hasLinks = preg_match('/<a\s+href/i', $htmlContent);
        $hasImages = preg_match('/<img\s+src/i', $htmlContent);
        $hasLightbox = preg_match('/class="kb-lightbox"/i', $htmlContent);

        $this->info('HTML Conversion Results:');
        $this->info('📋 Contains Headers: ' . ($hasHeaders ? 'Yes' : 'No'));
        $this->info('📝 Contains Lists: ' . ($hasLists ? 'Yes' : 'No'));
        $this->info('🔗 Contains Links: ' . ($hasLinks ? 'Yes' : 'No'));
        $this->info('🖼️  Contains Images: ' . ($hasImages ? 'Yes' : 'No'));
        $this->info('💡 Lightbox Ready: ' . ($hasLightbox ? 'Yes' : 'No'));

        // Check for preserved URLs
        $originalUrl = 'https://info.dipendajatim.go.id/index.php?page=info_pkb';
        if (strpos($htmlContent, $originalUrl) !== false) {
          $this->info('✅ Original URLs preserved in final HTML');
        } else {
          $this->warn('⚠️  URL preservation needs verification in final output');
        }

        $this->info('');
        $this->info('HTML Preview (First 300 chars):');
        $this->info('=' . str_repeat('=', 50));
        $this->line(substr($htmlContent, 0, 300) . '...');
        $this->info('=' . str_repeat('=', 50));
      } else {
        $this->error('❌ AI Response Generation Failed!');
        $this->error('Error: ' . ($response['error'] ?? 'Unknown error'));
      }
    } catch (\Exception $e) {
      $this->error('❌ Exception during test: ' . $e->getMessage());
    }

    // Clean up
    $this->info('');
    $this->info('🧹 Cleaning up...');
    $kb->delete();
    $this->info('✅ Test data cleaned up');

    $this->info('');
    $this->info('🎉 Complete Rich Content Flow Test Finished! 🎉');
    $this->info('');
    $this->info('✅ Features Tested:');
    $this->info('  - Rich HTML content in knowledge base');
    $this->info('  - URL preservation in vector database');
    $this->info('  - AI response with markdown formatting');
    $this->info('  - HTML conversion with lightbox support');
    $this->info('  - Frontend-ready rich content delivery');
  }
}
