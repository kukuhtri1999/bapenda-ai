<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\RunChatAnalytics;
use App\Models\AnalysisReport;
use App\Models\ChatMessage;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    // Render admin analytics Inertia page
    public function indexPage()
    {
        return inertia('Admin/Analytics/Index');
    }
    public function index()
    {
        return AnalysisReport::orderByDesc('created_at')->take(20)->get();
    }

    public function show($id)
    {
        return AnalysisReport::findOrFail($id);
    }

    public function start(Request $request)
    {
        $data = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'sample' => 'sometimes|boolean',
        ]);

        $start = $data['start_date'];
        $end = $data['end_date'];
        // base queries
        $messagesQuery = ChatMessage::whereBetween('sent_at', [$start, $end]);
        $messageCount = $messagesQuery->count();
        $chatCount = (clone $messagesQuery)->distinct('chat_id')->count('chat_id');

        $sample = (bool) ($data['sample'] ?? false);

        $report = AnalysisReport::create([
            'start_date' => $start,
            'end_date' => $end,
            'chat_count' => $chatCount, // store distinct chat count here
            'status' => 'pending',
        ]);

        $notes = [
            'sample' => $sample,
            'auto_sampled' => false,
            'message_count' => $messageCount,
            'processing_mode' => null,
        ];

        // decide synchronous vs queued: small workloads or sample mode run inline for instant result
        $syncThreshold = 300; // message count threshold for sync execution
        $runSync = $sample || $messageCount <= $syncThreshold || config('queue.default') === 'sync';
        $notes['processing_mode'] = $runSync ? 'sync' : 'queued';
        $report->notes = json_encode($notes);
        $report->save();

        if ($runSync) {
            // execute immediately
            RunChatAnalytics::dispatchSync($report);
            $report->refresh();
            return response()->json([
                'report_id' => $report->id,
                'chat_count' => $report->chat_count,
                'message_count' => $messageCount,
                'status' => $report->status,
                'summary_json' => $report->summary_json,
                'processing_mode' => 'sync',
            ]);
        }

        // queued path
        RunChatAnalytics::dispatch($report)->onQueue('default');
        return response()->json([
            'report_id' => $report->id,
            'chat_count' => $chatCount,
            'message_count' => $messageCount,
            'status' => 'pending',
            'processing_mode' => 'queued',
        ]);
    }
}
