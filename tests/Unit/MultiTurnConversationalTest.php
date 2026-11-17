<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\SalmaPromptService;
use App\Services\OpenAIService;

class MultiTurnConversationalTest extends TestCase
{
    public function test_salma_prompt_contains_multi_turn_rules(): void
    {
        $promptService = app(SalmaPromptService::class);
        $prompt = $promptService->buildPrompt();

        $this->assertStringContainsString('MULTI-TURN CHAT CONTEXT', $prompt);
        $this->assertStringContainsString('DILARANG Mengulang Salam', $prompt);
        $this->assertStringContainsString('Resolusi Kata Rujukan & Anaphora', $prompt);
        $this->assertStringContainsString('Konteks Data Pengguna', $prompt);
    }

    public function test_salma_prompt_intent_inherits_prior_topic_on_follow_ups(): void
    {
        $promptService = app(SalmaPromptService::class);

        // Ambiguous follow-up inheriting prior topic
        $intent = $promptService->detectIntent('kalau motor?', 'mutasi');
        $this->assertEquals('mutasi', $intent);

        // Standalone location query shouldn't inherit
        $intentLocation = $promptService->detectIntent('jadwal samsat keliling di paciran', 'mutasi');
        $this->assertEquals('lokasi_jadwal', $intentLocation);
    }

    public function test_contextual_retrieval_query_synthesizes_follow_up(): void
    {
        $openAIService = app(OpenAIService::class);

        $history = [
            ['role' => 'user', 'content' => 'Bagaimana cara mutasi keluar motor beat plat S?'],
            ['role' => 'assistant', 'content' => 'Proses mutasi keluar di Samsat Induk Lamongan...'],
            ['role' => 'user', 'content' => 'Berapa biayanya?']
        ];

        $contextualQuery = $openAIService->buildContextualRetrievalQuery('Berapa biayanya?', $history);

        // Should combine mutasi keluar motor with biayanya
        $this->assertStringContainsString('mutasi keluar motor', strtolower($contextualQuery));
        $this->assertStringContainsString('biayanya', strtolower($contextualQuery));
    }

    public function test_contextual_retrieval_query_preserves_standalone_query(): void
    {
        $openAIService = app(OpenAIService::class);

        $history = [
            ['role' => 'user', 'content' => 'Bagaimana cara mutasi keluar motor beat plat S?'],
            ['role' => 'assistant', 'content' => 'Proses mutasi keluar di Samsat Induk Lamongan...'],
            ['role' => 'user', 'content' => 'Jadwal Samsat Keliling hari ini buka dimana?']
        ];

        $contextualQuery = $openAIService->buildContextualRetrievalQuery('Jadwal Samsat Keliling hari ini buka dimana?', $history);

        // Standalone query about Samsat Keliling should NOT be contaminated by mutasi
        $this->assertStringContainsString('samsat keliling', strtolower($contextualQuery));
        $this->assertStringNotContainsString('mutasi keluar', strtolower($contextualQuery));
    }

    public function test_personalized_greeting_generation(): void
    {
        $openAIService = app(OpenAIService::class);

        $wajibPajak = [
            'nama' => 'Kukuh',
            'nopol' => 'S 8735 JK',
            'nomer_wa' => '081234567890'
        ];

        $greeting = $openAIService->generateGreeting($wajibPajak);

        $this->assertStringContainsString('Kukuh', $greeting);
        $this->assertStringContainsString('S 8735 JK', $greeting);

        $genericGreeting = $openAIService->generateGreeting(null);
        $this->assertStringContainsString('SALMA AI', $genericGreeting);
    }
}

