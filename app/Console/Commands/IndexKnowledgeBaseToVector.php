<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\KnowledgeBase;
use App\Services\PineconeService;
use Illuminate\Support\Facades\Log;

class IndexKnowledgeBaseToVector extends Command
{
  /**
   * The name and signature of the console command.
   */
  protected $signature = 'kb:index-vector {--force : Force re-indexing of all entries} {--id= : Index specific KB entry by ID}';

  /**
   * The console command description.
   */
  protected $description = 'Index Knowledge Base entries to Pinecone vector database';

  /**
   * Execute the console command.
   */
  public function handle()
  {
    $this->info('Starting Knowledge Base vector indexing...');

    // Initialize Pinecone service and create index if needed
    $pineconeService = app(PineconeService::class);

    $this->info('Ensuring Pinecone index exists...');
    if (!$pineconeService->createIndex()) {
      $this->error('Failed to create or verify Pinecone index');
      return 1;
    }

    $force = $this->option('force');
    $specificId = $this->option('id');

    if ($specificId) {
      return $this->indexSpecificEntry($specificId);
    }

    if ($force) {
      $this->warn('Force mode: This will re-index ALL entries and may take a while...');
      if (!$this->confirm('Are you sure you want to continue?')) {
        return 0;
      }
    }

    $query = KnowledgeBase::active()->published();

    if (!$force) {
      $this->info('Indexing only active and published entries...');
    }

    $totalEntries = $query->count();
    $this->info("Found {$totalEntries} entries to process");

    if ($totalEntries === 0) {
      $this->info('No entries to index');
      return 0;
    }

    $progressBar = $this->output->createProgressBar($totalEntries);
    $progressBar->start();

    $success = 0;
    $errors = 0;

    $query->chunk(50, function ($knowledgeBases) use (&$success, &$errors, $progressBar, $force) {
      foreach ($knowledgeBases as $kb) {
        try {
          if ($force) {
            // Remove existing vectors first in force mode
            $kb->removeFromVectorDatabase();
          }

          if ($kb->indexToVectorDatabase()) {
            $success++;
          } else {
            $errors++;
            $this->newLine();
            $this->error("Failed to index KB #{$kb->id}: {$kb->title}");
          }
        } catch (\Exception $e) {
          $errors++;
          $this->newLine();
          $this->error("Error indexing KB #{$kb->id}: " . $e->getMessage());
          Log::error("KB indexing error for #{$kb->id}: " . $e->getMessage());
        }

        $progressBar->advance();
      }
    });

    $progressBar->finish();
    $this->newLine(2);

    $this->info("Indexing completed!");
    $this->info("Successfully indexed: {$success} entries");

    if ($errors > 0) {
      $this->error("Failed to index: {$errors} entries");
    }

    // Show index statistics
    $this->showIndexStats($pineconeService);

    return $errors > 0 ? 1 : 0;
  }

  /**
   * Index a specific KB entry
   */
  private function indexSpecificEntry(int $id): int
  {
    $kb = KnowledgeBase::find($id);

    if (!$kb) {
      $this->error("Knowledge Base entry with ID {$id} not found");
      return 1;
    }

    $this->info("Indexing KB #{$kb->id}: {$kb->title}");

    try {
      // Remove existing vectors first
      $kb->removeFromVectorDatabase();

      if ($kb->indexToVectorDatabase()) {
        $this->info('Successfully indexed to vector database');
        return 0;
      } else {
        $this->error('Failed to index to vector database');
        return 1;
      }
    } catch (\Exception $e) {
      $this->error('Error: ' . $e->getMessage());
      return 1;
    }
  }

  /**
   * Show index statistics
   */
  private function showIndexStats(PineconeService $pineconeService): void
  {
    try {
      $stats = $pineconeService->getStats();

      if (!empty($stats)) {
        $this->newLine();
        $this->info('Pinecone Index Statistics:');
        $this->table(
          ['Metric', 'Value'],
          [
            ['Total Vectors', $stats['totalVectorCount'] ?? 'N/A'],
            ['Dimension', $stats['dimension'] ?? 'N/A'],
            ['Index Fullness', ($stats['indexFullness'] ?? 0) * 100 . '%'],
          ]
        );
      }
    } catch (\Exception $e) {
      $this->warn('Could not retrieve index statistics: ' . $e->getMessage());
    }
  }
}
