<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KnowledgeGap;
use App\Models\KnowledgeBase;
use App\Services\OpenAIService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Log;

class KnowledgeGapController extends Controller
{
    protected OpenAIService $openAIService;

    public function __construct(OpenAIService $openAIService)
    {
        $this->openAIService = $openAIService;
    }

    /**
     * Display a listing of knowledge gaps.
     */
    public function index(Request $request): Response
    {
        $query = KnowledgeGap::query()
            ->with(['draftKnowledgeBase:id,title,category'])
            ->orderBy('frequency', 'desc')
            ->orderBy('last_seen_at', 'desc');

        // Status filter
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        } else {
            // Default to showing pending first
            if (!$request->filled('status')) {
                $query->where('status', 'pending');
            }
        }

        // Search query
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('query', 'like', "%{$search}%")
                  ->orWhere('session_id', 'like', "%{$search}%");
            });
        }

        // Source filter
        if ($request->filled('source')) {
            $query->where('source', $request->source);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'frequency');
        $sortOrder = $request->get('sort_order', 'desc');
        if (in_array($sortBy, ['frequency', 'similarity_score', 'last_seen_at', 'created_at'])) {
            $query->orderBy($sortBy, $sortOrder === 'asc' ? 'asc' : 'desc');
        }

        $gaps = $query->paginate(15)->withQueryString();

        // Calculate statistics
        $stats = [
            'total_gaps' => KnowledgeGap::count(),
            'pending_gaps' => KnowledgeGap::where('status', 'pending')->count(),
            'resolved_gaps' => KnowledgeGap::where('status', 'resolved')->count(),
            'dismissed_gaps' => KnowledgeGap::where('status', 'dismissed')->count(),
            'top_gap' => KnowledgeGap::orderBy('frequency', 'desc')->first()?->query ?? 'Belum ada data',
        ];

        return Inertia::render('Admin/KnowledgeGaps/Index', [
            'gaps' => $gaps,
            'stats' => $stats,
            'filters' => $request->only(['search', 'status', 'source', 'sort_by', 'sort_order']),
            'categories' => KnowledgeBase::getCategories(),
            'types' => KnowledgeBase::getTypes(),
        ]);
    }

    /**
     * Generate an AI draft for a specific knowledge gap.
     */
    public function generateDraft(KnowledgeGap $knowledgeGap): JsonResponse
    {
        try {
            $context = "Ditemukan dari pencarian dengan kemiripan rendah: " . round($knowledgeGap->similarity_score ?? 0, 2) . 
                       ", ditanyakan sebanyak {$knowledgeGap->frequency} kali.";

            $draft = $this->openAIService->generateKnowledgeBaseDraft($knowledgeGap->query, $context);

            if (!$draft['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $draft['error'] ?? 'Gagal membuat draf dengan AI.'
                ], 422);
            }

            // Save suggested draft to gap model
            $knowledgeGap->update([
                'suggested_draft' => $draft['data']
            ]);

            return response()->json([
                'success' => true,
                'draft' => $draft['data'],
                'gap' => $knowledgeGap
            ]);
        } catch (\Throwable $e) {
            Log::error('KnowledgeGapController::generateDraft error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Dismiss a knowledge gap.
     */
    public function dismiss(KnowledgeGap $knowledgeGap): JsonResponse
    {
        $knowledgeGap->update(['status' => 'dismissed']);

        return response()->json([
            'success' => true,
            'message' => 'Knowledge gap berhasil diabaikan.'
        ]);
    }

    /**
     * Mark a knowledge gap as resolved.
     */
    public function resolve(KnowledgeGap $knowledgeGap, Request $request): JsonResponse
    {
        $knowledgeGap->update([
            'status' => 'resolved',
            'draft_kb_id' => $request->get('kb_id')
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Knowledge gap ditandai telah terselesaikan.'
        ]);
    }

    /**
     * Delete a knowledge gap record.
     */
    public function destroy(KnowledgeGap $knowledgeGap): JsonResponse
    {
        $knowledgeGap->delete();

        return response()->json([
            'success' => true,
            'message' => 'Knowledge gap berhasil dihapus.'
        ]);
    }
}
