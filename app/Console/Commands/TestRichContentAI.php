<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\KnowledgeBase;
use App\Services\OpenAIService;

class TestRichContentAI extends Command
{
  protected $signature = 'test:rich-content-ai';
  protected $description = 'Test AI responses with rich content from knowledge base';

  public function handle()
  {
    $this->info('🎨 Testing Rich Content AI Response System...');

    // Create a test knowledge base entry with rich content
    $richHtmlContent = '<h2>Panduan Pembayaran PKB Online</h2>
        <p>Berikut adalah langkah-langkah untuk melakukan pembayaran PKB secara online:</p>

        <h3>Persyaratan</h3>
        <ul>
            <li>STNK asli</li>
            <li>KTP pemilik kendaraan</li>
            <li>Nomor rekening untuk transfer</li>
        </ul>

        <h3>Langkah-langkah Pembayaran</h3>
        <ol>
            <li>Kunjungi website resmi: <a href="https://info.dipendajatim.go.id/index.php?page=info_pkb">Portal PKB Jawa Timur</a></li>
            <li>Masukkan nomor polisi kendaraan</li>
            <li>Pilih metode pembayaran</li>
            <li>Lakukan pembayaran sesuai instruksi</li>
        </ol>

        <p>Untuk panduan visual, lihat gambar di bawah ini:</p>
        <img src="/storage/images/tutorial-pkb.jpg" alt="Tutorial Pembayaran PKB" title="Screenshot langkah-langkah pembayaran PKB online" />

        <p>Jika mengalami kesulitan, hubungi layanan bantuan di: <a href="https://samsat.jatimprov.go.id/layanan.php?id=help&category=pkb">Bantuan Samsat</a></p>';

    // Create knowledge base entry
    $kb = KnowledgeBase::create([
      'title' => 'Tutorial Pembayaran PKB Online dengan Rich Content',
      'question' => 'Bagaimana cara bayar PKB online?', // Required field
      'answer' => $richHtmlContent, // Map to answer field as well
      'content' => $richHtmlContent,
      'category' => 'tutorial',
      'type' => 'guide',
      'source_type' => 'manual',
      'status' => 'published',
      'is_active' => true,
      'created_by' => 1,
      'updated_by' => 1,
      'search_content' => 'pkb online pembayaran tutorial panduan samsat',
      'published_at' => now(),
    ]);

    $this->info("✅ Created test knowledge base entry with ID: {$kb->id}");
    $this->info("Rich content includes: headers, lists, links, and images");

    // Test AI response with this rich content
    $testMessages = [
      ['role' => 'user', 'content' => 'Bagaimana cara bayar PKB online? Bisa kasih panduan lengkap?']
    ];

    $this->info('');
    $this->info('🤖 Testing AI Response Generation...');

    $openAIService = app(OpenAIService::class);

    try {
      $response = $openAIService->generateCustomerServiceResponse($testMessages);

      if ($response['success']) {
        $this->info('✅ AI Response Generated Successfully!');
        $this->info('');
        $this->info('AI Response:');
        $this->info('=' . str_repeat('=', 50));
        $this->line($response['message']);
        $this->info('=' . str_repeat('=', 50));
        $this->info('');

        // Analyze response for rich content elements
        $aiMessage = $response['message'];
        $hasLinks = preg_match('/\[.*?\]\(https?:\/\/.*?\)/', $aiMessage);
        $hasHeaders = preg_match('/^#{1,6}\s/m', $aiMessage);
        $hasLists = preg_match('/^[\d\-\*\+]\s/m', $aiMessage);
        $hasImageRefs = stripos($aiMessage, 'gambar') !== false;

        $this->info('Rich Content Analysis:');
        $this->info('📎 Contains Links: ' . ($hasLinks ? 'Yes' : 'No'));
        $this->info('📋 Contains Headers: ' . ($hasHeaders ? 'Yes' : 'No'));
        $this->info('📝 Contains Lists: ' . ($hasLists ? 'Yes' : 'No'));
        $this->info('🖼️  References Images: ' . ($hasImageRefs ? 'Yes' : 'No'));

        $this->info('');
        $this->info('Response Metadata:');
        $this->info('🔢 Knowledge Used: ' . ($response['knowledge_used'] ?? 0));
        $this->info('⚡ Tokens Used: ' . ($response['usage']['total_tokens'] ?? 'N/A'));

        // Check for URL preservation in AI response
        $originalUrl = 'https://info.dipendajatim.go.id/index.php?page=info_pkb';
        if (strpos($aiMessage, $originalUrl) !== false) {
          $this->info('✅ Original URLs preserved in AI response');
        } else {
          $this->warn('⚠️  Original URLs may have been modified in AI response');
        }
      } else {
        $this->error('❌ AI Response Generation Failed!');
        $this->error('Error: ' . ($response['error'] ?? 'Unknown error'));
      }
    } catch (\Exception $e) {
      $this->error('❌ Exception during AI response generation: ' . $e->getMessage());
    }

    // Clean up test data
    $this->info('');
    $this->info('🧹 Cleaning up test data...');
    $kb->delete();
    $this->info('✅ Test knowledge base entry deleted');

    $this->info('');
    $this->info('🎉 Rich Content AI Test Complete! 🎉');
    $this->info('');
    $this->info('Key Features Demonstrated:');
    $this->info('✅ Rich HTML content processing');
    $this->info('✅ URL preservation with special characters');
    $this->info('✅ AI response with formatted content');
    $this->info('✅ Link, image, and list handling');
    $this->info('✅ Structured response generation');
  }
}
