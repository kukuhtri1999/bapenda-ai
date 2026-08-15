<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RagEvalTest;
use App\Models\RagEvalRun;
use App\Services\RagEvaluationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Log;

class RagEvaluationController extends Controller
{
    protected RagEvaluationService $evalService;

    public function __construct(RagEvaluationService $evalService)
    {
        $this->evalService = $evalService;
    }

    /**
     * Display the RAG Evaluation Suite dashboard.
     */
    public function index(): Response
    {
        $this->evalService->seedDefaultGoldenTestsIfEmpty();

        $latestRun = RagEvalRun::orderBy('id', 'desc')->first();
        $historyRuns = RagEvalRun::orderBy('id', 'desc')->paginate(10);
        $tests = RagEvalTest::orderBy('id', 'asc')->get();

        // Calculate metrics
        $metrics = [
            'total_tests' => RagEvalTest::count(),
            'active_tests' => RagEvalTest::active()->count(),
            'latest_overall_score' => $latestRun ? round($latestRun->overall_score * 100, 1) : 0,
            'latest_faithfulness' => $latestRun ? round($latestRun->avg_faithfulness_score * 100, 1) : 0,
            'latest_answer_relevance' => $latestRun ? round($latestRun->avg_answer_relevance_score * 100, 1) : 0,
            'latest_context_relevance' => $latestRun ? round($latestRun->avg_context_relevance_score * 100, 1) : 0,
            'latest_avg_latency' => $latestRun ? $latestRun->avg_latency_seconds : 0,
            'total_runs_count' => RagEvalRun::count(),
            'model_name' => config('services.openai.model', 'gpt-5.6-luna'),
        ];

        return Inertia::render('Admin/RagEvaluation/Index', [
            'latestRun' => $latestRun,
            'historyRuns' => $historyRuns,
            'tests' => $tests,
            'metrics' => $metrics,
        ]);
    }

    /**
     * Trigger a new automated evaluation benchmark run.
     */
    public function runBenchmark(Request $request): JsonResponse
    {
        try {
            $limit = $request->get('limit') ? (int)$request->get('limit') : null;
            $run = $this->evalService->runEvaluation($limit);

            return response()->json([
                'success' => true,
                'run' => $run,
                'message' => "Benchmark evaluasi berhasil dijalankan! RAG Score: " . ($run->overall_score * 100) . "%"
            ]);
        } catch (\Throwable $e) {
            Log::error('RagEvaluationController::runBenchmark error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menjalankan evaluasi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add a new golden test inquiry to the dataset.
     */
    public function storeTest(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'query' => 'required|string|min:5',
            'language' => 'required|in:id,jv',
            'expected_topic' => 'nullable|string|max:100',
            'ground_truth' => 'nullable|string',
            'tags' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $test = RagEvalTest::create($validated);

        return response()->json([
            'success' => true,
            'test' => $test,
            'message' => 'Test case berhasil ditambahkan ke dataset evaluasi.'
        ]);
    }

    /**
     * Update an existing golden test case.
     */
    public function updateTest(RagEvalTest $ragEvalTest, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'query' => 'required|string|min:5',
            'language' => 'required|in:id,jv',
            'expected_topic' => 'nullable|string|max:100',
            'ground_truth' => 'nullable|string',
            'tags' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $ragEvalTest->update($validated);

        return response()->json([
            'success' => true,
            'test' => $ragEvalTest,
            'message' => 'Test case berhasil diperbarui.'
        ]);
    }

    /**
     * Delete a test case from the dataset.
     */
    public function destroyTest(RagEvalTest $ragEvalTest): JsonResponse
    {
        $ragEvalTest->delete();

        return response()->json([
            'success' => true,
            'message' => 'Test case berhasil dihapus.'
        ]);
    }
}
