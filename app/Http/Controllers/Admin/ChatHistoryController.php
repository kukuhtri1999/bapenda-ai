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

  // Paginated list with filters; returns JSON
  public function index(Request $request)
  {
    $per = (int) $request->query('per_page', 10);
    $allowed = [10, 25, 50, 100];
    if (!in_array($per, $allowed, true)) $per = 10;

    $q = (string) $request->query('q', '');
    $sentiment = $request->query('sentiment');
    $topic = $request->query('topic');
    $start = $request->query('start_date'); // yyyy-mm-dd
    $end = $request->query('end_date'); // yyyy-mm-dd

    $query = ChatMessage::query()
      ->select('id', 'content', 'sentiment', 'topic', 'sent_at')
      ->orderByDesc('sent_at');

    if ($q !== '') {
      $query->where(function ($x) use ($q) {
        $x->where('content', 'LIKE', "%" . $q . "%");
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

  // Meta for filters (distinct topics)
  public function meta()
  {
    $topics = ChatMessage::query()
      ->select('topic')
      ->whereNotNull('topic')
      ->where('topic', '!=', '')
      ->distinct()
      ->orderBy('topic')
      ->limit(200)
      ->pluck('topic');

    return response()->json([
      'topics' => $topics,
      'sentiments' => ['positive', 'neutral', 'negative'],
    ]);
  }
}
