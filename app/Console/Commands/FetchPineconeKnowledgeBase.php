<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PineconeFetchService;

class FetchPineconeKnowledgeBase extends Command
{
    protected $signature = 'kb:fetch-pinecone
                            {--dry-run : Analyze vectors without modifying the database}
                            {--force : Skip confirmation prompt}';

    protected $description = 'Fetch and synchronize all Knowledge Base records from Pinecone Vector DB into MySQL database';

    public function handle(PineconeFetchService $fetchService): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');

        $this->info('================================================================');
        $this->info('   FETCH & SYNC KNOWLEDGE BASE FROM PINECONE VECTOR DATABASE    ');
        $this->info('================================================================');

        if (!$dryRun && !$force) {
            if (!$this->confirm('This will insert/update Knowledge Base records in your local MySQL database. Continue?', true)) {
                $this->warn('Operation cancelled by user.');
                return Command::SUCCESS;
            }
        }

        $progressBar = null;

        $result = $fetchService->fetchAndSyncToDatabase(
            dryRun: $dryRun,
            onProgress: function (string $stage, int $percent, string $message) use (&$progressBar) {
                $this->line("<fg=cyan>[{$percent}%]</> {$message}");
            }
        );

        if (!$result['success']) {
            $this->error('Error: ' . $result['message']);
            return Command::FAILURE;
        }

        $stats = $result['stats'] ?? [];
        $this->newLine();
        $this->info('================================================================');
        $this->info('                      SYNC SUMMARY RESULTS                      ');
        $this->info('================================================================');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Vectors in Pinecone', $stats['total_vectors'] ?? 0],
                ['Total Knowledge Base Articles', $stats['total_docs'] ?? 0],
                ['New Articles Inserted', $stats['db_created'] ?? 0],
                ['Existing Articles Updated', $stats['db_updated'] ?? 0],
            ]
        );

        if (!empty($stats['categories'])) {
            $this->newLine();
            $this->info('Category Distribution:');
            $catRows = [];
            foreach ($stats['categories'] as $cat => $cnt) {
                $catRows[] = [$cat, $cnt];
            }
            $this->table(['Category', 'Total Documents'], $catRows);
        }

        $this->info('🎉 ' . $result['message']);
        return Command::SUCCESS;
    }
}
