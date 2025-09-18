<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\OpenAIService;

class TestKbAnswer extends Command
{
    protected $signature = 'rag:answer {q* : Query words}';
    protected $description = 'Build answer directly from top KB (fast-path) for debugging';

    public function handle(OpenAIService $svc)
    {
        $q = implode(' ', $this->argument('q'));
        $messages = [['role' => 'user', 'content' => $q]];

        $ref = new \ReflectionClass($svc);
        $getRel = $ref->getMethod('getRelevantKnowledge');
        $getRel->setAccessible(true);
        $list = $getRel->invoke($svc, $messages);
        if (empty($list)) {
            $this->error('No KB found');
            return 1;
        }
        $top = $list[0];
        $this->info('Top KB: #' . ($top['id'] ?? '-') . ' ' . ($top['title'] ?? ''));

        $build = $ref->getMethod('buildConciseAnswerFromKb');
        $build->setAccessible(true);
        $ans = $build->invoke($svc, $top, $q);
        $this->line("\n----- KB Answer -----\n");
        $this->line($ans !== '' ? $ans : '[EMPTY]');
        return 0;
    }
}
