<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\RagEvaluationService;

class RunRagEvaluation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rag:evaluate {--limit= : Limit the number of tests to evaluate}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run automated RAG Triad evaluation benchmarks (Faithfulness, Relevance, Context Quality)';

    /**
     * Execute the console command.
     */
    public function handle(RagEvaluationService $evalService): int
    {
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;

        $this->info("================================================================================");
        $this->info("             SALMA AI - AUTOMATED RAG TRIAD EVALUATION BENCHMARK               ");
        $this->info("================================================================================");
        $this->line("Model: <fg=cyan>" . config('services.openai.model', 'gpt-5-mini') . "</>");
        if ($limit) {
            $this->line("Test Limit: <fg=yellow>{$limit}</> tests");
        }
        $this->newLine();

        $this->line("Starting evaluation runner...");
        $bar = null;

        $run = $evalService->runEvaluation($limit, function ($current, $total, $item) {
            $q = mb_substr($item['query'], 0, 45) . '...';
            $score = number_format($item['overall_score'] * 100, 0);
            $lat = $item['latency_seconds'] . 's';
            $this->line("[{$current}/{$total}] <fg=yellow>Score: {$score}%</> ({$lat}) -> \"{$q}\"");
        });

        $this->newLine();
        $this->info("================================================================================");
        $this->info("                         BENCHMARK RESULTS SUMMARY                              ");
        $this->info("================================================================================");
        $this->table(
            ['Metric', 'Score (0-1.00)', 'Percentage', 'Rating'],
            [
                ['Faithfulness / Groundedness', $run->avg_faithfulness_score, ($run->avg_faithfulness_score * 100) . '%', $this->getRating($run->avg_faithfulness_score)],
                ['Answer Relevance', $run->avg_answer_relevance_score, ($run->avg_answer_relevance_score * 100) . '%', $this->getRating($run->avg_answer_relevance_score)],
                ['Context Relevance', $run->avg_context_relevance_score, ($run->avg_context_relevance_score * 100) . '%', $this->getRating($run->avg_context_relevance_score)],
                ['Overall RAG Score', $run->overall_score, ($run->overall_score * 100) . '%', $this->getRating($run->overall_score)],
                ['Average Latency', $run->avg_latency_seconds . 's', '-', '-'],
            ]
        );

        $this->info("Run ID #{$run->id} successfully saved to database.");
        return Command::SUCCESS;
    }

    private function getRating(float $score): string
    {
        if ($score >= 0.90) return 'EXCELLENT (Sangat Baik)';
        if ($score >= 0.80) return 'GOOD (Baik)';
        if ($score >= 0.70) return 'ACCEPTABLE (Cukup)';
        return 'NEEDS IMPROVEMENT';
    }
}
