<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\OpenAIService;

class TestRagQuery extends Command
{
    protected $signature = 'rag:test {q* : Query words}';
    protected $description = 'Test RAG retrieval and show top knowledge titles/scores';

    public function handle(OpenAIService $svc)
    {
        $q = implode(' ', $this->argument('q'));
        $messages = [
            ['role' => 'user', 'content' => $q],
        ];
        $ref = (new \ReflectionClass($svc))->getMethod('getRelevantKnowledge');
        $ref->setAccessible(true);
        $list = $ref->invoke($svc, $messages);
        $this->info('Top knowledge results:');
        foreach ($list as $i => $row) {
            $snippet = '';
            $body = (string)($row['content'] ?? '');
            if ($body === '' && !empty($row['answer'] ?? '')) $body = (string)$row['answer'];
            if ($body === '' && !empty($row['excerpt'] ?? '')) $body = (string)$row['excerpt'];
            if ($body === '' && !empty($row['question'] ?? '')) $body = (string)$row['question'];
            $plain = trim(strip_tags($body));
            if ($plain !== '') $snippet = mb_substr($plain, 0, 120);
            $this->line(sprintf('%d) #%s | score: %s | title: %s', $i + 1, $row['id'] ?? '-', $row['score'] ?? '-', $row['title'] ?? '-'));
            if ($snippet !== '') $this->line('    snippet: ' . $snippet . (mb_strlen($plain) > 120 ? '…' : ''));
            $sc = trim((string)($row['search_content'] ?? ''));
            if ($sc !== '') $this->line('    search_content: ' . mb_substr($sc, 0, 140) . (mb_strlen($sc) > 140 ? '…' : ''));
        }
        return 0;
    }
}
