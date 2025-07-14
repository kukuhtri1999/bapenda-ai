<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PkbScrapingService;

class CleanupPkbSessions extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'pkb:cleanup-sessions';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Clean up expired PKB WebDriver sessions';

  /**
   * Execute the console command.
   */
  public function handle()
  {
    $this->info('Cleaning up expired PKB sessions...');
    PkbScrapingService::cleanupExpiredSessions();
    $this->info('PKB session cleanup completed.');
    return 0;
  }
}
