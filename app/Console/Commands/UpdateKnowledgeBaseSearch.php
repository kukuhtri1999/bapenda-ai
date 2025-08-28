<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\KnowledgeBase;

class UpdateKnowledgeBaseSearch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kb:update-search';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update search content for Knowledge Base entries';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Updating search content for Knowledge Base entries...');

        $entries = KnowledgeBase::all();
        $count = 0;

        foreach ($entries as $kb) {
            $searchContent = strip_tags($kb->title . ' ' . $kb->content . ' ' . $kb->excerpt);
            $kb->update(['search_content' => $searchContent]);
            $count++;
            $this->line("Updated entry: {$kb->title}");
        }

        $this->info("Successfully updated {$count} entries.");
        return 0;
    }
}
