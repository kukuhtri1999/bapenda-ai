<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ChatMessage;
use Illuminate\Support\Str;

class ExportChatsForAI extends Command
{
  protected $signature = 'analytics:export-chats {--start=} {--end=} {--out=chat_export.xlsx}';
  protected $description = 'Export chats to CSV/XLSX for offline analysis (for bulk AI processing)';

  public function handle()
  {
    $start = $this->option('start');
    $end = $this->option('end');
    $out = $this->option('out') ?: 'chat_export.xlsx';

    $query = ChatMessage::query();
    if ($start && $end) {
      $query->whereBetween('sent_at', [$start, $end]);
    }
    $messages = $query->orderBy('sent_at')->get(['chat_id', 'role', 'content', 'sent_at']);

    $rows = [];
    foreach ($messages as $m) {
      $rows[] = [
        'chat_id' => $m->chat_id,
        'role' => $m->role,
        'content' => $m->content,
        'sent_at' => $m->sent_at,
      ];
    }

    // Write as CSV (xlsx would need dependency; CSV is portable)
    $fp = fopen($out, 'w');
    fputcsv($fp, ['chat_id', 'role', 'content', 'sent_at']);
    foreach ($rows as $r) fputcsv($fp, [$r['chat_id'], $r['role'], str_replace(["\r\n", "\n"], ' ', $r['content']), $r['sent_at']]);
    fclose($fp);

    $this->info('Exported ' . count($rows) . ' rows to ' . $out);
    return 0;
  }
}
