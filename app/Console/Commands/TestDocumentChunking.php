<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\DocumentChunkingService;
use App\Models\KnowledgeBase;

class TestDocumentChunking extends Command
{
  protected $signature = 'test:document-chunking';
  protected $description = 'Test document chunking functionality with a large text';

  public function handle()
  {
    $this->info('🧪 Testing Document Chunking Service...');

    // Create a large test document (simulating 100+ page content)
    $largeContent = $this->generateLargeContent();

    $this->info("Generated test content: " . strlen($largeContent) . " characters");

    // Test the chunking service
    $chunkingService = app(DocumentChunkingService::class);

    $chunks = $chunkingService->chunkDocument($largeContent, 'Test Large Document');

    $this->info("✅ Document chunked into " . count($chunks) . " pieces");

    // Display chunk information
    foreach ($chunks as $i => $chunk) {
      $this->info("Chunk {$i}: {$chunk['char_count']} chars, {$chunk['word_count']} words");
      $this->info("Summary: {$chunk['chunk_summary']}");
      $this->info("---");
    }

    // Test saving chunks to database
    $this->info('Testing database storage with chunking...');

    try {
      $testData = [
        'title' => 'Large Document Chunking Test',
        'question' => 'How to handle large documents?',
        'answer' => 'This document demonstrates chunking.',
        'content' => $largeContent,
        'category' => 'test',
        'type' => 'guide',
        'source_type' => 'file',
        'file_name' => 'large_document.pdf',
        'file_type' => 'pdf',
        'mime_type' => 'application/pdf',
        'file_size' => strlen($largeContent),
        'status' => 'published',
        'is_active' => true,
        'priority' => 2,
        'created_by' => 1,
        'search_content' => 'large document chunking test processing pdf upload'
      ];

      $createdEntries = [];
      $isMainEntry = true;

      foreach ($chunks as $chunk) {
        $chunkData = $testData;
        $chunkData['content'] = $chunk['content'];

        if ($chunk['total_chunks'] > 1) {
          if ($isMainEntry) {
            $chunkData['title'] = $testData['title'];
          } else {
            $chunkData['title'] = $testData['title'] . " - Part " . ($chunk['chunk_index'] + 1);
          }
        }

        $chunkData['metadata'] = [
          'is_chunked' => true,
          'chunk_index' => $chunk['chunk_index'],
          'total_chunks' => $chunk['total_chunks'],
          'char_count' => $chunk['char_count'],
          'word_count' => $chunk['word_count'],
          'original_title' => $testData['title'],
          'chunk_summary' => $chunk['chunk_summary']
        ];

        $chunkData['published_at'] = now();

        $knowledgeBase = KnowledgeBase::create($chunkData);
        $createdEntries[] = $knowledgeBase;

        $isMainEntry = false;
      }

      $this->info("✅ Successfully created " . count($createdEntries) . " chunked entries in database");

      // Test indexing to Pinecone
      $this->info('Testing Pinecone indexing for chunked documents...');
      $this->call('kb:index-vector');

      $this->info('🎉 Document chunking test completed successfully!');
    } catch (\Exception $e) {
      $this->error('❌ Error testing chunking: ' . $e->getMessage());
      $this->error($e->getTraceAsString());
    }
  }

  private function generateLargeContent(): string
  {
    $paragraphs = [
      "Bapenda Samsat Lamongan adalah instansi pemerintah yang bertanggung jawab atas pengelolaan pajak kendaraan bermotor dan administrasi kendaraan bermotor di Kabupaten Lamongan. Sebagai bagian dari Badan Pendapatan Daerah, Samsat Lamongan berkomitmen untuk memberikan pelayanan terbaik kepada masyarakat dalam hal perpanjangan STNK, pembayaran pajak kendaraan bermotor, dan berbagai layanan administrasi kendaraan lainnya.",

      "Layanan Samsat Keliling merupakan inovasi pelayanan yang dikembangkan untuk mempermudah masyarakat yang memiliki keterbatasan waktu atau jarak untuk datang langsung ke kantor Samsat. Layanan ini hadir di berbagai lokasi strategis dengan jadwal yang telah ditentukan, sehingga masyarakat dapat memanfaatkan layanan perpanjangan STNK dan pembayaran pajak kendaraan bermotor tanpa harus datang ke kantor pusat.",

      "Proses perpanjangan STNK melalui Samsat Keliling sama dengan prosedur di kantor pusat. Masyarakat cukup membawa dokumen yang diperlukan seperti STNK asli, KTP, dan uang untuk pembayaran pajak. Petugas Samsat Keliling akan membantu proses verifikasi dokumen, penghitungan pajak, pembayaran, hingga penerbitan STNK baru. Semua proses dapat diselesaikan dalam waktu yang relatif singkat.",

      "Untuk informasi jadwal dan lokasi Samsat Keliling, masyarakat dapat mengakses website resmi Bapenda Jawa Timur atau mengikuti pengumuman melalui media sosial resmi. Jadwal Samsat Keliling disusun secara rutin dengan mempertimbangkan kebutuhan masyarakat di berbagai wilayah. Biasanya setiap kecamatan mendapat giliran kunjungan Samsat Keliling sesuai dengan jadwal yang telah ditetapkan.",

      "Selain layanan Samsat Keliling, masyarakat juga dapat memanfaatkan layanan online melalui aplikasi Samsat Digital atau e-Samsat. Layanan digital ini memungkinkan masyarakat untuk melakukan pembayaran pajak kendaraan bermotor secara online, cek tagihan pajak, dan berbagai layanan administratif lainnya. Penggunaan teknologi digital ini merupakan upaya modernisasi pelayanan untuk meningkatkan kemudahan akses bagi masyarakat.",

      "Persyaratan umum untuk perpanjangan STNK meliputi STNK asli yang masih berlaku, KTP pemilik kendaraan yang sesuai dengan data di STNK, dan dana untuk pembayaran pajak kendaraan bermotor. Untuk kendaraan yang telah jatuh tempo lebih dari satu tahun, diperlukan dokumen tambahan seperti surat keterangan fisik kendaraan dari kepolisian. Masyarakat disarankan untuk mempersiapkan semua dokumen dengan baik sebelum datang ke lokasi pelayanan.",

      "Tarif pajak kendaraan bermotor ditetapkan berdasarkan jenis kendaraan, tahun pembuatan, dan nilai jual kendaraan bermotor. Untuk kendaraan roda dua, tarif pajak umumnya berkisar antara Rp 75.000 hingga Rp 500.000 per tahun tergantung kapasitas mesin dan tahun pembuatan. Sedangkan untuk kendaraan roda empat, tarif pajak bervariasi mulai dari Rp 200.000 hingga jutaan rupiah tergantung nilai jual dan spesifikasi kendaraan.",

      "Denda keterlambatan dikenakan bagi kendaraan yang terlambat membayar pajak. Besaran denda dihitung berdasarkan persentase dari pokok pajak kendaraan bermotor dengan ketentuan sebagai berikut: keterlambatan 1-12 bulan dikenakan denda 25% dari pokok pajak, keterlambatan 13-24 bulan dikenakan denda 50% dari pokok pajak, dan keterlambatan di atas 24 bulan dikenakan denda 75% dari pokok pajak. Oleh karena itu, masyarakat disarankan untuk selalu membayar pajak kendaraan tepat waktu.",
    ];

    // Repeat paragraphs to create a very large document
    $content = '';
    for ($i = 0; $i < 50; $i++) { // This will create a document with 400 paragraphs
      foreach ($paragraphs as $paragraph) {
        $content .= $paragraph . "\n\n";
      }
    }

    return $content;
  }
}
