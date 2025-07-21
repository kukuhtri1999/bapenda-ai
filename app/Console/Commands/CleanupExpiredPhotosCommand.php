<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\CleanupExpiredPhotos;

class CleanupExpiredPhotosCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'photos:cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up expired temporary photos';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting cleanup of expired photos...');

        CleanupExpiredPhotos::dispatch();

        $this->info('Cleanup job dispatched successfully!');

        return Command::SUCCESS;
    }
}
