<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChatFeedback;
use App\Models\Chat;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Log;

class FeedbackController extends Controller
{
    /**
     * Display a listing of feedback.
     */
    public function index(Request $request): Response
    {
        $query = ChatFeedback::query()
            ->leftJoin('chats', 'chat_feedback.session_id', '=', 'chats.session_id')
            ->select('chat_feedback.*')
            ->orderBy('chat_feedback.created_at', 'desc');

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('chat_feedback.feedback_text', 'like', "%{$search}%")
                  ->orWhere('chat_feedback.session_id', 'like', "%{$search}%")
                  ->orWhere('chat_feedback.rating', 'like', "%{$search}%");
            });
        }

        // Rating filter
        if ($request->filled('rating')) {
            $query->where('chat_feedback.rating', $request->get('rating'));
        }

        // Date range filter
        if ($request->filled('date_from')) {
            $query->whereDate('chat_feedback.created_at', '>=', $request->get('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('chat_feedback.created_at', '<=', $request->get('date_to'));
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        
        if (in_array($sortBy, ['created_at', 'rating', 'session_id'])) {
            $query->orderBy('chat_feedback.' . $sortBy, $sortOrder === 'asc' ? 'asc' : 'desc');
        }

        $feedbacks = $query->paginate(15)->withQueryString();

        // Calculate statistics
        $stats = [
            'total_feedbacks' => ChatFeedback::count(),
            'average_rating' => round(ChatFeedback::avg('rating'), 2),
            'rating_distribution' => ChatFeedback::selectRaw('rating, COUNT(*) as count')
                ->groupBy('rating')
                ->orderBy('rating')
                ->get()
                ->pluck('count', 'rating')
                ->toArray(),
            'recent_feedbacks' => ChatFeedback::where('created_at', '>=', now()->subDays(7))->count(),
        ];

        return Inertia::render('Admin/Feedback/Index', [
            'feedbacks' => $feedbacks,
            'stats' => $stats,
            'filters' => $request->only(['search', 'rating', 'date_from', 'date_to', 'sort_by', 'sort_order']),
        ]);
    }

    /**
     * Show detailed feedback.
     */
    public function show(ChatFeedback $feedback): Response
    {
        // Try to load the chat session, but don't fail if it doesn't exist
        $chatSession = null;
        try {
            $chatSession = Chat::where('session_id', $feedback->session_id)
                ->with(['messages'])
                ->first();
        } catch (\Exception $e) {
            // Handle case where chat session doesn't exist
            Log::warning('Chat session not found for feedback', [
                'feedback_id' => $feedback->id,
                'session_id' => $feedback->session_id,
                'error' => $e->getMessage()
            ]);
        }

        return Inertia::render('Admin/Feedback/Show', [
            'feedback' => array_merge($feedback->toArray(), [
                'chat_session' => $chatSession ? $chatSession->toArray() : null
            ]),
        ]);
    }

    /**
     * Export feedback data.
     */
    public function export(Request $request)
    {
        $query = ChatFeedback::query();

        // Apply same filters as index
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('feedback_text', 'like', "%{$search}%")
                  ->orWhere('session_id', 'like', "%{$search}%");
            });
        }

        if ($request->filled('rating')) {
            $query->where('rating', $request->get('rating'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->get('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->get('date_to'));
        }

        $feedbacks = $query->orderBy('created_at', 'desc')->get();

        $csvData = [];
        $csvData[] = ['Session ID', 'Rating', 'Feedback Text', 'Created At'];

        foreach ($feedbacks as $feedback) {
            $csvData[] = [
                $feedback->session_id,
                $feedback->rating,
                $feedback->feedback_text ?? 'No text provided',
                $feedback->created_at->format('Y-m-d H:i:s'),
            ];
        }

        $filename = 'chat_feedback_' . now()->format('Y_m_d_H_i_s') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($csvData) {
            $file = fopen('php://output', 'w');
            foreach ($csvData as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}