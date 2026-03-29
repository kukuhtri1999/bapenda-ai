<?php

namespace App\Console\Commands;

use Database\Seeders\ChatFeedbackSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SeedReviews extends Command
{
  /**
   * The name and signature of the console command.
   *
   * Usage:
   *   php artisan seed:reviews          — insert all 34 sample reviews
   *   php artisan seed:reviews --fresh  — truncate first, then insert
   */
  protected $signature = 'seed:reviews
                            {--fresh : Truncate the chat_feedback table before seeding}';

  protected $description = 'Seed chat_feedback table with sample SALMA AI reviews';

  public function handle(): int
  {
    if ($this->option('fresh')) {
      if (! $this->confirm('This will DELETE all existing feedback records. Continue?', false)) {
        $this->info('Aborted.');
        return self::SUCCESS;
      }
      DB::table('chat_feedback')->truncate();
      $this->info('Table truncated.');
    }

    $this->call(ChatFeedbackSeeder::class);

    $this->info('✅ Inserted 34 feedback reviews.');
    $this->newLine();
    $this->info('Done! Run <fg=yellow>php artisan seed:reviews --fresh</> to reset and re-seed.');

    return self::SUCCESS;
  }
}
