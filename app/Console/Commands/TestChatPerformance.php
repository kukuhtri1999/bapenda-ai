<?php

namespace App\Console\Commands;

use App\Services\OpenAIService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestChatPerformance extends Command
{
  /**
   * The name and signature of the console command.
   */
  protected $signature = 'test:chat-performance
                            {query? : The test query to run}
                            {--runs=10 : Number of test runs}
                            {--cache : Test cache performance}';

  /**
   * The console command description.
   */
  protected $description = 'Test AI chat performance with various queries';

  /**
   * Execute the console command.
   */
  public function handle()
  {
    $openAIService = app(OpenAIService::class);

    $query = $this->argument('query');
    $runs = (int) $this->option('runs');
    $testCache = $this->option('cache');

    if (!$query) {
      $this->testMultipleQueries($openAIService, $runs);
    } else {
      $this->testSingleQuery($openAIService, $query, $runs, $testCache);
    }
  }

  private function testMultipleQueries(OpenAIService $service, int $runs): void
  {
    $testQueries = [
      'Jadwal samsat keliling malam belok wangi',
      'Biaya perpanjang STNK motor 2024',
      'Alamat kantor samsat lamongan',
      'Cara balik nama kendaraan',
      'Jam buka samsat lamongan',
      'Syarat mutasi motor ke luar daerah',
      'Berapa biaya cek fisik kendaraan',
      'Lokasi samsat keliling hari ini'
    ];

    $this->info("Testing multiple queries with {$runs} runs each...\n");

    $totalResults = [];
    foreach ($testQueries as $index => $query) {
      $this->info("Query " . ($index + 1) . ": {$query}");
      $result = $this->runPerformanceTest($service, $query, $runs);
      $totalResults[] = $result;

      $this->line("  Average: {$result['avg_time']}ms");
      $this->line("  Fastest: {$result['min_time']}ms");
      $this->line("  Slowest: {$result['max_time']}ms");
      $this->line("  Cache hits: {$result['cache_hits']}");
      $this->line("");
    }

    // Overall statistics
    $this->info("OVERALL RESULTS:");
    $overallAvg = array_sum(array_column($totalResults, 'avg_time')) / count($totalResults);
    $overallMin = min(array_column($totalResults, 'min_time'));
    $overallMax = max(array_column($totalResults, 'max_time'));
    $totalCacheHits = array_sum(array_column($totalResults, 'cache_hits'));

    $this->info("Average response time: " . round($overallAvg, 2) . "ms");
    $this->info("Fastest response: {$overallMin}ms");
    $this->info("Slowest response: {$overallMax}ms");
    $this->info("Total cache hits: {$totalCacheHits}");

    if ($overallAvg < 2000) {
      $this->info("✅ Performance target achieved! (Under 2 seconds average)");
    } else {
      $this->warn("⚠️  Performance target not met. Average: " . round($overallAvg, 2) . "ms");
    }
  }

  private function testSingleQuery(OpenAIService $service, string $query, int $runs, bool $testCache): void
  {
    $this->info("Testing query: {$query}");
    $this->info("Runs: {$runs}\n");

    if ($testCache) {
      $this->info("Testing cache performance...\n");

      // First run (cold cache)
      $this->info("Cold cache run:");
      $coldResult = $this->runPerformanceTest($service, $query, 1);
      $this->line("Time: {$coldResult['avg_time']}ms\n");

      // Warm cache runs
      $this->info("Warm cache runs:");
      $warmResult = $this->runPerformanceTest($service, $query, $runs - 1);
      $this->line("Average: {$warmResult['avg_time']}ms");
      $this->line("Cache hits: {$warmResult['cache_hits']}");

      $speedup = $coldResult['avg_time'] / $warmResult['avg_time'];
      $this->info("Cache speedup: " . round($speedup, 2) . "x faster");
    } else {
      $result = $this->runPerformanceTest($service, $query, $runs);

      $this->info("RESULTS:");
      $this->info("Average: {$result['avg_time']}ms");
      $this->info("Min: {$result['min_time']}ms");
      $this->info("Max: {$result['max_time']}ms");
      $this->info("Success rate: {$result['success_rate']}%");
      $this->info("Cache hits: {$result['cache_hits']}");
    }
  }

  private function runPerformanceTest(OpenAIService $service, string $query, int $runs): array
  {
    $times = [];
    $successes = 0;
    $cacheHits = 0;

    $progressBar = $this->output->createProgressBar($runs);
    $progressBar->start();

    for ($i = 0; $i < $runs; $i++) {
      $startTime = microtime(true);

      try {
        $messages = [
          ['role' => 'user', 'content' => $query]
        ];

        $response = $service->generateCustomerServiceResponse($messages);

        $endTime = microtime(true);
        $timeMs = round(($endTime - $startTime) * 1000, 2);
        $times[] = $timeMs;

        if ($response['success'] ?? false) {
          $successes++;
        }

        // Check if response was fast (likely cached)
        if ($timeMs < 100) {
          $cacheHits++;
        }
      } catch (\Exception $e) {
        $this->error("Error in run " . ($i + 1) . ": " . $e->getMessage());
        $times[] = 999999; // Mark as failed
      }

      $progressBar->advance();
    }

    $progressBar->finish();
    $this->line("");

    return [
      'avg_time' => round(array_sum($times) / count($times), 2),
      'min_time' => min($times),
      'max_time' => max($times),
      'success_rate' => round(($successes / $runs) * 100, 2),
      'cache_hits' => $cacheHits,
      'times' => $times
    ];
  }
}
