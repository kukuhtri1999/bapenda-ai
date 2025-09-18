<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\OpenAIService;

class TestChatAsk extends Command
{
  protected $signature = 'chat:test {q* : Full user message} {--force-kb : Force KB-only fast path if possible}';
  protected $description = 'Simulate chat ask via OpenAIService with RAG; prints answer and knowledge_used';

  public function handle(OpenAIService $svc)
  {
    $q = implode(' ', $this->argument('q'));
    if ($this->option('force-kb')) {
      @putenv('RAG_FORCE_KB_FIRST=true');
      // also set config at runtime to ensure service sees it
      try {
        config(['services.ai.force_kb_first' => true]);
      } catch (\Throwable $t) {
      }
    }
    $messages = [
      ['role' => 'user', 'content' => $q],
    ];
    $res = $svc->generateCustomerServiceResponse($messages);
    $this->info('Success: ' . (($res['success'] ?? false) ? 'yes' : 'no'));
    $this->line('Knowledge used: ' . ($res['knowledge_used'] ?? 'n/a'));
    if (isset($res['error'])) {
      $this->error('Error: ' . $res['error']);
    }
    $this->line("\n----- Answer -----\n");
    $this->line((string)($res['message'] ?? ''));
    return 0;
  }
}
