<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\KnowledgeBase;

class BackfillKnowledgeBaseSearch extends Command
{
  protected $signature = 'kb:backfill-search
                            {--chunk=100 : Number of records per chunk}
                            {--quiet-save : Skip vector re-indexing (faster, only rebuilds search_content)}';

  protected $description = 'Recompute and backfill search_content for all Knowledge Base entries';

  public function handle(): int
  {
    $chunk      = (int) $this->option('chunk');
    $quietSave  = $this->option('quiet-save');
    $count      = 0;

    $this->info('Rebuilding search_content for all Knowledge Base entries…');
    $bar = $this->output->createProgressBar(KnowledgeBase::withTrashed()->count());
    $bar->start();

    KnowledgeBase::withTrashed()->orderBy('id')->chunk($chunk, function ($rows) use (&$count, $quietSave, $bar) {
      foreach ($rows as $kb) {
        $kb->generateSearchContent();
        if ($quietSave) {
          // saveQuietly() skips all Eloquent events (no Pinecone re-index)
          $kb->saveQuietly();
        } else {
          $kb->save();
        }
        $count++;
        $bar->advance();
      }
    });

    $bar->finish();
    $this->newLine();
    $this->info("✓ Rebuilt search_content for {$count} knowledge base rows.");
    return Command::SUCCESS;
  }
}
