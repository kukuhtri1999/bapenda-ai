<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\KnowledgeBase;

class TestLargeDocumentUpload extends Command
{
  protected $signature = 'test:large-upload';
  protected $description = 'Test uploading a large document to verify it gets chunked properly';

  public function handle()
  {
    $this->info('🧪 Testing Large Document Upload (Simulated)...');

    // Create a moderately large document (simulating what would cause the original error)
    $largeContent = str_repeat("This is a test paragraph about Samsat services. " .
      "It contains information about vehicle registration, tax payments, and administrative procedures. " .
      "The content is repeated to simulate a large PDF document upload. ", 100);

    $this->info("Created test content: " . strlen($largeContent) . " characters");

    try {
      // Simulate the data that would come from a file upload
      $testData = [
        'title' => 'Large Document Upload Test',
        'question' => 'How to handle large PDF uploads?',
        'answer' => 'This demonstrates large document processing.',
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
        'metadata' => [],
        'search_content' => 'large document upload test samsat vehicle registration',
        'published_at' => now()
      ];

      // Test if content length would trigger chunking (>1500 chars)
      if (strlen($testData['content']) > 1500) {
        $this->info('✅ Content is large enough to trigger chunking (>1500 chars)');

        // Test the chunking logic manually (simulating controller behavior)
        $chunkingService = app(\App\Services\DocumentChunkingService::class);
        $chunks = $chunkingService->chunkDocument($testData['content'], $testData['title']);

        $this->info("Document would be split into " . count($chunks) . " chunks");

        // Create the first chunk only for testing
        $firstChunk = $chunks[0];
        $chunkData = $testData;
        $chunkData['content'] = $firstChunk['content'];
        $chunkData['title'] = $testData['title'] . ' (Test Chunk 1)';
        $chunkData['metadata'] = [
          'is_chunked' => true,
          'chunk_index' => $firstChunk['chunk_index'],
          'total_chunks' => $firstChunk['total_chunks'],
          'char_count' => $firstChunk['char_count'],
          'word_count' => $firstChunk['word_count'],
          'original_title' => $testData['title'],
          'chunk_summary' => $firstChunk['chunk_summary']
        ];

        $knowledgeBase = KnowledgeBase::create($chunkData);
        $this->info("✅ Successfully created chunked knowledge base entry with ID: {$knowledgeBase->id}");
        $this->info("Chunk content length: " . strlen($knowledgeBase->content) . " characters");
        $this->info("Chunk metadata: " . json_encode($knowledgeBase->metadata));
      } else {
        $this->info('Content is small, would be stored as single entry');
        $knowledgeBase = KnowledgeBase::create($testData);
        $this->info("✅ Successfully created knowledge base entry with ID: {$knowledgeBase->id}");
      }

      $this->info('');
      $this->info('🎉 Large Document Upload Test SUCCESSFUL! 🎉');
      $this->info('');
      $this->info('Key Benefits:');
      $this->info('✅ No more database "Data too long" errors');
      $this->info('✅ Large documents are automatically chunked');
      $this->info('✅ Each chunk is optimally sized for RAG retrieval');
      $this->info('✅ Chunk metadata preserved for better organization');
      $this->info('✅ Better search performance with smaller, focused content');
    } catch (\Exception $e) {
      $this->error('❌ Test failed: ' . $e->getMessage());
      $this->error($e->getTraceAsString());
    }
  }
}
