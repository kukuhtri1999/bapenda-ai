<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\RunChatAnalytics;
use App\Models\AnalysisReport;
use Carbon\Carbon;

class DebugRunAnalytics extends Command
{
  protected $signature = 'debug:run-analytics {--sample} {--start=} {--end=}';
  protected $description = 'Run RunChatAnalytics job synchronously for quick smoke test';

  public function handle()
  {
    $startOpt = $this->option('start');
    $endOpt = $this->option('end');
    if ($startOpt && $endOpt) {
      $start = Carbon::parse($startOpt)->startOfDay();
      $end = Carbon::parse($endOpt)->endOfDay();
    } else {
      $start = Carbon::now()->subDay()->startOfDay();
      $end = Carbon::now()->endOfDay();
    }

    $report = AnalysisReport::create([
      'start_date' => $start->toDateString(),
      'end_date' => $end->toDateString(),
      'status' => 'pending',
      'notes' => ['debug' => true, 'sample' => (bool)$this->option('sample')],
    ]);

    $this->info('Created report id=' . $report->id);

    // run job synchronously
    $job = new RunChatAnalytics($report);
    $job->handle(app(\App\Services\OpenAIService::class));

    $report->refresh();
    $this->info('Job completed, status=' . $report->status);
    $this->line('Summary keys: ' . implode(', ', array_keys(is_array($report->summary_json) ? $report->summary_json : [])));

    return 0;
  }
}
