<?php

namespace App\Console\Commands;

use Database\Seeders\WajibPajakSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SeedWajibPajak extends Command
{
  /**
   * Usage:
   *   php artisan seed:wajib-pajak          – insert 60 sample records
   *   php artisan seed:wajib-pajak --fresh  – truncate first, then insert
   */
  protected $signature = 'seed:wajib-pajak
                            {--fresh : Truncate the wajib_pajak table before seeding}';

  protected $description = 'Seed wajib_pajak table with 60 sample Lamongan taxpayer records';

  public function handle(): int
  {
    if ($this->option('fresh')) {
      if (! $this->confirm('This will DELETE all existing wajib pajak records (including soft-deleted). Continue?', false)) {
        $this->info('Aborted.');
        return self::SUCCESS;
      }
      DB::table('wajib_pajak')->truncate();
      $this->info('Table truncated.');
    }

    $before = DB::table('wajib_pajak')->count();
    $this->call(WajibPajakSeeder::class);
    $after = DB::table('wajib_pajak')->count();

    $this->info("✅ Inserted " . ($after - $before) . " wajib pajak records. (Total: {$after})");
    $this->newLine();
    $this->info('Tip: use <fg=yellow>--fresh</> to wipe and re-seed.');

    return self::SUCCESS;
  }
}
