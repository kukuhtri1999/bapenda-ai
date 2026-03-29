<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use Illuminate\Http\Request;

class ChatHistoryController extends Controller
{
  // Render the Inertia page; data loads via AJAX for speed
  public function indexPage()
  {
    return inertia('Admin/ChatHistory/Index');
  }

  // Paginated list — only user messages that have an AI answer
  public function index(Request $request)
  {
    $per = (int) $request->query('per_page', 10);
    if (!in_array($per, [10, 25, 50, 100], true)) $per = 10;

    $q         = (string) $request->query('q', '');
    $sentiment = $request->query('sentiment');
    $topic     = $request->query('topic');
    $start     = $request->query('start_date');
    $end       = $request->query('end_date');

    $query = ChatMessage::query()
      ->select('id', 'chat_id', 'content', 'answer', 'sentiment', 'topic', 'response_time_seconds', 'sent_at')
      ->where('role', 'user')
      ->whereNotNull('answer')
      ->where('answer', '!=', '')
      ->orderByDesc('sent_at');

    if ($q !== '') {
      $query->where(function ($x) use ($q) {
        $x->where('content', 'LIKE', '%' . $q . '%')
          ->orWhere('answer', 'LIKE', '%' . $q . '%');
      });
    }
    if ($sentiment && in_array($sentiment, ['positive', 'neutral', 'negative'], true)) {
      $query->where('sentiment', $sentiment);
    }
    if ($topic !== null && $topic !== '') {
      $query->where('topic', $topic);
    }
    if ($start) {
      $query->whereDate('sent_at', '>=', $start);
    }
    if ($end) {
      $query->whereDate('sent_at', '<=', $end);
    }

    return $query->paginate($per);
  }

  // Return full detail for a single Q&A pair
  public function show($id)
  {
    $msg = ChatMessage::select('id', 'chat_id', 'content', 'answer', 'sentiment', 'topic', 'response_time_seconds', 'sent_at')
      ->where('role', 'user')
      ->findOrFail($id);

    return response()->json($msg);
  }

  // Meta for filters (distinct topics + avg response time)
  public function meta()
  {
    $topics = ChatMessage::query()
      ->where('role', 'user')
      ->whereNotNull('topic')
      ->where('topic', '!=', '')
      ->distinct()
      ->orderBy('topic')
      ->limit(200)
      ->pluck('topic');

    $avgResponseTime = ChatMessage::query()
      ->where('role', 'user')
      ->whereNotNull('response_time_seconds')
      ->where('response_time_seconds', '>', 0)
      ->avg('response_time_seconds');

    $total = ChatMessage::where('role', 'user')->whereNotNull('answer')->where('answer', '!=', '')->count();

    return response()->json([
      'topics'                    => $topics,
      'sentiments'                => ['positive', 'neutral', 'negative'],
      'avg_response_time_seconds' => $avgResponseTime ? round((float) $avgResponseTime, 2) : null,
      'total'                     => $total,
    ]);
  }
}
