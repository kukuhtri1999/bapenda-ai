<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\KnowledgeBase;
use App\Services\PineconeService;
use Illuminate\Support\Facades\Log;

class SyncPineconeKnowledgeBase extends Command
{
    protected $signature = 'kb:sync-pinecone {--dry-run : Show what would be synced without making changes} {--force : Skip confirmation prompt}';
    protected $description = 'Clear and rebuild Pinecone vector database from MySQL Knowledge Base';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        $this->info($dryRun ? 'DRY RUN: Analyzing Pinecone rebuild (no changes will be made)' : 'Starting Pinecone Knowledge Base rebuild...');

        try {
            $pineconeService = app(PineconeService::class);

            // Ensure Pinecone index exists
            if (!$pineconeService->createIndex()) {
                $this->error('Failed to create or verify Pinecone index');
                return 1;
            }

            // Get all active KB entries from database
            $dbKnowledgeBases = KnowledgeBase::where('is_active', true)
                ->where('status', 'published')
                ->get(); // Load all fields needed for indexing

            $this->info("Found {$dbKnowledgeBases->count()} active KB entries in database");

            // Get current vector count
            $pineconeStats = $pineconeService->getStats();
            $currentVectors = $pineconeStats['totalVectorCount'] ?? 0;
            $this->info("Current vectors in Pinecone: {$currentVectors}");

            $this->newLine();
            $this->line('=== REBUILD PLAN ===');
            $this->info("🗑️  Clear: Remove ALL vectors from Pinecone");
            $this->info("🔄 Rebuild: Index {$dbKnowledgeBases->count()} KB entries from database");
            $this->info("📊 Result: Fresh, synchronized vector database");

            if ($dryRun) {
                $this->info('DRY RUN completed - no changes made');
                return 0;
            }

            if (!$force && !$this->confirm('This will completely clear and rebuild the vector database. Continue?', false)) {
                $this->info('Operation cancelled');
                return 0;
            }

            // Perform rebuild
            $this->newLine();
            $this->line('=== REBUILDING VECTOR DATABASE ===');

            // Step 1: Clear all vectors
            $this->info('Step 1/2: Clearing all vectors from Pinecone...');
            $startTime = microtime(true);

            if ($currentVectors > 0) {
                $cleared = $this->clearAllVectors($pineconeService);
                $clearTime = round(microtime(true) - $startTime, 2);

                if (!$cleared) {
                    $this->error("Failed to clear vectors after multiple attempts ({$clearTime}s)");
                    $this->info("💡 Tip: Check storage/logs/laravel.log for detailed error information");
                    $this->info("⚠️  This might be a temporary Pinecone API issue. You can try again in a moment.");
                    return 1;
                }
                $this->info("✅ Cleared {$currentVectors} vectors ({$clearTime}s)");
            } else {
                $this->info("✅ No vectors to clear");
            }

            // Step 2: Rebuild from database
            $this->info('Step 2/2: Rebuilding vectors from database...');
            $startTime = microtime(true);
            $indexed = 0;
            $errors = 0;

            $progressBar = $this->output->createProgressBar($dbKnowledgeBases->count());

            foreach ($dbKnowledgeBases as $kb) {
                try {
                    if ($kb->indexToVectorDatabase()) {
                        $indexed++;
                    } else {
                        $errors++;
                        $this->newLine();
                        $this->error("Failed to index KB #{$kb->id}: {$kb->title}");
                    }
                } catch (\Exception $e) {
                    $errors++;
                    $this->newLine();
                    $this->error("Error indexing KB #{$kb->id}: " . $e->getMessage());
                }
                $progressBar->advance();
            }
            $progressBar->finish();
            $this->newLine();

            // Show final stats
            $this->newLine();
            $this->line('=== REBUILD COMPLETED ===');
            $this->info("✅ Successfully indexed: {$indexed} entries");

            if ($errors > 0) {
                $this->error("❌ Errors: {$errors}");
            }

            // Show updated stats
            $updatedStats = $pineconeService->getStats();
            $newTotalVectors = $updatedStats['totalVectorCount'] ?? 0;
            $this->info("📊 Pinecone now contains: {$newTotalVectors} vectors");

            return $errors > 0 ? 1 : 0;
        } catch (\Exception $e) {
            $this->error('Rebuild failed: ' . $e->getMessage());
            Log::error('Pinecone rebuild error: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Clear all vectors from Pinecone index
     */
    private function clearAllVectors(PineconeService $pineconeService): bool
    {
        try {
            // Delete all vectors by using empty filter (deletes everything)
            return $pineconeService->deleteAll();
        } catch (\Exception $e) {
            Log::error('Error clearing all vectors: ' . $e->getMessage());
            return false;
        }
    }
}
