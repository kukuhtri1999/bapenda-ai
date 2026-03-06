<?php

namespace App\Http\Controllers;

use App\Models\WajibPajak;
use App\Models\ChatMessage;
use App\Models\KnowledgeBase;
use App\Models\AnalysisReport;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

class DashboardController extends Controller
{
  public function index()
  {
    $wpTotal      = WajibPajak::query()->count();
    $wpThisMonth  = WajibPajak::query()
      ->whereMonth('created_at', now()->month)
      ->whereYear('created_at', now()->year)
      ->count();

    $chatTotal    = ChatMessage::query()->count();
    $chatThisWeek = ChatMessage::query()
      ->whereBetween('sent_at', [now()->startOfWeek(), now()->endOfWeek()])
      ->count();

    $kbTotal = KnowledgeBase::query()->count();

    $latestReport     = AnalysisReport::query()->latest('end_date')->first();
    $insightAvailable = (bool) ($latestReport && $latestReport->status === 'completed');
    $insightPeriod    = $latestReport
      ? [
        optional($latestReport->start_date)->toDateString(),
        optional($latestReport->end_date)->toDateString(),
      ]
      : null;

    // Average AI response time in seconds (null when no data yet)
    $avgResponseTime = ChatMessage::query()
      ->whereNotNull('response_time_seconds')
      ->where('response_time_seconds', '>', 0)
      ->avg('response_time_seconds');

    return Inertia::render('Dashboard', [
      'metrics' => [
        'wajib_pajak' => [
          'total'      => $wpTotal,
          'this_month' => $wpThisMonth,
        ],
        'chat_messages' => [
          'total'     => $chatTotal,
          'this_week' => $chatThisWeek,
        ],
        'knowledge_base' => [
          'total' => $kbTotal,
        ],
        'insights' => [
          'available' => $insightAvailable,
          'period'    => $insightPeriod,
        ],
        'avg_response_time' => $avgResponseTime
          ? round((float) $avgResponseTime, 2)
          : null,
      ],
    ]);
  }
}
