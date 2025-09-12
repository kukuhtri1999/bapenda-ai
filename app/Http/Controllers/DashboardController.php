<?php

namespace App\Http\Controllers;

use App\Models\WajibPajak;
use App\Models\ChatMessage;
use App\Models\AnalysisReport;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

class DashboardController extends Controller
{
  public function index()
  {
    // Aggregate counters relevant to domain
    $wpTotal = WajibPajak::query()->count();
    $wpThisMonth = WajibPajak::query()->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count();

    $chatTotal = ChatMessage::query()->count();
    $chatThisWeek = ChatMessage::query()->whereBetween('sent_at', [now()->startOfWeek(), now()->endOfWeek()])->count();

    $latestReport = AnalysisReport::query()->latest('end_date')->first();
    $insightAvailable = (bool) ($latestReport && $latestReport->status === 'completed');
    $insightPeriod = $latestReport ? [optional($latestReport->start_date)->toDateString(), optional($latestReport->end_date)->toDateString()] : null;

    return Inertia::render('Dashboard', [
      'metrics' => [
        'wajib_pajak' => [
          'total' => $wpTotal,
          'this_month' => $wpThisMonth,
        ],
        'chat_messages' => [
          'total' => $chatTotal,
          'this_week' => $chatThisWeek,
        ],
        'insights' => [
          'available' => $insightAvailable,
          'period' => $insightPeriod,
        ],
      ],
    ]);
  }
}
