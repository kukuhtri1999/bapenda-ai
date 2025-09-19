<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\KnowledgeBase;

class InspectKb extends Command
{
  protected $signature = 'kb:inspect {id : KB ID}';
  protected $description = 'Inspect KB entry fields';

  public function handle()
  {
    $id = $this->argument('id');
    $kb = KnowledgeBase::find($id);
    if (!$kb) {
      $this->error("KB #{$id} not found");
      return 1;
    }

    $this->info("KB #{$id} inspection:");
    $this->line("Title: " . ($kb->title ?? 'NULL'));
    $this->line("Content: " . substr($kb->content ?? 'NULL', 0, 300) . (strlen($kb->content ?? '') > 300 ? '...' : ''));
    $this->line("Answer: " . substr($kb->answer ?? 'NULL', 0, 300) . (strlen($kb->answer ?? '') > 300 ? '...' : ''));
    $this->line("Excerpt: " . substr($kb->excerpt ?? 'NULL', 0, 300) . (strlen($kb->excerpt ?? '') > 300 ? '...' : ''));
    $this->line("Question: " . substr($kb->question ?? 'NULL', 0, 300) . (strlen($kb->question ?? '') > 300 ? '...' : ''));
    $this->line("Search Content: " . substr($kb->search_content ?? 'NULL', 0, 300) . (strlen($kb->search_content ?? '') > 300 ? '...' : ''));
    $this->line("Category: " . ($kb->category ?? 'NULL'));
    $this->line("Type: " . ($kb->type ?? 'NULL'));
    $this->line("Status: " . ($kb->status ?? 'NULL'));
    $this->line("Is Active: " . ($kb->is_active ? 'true' : 'false'));

    return 0;
  }
}
