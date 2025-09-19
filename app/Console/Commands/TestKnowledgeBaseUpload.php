<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\KnowledgeBase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class TestKnowledgeBaseUpload extends Command
{
  protected $signature = 'test:kb-upload';
  protected $description = 'Test knowledge base upload functionality without actual file';

  public function handle()
  {
    $this->info('Testing Knowledge Base creation...');

    try {
      // Test creating a knowledge base entry manually (simulating file upload)
      $testData = [
        'title' => 'Test Knowledge Base Entry',
        'question' => 'How to test knowledge base?',
        'answer' => 'This is a test answer for knowledge base functionality.',
        'content' => 'This is test content that would normally come from a PDF file.',
        'category' => 'test',
        'type' => 'faq',
        'source_type' => 'file',
        'file_name' => 'test.pdf',
        'file_type' => 'pdf',
        'mime_type' => 'application/pdf',
        'file_size' => 12345,
        'status' => 'published',
        'is_active' => true,
        'priority' => 2,
        'created_by' => 1,
        'search_content' => 'test knowledge base functionality'
      ];

      $this->info('Creating knowledge base entry...');
      $kb = KnowledgeBase::create($testData);

      $this->info("✅ Knowledge base entry created successfully!");
      $this->info("ID: {$kb->id}");
      $this->info("Title: {$kb->title}");
      $this->info("Mime Type: {$kb->mime_type}");
      $this->info("File Type: {$kb->file_type}");
      $this->info("Status: {$kb->status}");

      // Test updating the entry
      $this->info('Testing update...');
      $kb->update(['answer' => 'Updated test answer']);
      $this->info("✅ Knowledge base entry updated successfully!");

      // Test if it can be found
      $found = KnowledgeBase::where('mime_type', 'application/pdf')->count();
      $this->info("✅ Found {$found} entries with PDF mime type");

      $this->info('🎉 Knowledge base functionality is working correctly!');
    } catch (\Exception $e) {
      $this->error('❌ Error testing knowledge base: ' . $e->getMessage());
      $this->error($e->getTraceAsString());
    }
  }
}
