<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\OpenAIService;

class TestClassifyChats extends Command
{
  protected $signature = 'analytics:test-classify';
  protected $description = 'Test classifyChats with a small sample and print raw AI output and parsed data.';

  public function handle(OpenAIService $ai)
  {
    $sample = [
      ['chat_id' => 't1', 'text' => 'Bagaimana cara bayar pajak kendaraan secara online?'],
      ['chat_id' => 't2', 'text' => 'Syarat untuk perpanjang STNK apa saja?'],
      ['chat_id' => 't3', 'text' => 'Kenapa antriannya lama sekali, pelayanan sangat lambat'],
      ['chat_id' => 't4', 'text' => 'Apakah ada samsat keliling di daerah saya?'],
      ['chat_id' => 't5', 'text' => 'Saya ingin tahu jumlah denda kalau telat 3 bulan'],
    ];

    $categories = config('analytics.categories');
    $res = $ai->classifyChats($sample, array_combine($categories, $categories));

    $this->line("RAW MESSAGE:\n" . ($res['message'] ?? ''));
    $this->line("PARSED DATA:\n" . json_encode($res['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    return 0;
  }
}
