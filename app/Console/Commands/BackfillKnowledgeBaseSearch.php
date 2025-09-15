<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\KnowledgeBase;

class BackfillKnowledgeBaseSearch extends Command
{
  protected $signature = 'kb:backfill-search {--chunk=200 : Number of records per chunk}';
  protected $description = 'Recompute and backfill search_content for all Knowledge Base entries';

  public function handle(): int
  {
    $chunk = (int) $this->option('chunk');
    $count = 0;
    KnowledgeBase::withTrashed()->orderBy('id')->chunk($chunk, function ($rows) use (&$count) {
      foreach ($rows as $kb) {
        $kb->generateSearchContent();
        $kb->save();
        $count++;
      }
    });
    $this->info("Backfilled search_content for {$count} knowledge base rows.");
    return Command::SUCCESS;
  }
}
