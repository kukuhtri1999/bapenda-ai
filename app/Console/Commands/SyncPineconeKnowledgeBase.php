<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\KnowledgeBase;
use App\Services\PineconeService;
use Illuminate\Support\Facades\Log;

class SyncPineconeKnowledgeBase extends Command
{
  protected $signature = 'kb:sync-pinecone {--dry-run : Show what would be synced without making changes}';
  protected $description = 'Sync Knowledge Base with Pinecone vector database - remove orphans and add missing entries';

  public function handle()
  {
    $dryRun = $this->option('dry-run');

    $this->info($dryRun ? 'DRY RUN: Analyzing Pinecone sync (no changes will be made)' : 'Starting Pinecone Knowledge Base sync...');

    try {
      $pineconeService = app(PineconeService::class);

      // Ensure Pinecone index exists
      if (!$pineconeService->createIndex()) {
        $this->error('Failed to create or verify Pinecone index');
        return 1;
      }

      // Get all KB entries from database (active and published only)
      $dbKnowledgeBases = KnowledgeBase::where('is_active', true)
        ->where('status', 'published')
        ->get(['id', 'title', 'updated_at']);

      $this->info("Found {$dbKnowledgeBases->count()} active KB entries in database");

      // Get all vectors from Pinecone
      $pineconeStats = $pineconeService->getStats();
      $totalVectors = $pineconeStats['totalVectorCount'] ?? 0;
      $this->info("Found {$totalVectors} vectors in Pinecone");

      // Create sets for comparison
      $dbKbIds = $dbKnowledgeBases->pluck('id')->toArray();
      $pineconeKbIds = $this->getPineconeKbIds($pineconeService);

      // Find orphaned vectors (in Pinecone but not in DB)
      $orphanedIds = array_diff($pineconeKbIds, $dbKbIds);

      // Find missing vectors (in DB but not in Pinecone)
      $missingIds = array_diff($dbKbIds, $pineconeKbIds);

      $this->newLine();
      $this->line('=== SYNC ANALYSIS ===');
      $this->info("Orphaned vectors (will be removed): " . count($orphanedIds));
      $this->info("Missing vectors (will be added): " . count($missingIds));

      if (count($orphanedIds) > 0) {
        $this->warn("Orphaned KB IDs: " . implode(', ', $orphanedIds));
      }

      if (count($missingIds) > 0) {
        $this->warn("Missing KB IDs: " . implode(', ', $missingIds));
      }

      if (count($orphanedIds) === 0 && count($missingIds) === 0) {
        $this->info('✅ Pinecone is already in sync with database!');
        return 0;
      }

      if ($dryRun) {
        $this->info('DRY RUN completed - no changes made');
        return 0;
      }

      // Perform actual sync
      $this->newLine();
      $this->line('=== PERFORMING SYNC ===');

      $removed = 0;
      $added = 0;
      $errors = 0;

      // Remove orphaned vectors
      if (count($orphanedIds) > 0) {
        $this->info('Removing orphaned vectors...');
        $progressBar = $this->output->createProgressBar(count($orphanedIds));

        foreach ($orphanedIds as $kbId) {
          try {
            if ($pineconeService->deleteByFilter(['kb_id' => ['$eq' => $kbId]])) {
              $removed++;
            } else {
              $errors++;
              $this->newLine();
              $this->error("Failed to remove orphaned KB #{$kbId}");
            }
          } catch (\Exception $e) {
            $errors++;
            $this->newLine();
            $this->error("Error removing KB #{$kbId}: " . $e->getMessage());
          }
          $progressBar->advance();
        }
        $progressBar->finish();
        $this->newLine();
      }

      // Add missing vectors
      if (count($missingIds) > 0) {
        $this->info('Adding missing vectors...');
        $progressBar = $this->output->createProgressBar(count($missingIds));

        foreach ($missingIds as $kbId) {
          try {
            $kb = KnowledgeBase::find($kbId);
            if ($kb && $kb->indexToVectorDatabase()) {
              $added++;
            } else {
              $errors++;
              $this->newLine();
              $this->error("Failed to add missing KB #{$kbId}");
            }
          } catch (\Exception $e) {
            $errors++;
            $this->newLine();
            $this->error("Error adding KB #{$kbId}: " . $e->getMessage());
          }
          $progressBar->advance();
        }
        $progressBar->finish();
        $this->newLine();
      }

      // Show final stats
      $this->newLine();
      $this->line('=== SYNC COMPLETED ===');
      $this->info("✅ Removed: {$removed} orphaned vectors");
      $this->info("✅ Added: {$added} missing vectors");

      if ($errors > 0) {
        $this->error("❌ Errors: {$errors}");
      }

      // Show updated stats
      $updatedStats = $pineconeService->getStats();
      $newTotalVectors = $updatedStats['totalVectorCount'] ?? 0;
      $this->info("📊 Pinecone now contains: {$newTotalVectors} vectors");

      return $errors > 0 ? 1 : 0;
    } catch (\Exception $e) {
      $this->error('Sync failed: ' . $e->getMessage());
      Log::error('Pinecone sync error: ' . $e->getMessage());
      return 1;
    }
  }

  /**
   * Get all KB IDs that exist in Pinecone
   */
  private function getPineconeKbIds(PineconeService $pineconeService): array
  {
    try {
      // Since Pinecone doesn't have a direct way to list all vectors,
      // we'll use a broad query to get them
      $allVectors = $pineconeService->query(
        vector: array_fill(0, 1536, 0), // Zero vector to get all results
        topK: 10000, // High number to get all vectors
        filter: [] // No filter to get everything
      );

      $kbIds = [];
      foreach ($allVectors as $vector) {
        $kbId = $vector['metadata']['kb_id'] ?? null;
        if ($kbId && !in_array($kbId, $kbIds)) {
          $kbIds[] = $kbId;
        }
      }

      return $kbIds;
    } catch (\Exception $e) {
      Log::error('Error getting Pinecone KB IDs: ' . $e->getMessage());
      return [];
    }
  }
}
