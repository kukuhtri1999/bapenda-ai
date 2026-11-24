<?php

namespace Tests\Unit;

use App\Models\KnowledgeBase;
use App\Services\SalmaPromptService;
use Tests\TestCase;

class MarkdownHandlingAndSecurityTest extends TestCase
{
    public function test_clean_html_strips_tags_and_citation_markers(): void
    {
        $raw = '<p>Ketentuan <strong>BBNKB</strong> diatur dalam UU HKPD[cite: 42, 114]:</p><p>### 1. BBNKB I</p>';
        $cleaned = KnowledgeBase::cleanHtml($raw);

        $this->assertStringNotContainsString('[cite:', $cleaned);
        $this->assertStringNotContainsString('<strong>', $cleaned);
        $this->assertStringContainsString('Ketentuan BBNKB diatur dalam UU HKPD:', $cleaned);
        $this->assertStringContainsString('### 1. BBNKB I', $cleaned);
    }

    public function test_format_kb_chunks_preserves_markdown_structure_and_strips_citations(): void
    {
        /** @var SalmaPromptService $service */
        $service = app(SalmaPromptService::class);

        $chunks = $service->formatKbChunks([
            [
                'title' => 'Ketentuan BBNKB Jawa Timur',
                'category' => 'pajak',
                'score' => 0.92,
                '_source' => 'vector',
                'content' => '<p>Ketentuan **Bea Balik Nama** (Pasal 12-19)[cite: 42, 114]:</p><p>### 1. Perbedaan BBNKB I</p><p>* **Tarif BBNKB I:** Sebesar **12%**</p>',
            ],
        ]);

        $this->assertCount(1, $chunks);
        $block = $chunks[0];

        $this->assertStringNotContainsString('[cite:', $block);
        $this->assertStringContainsString("### 1. Perbedaan BBNKB I\n", $block);
        $this->assertStringContainsString('* **Tarif BBNKB I:** Sebesar **12%**', $block);
    }

    public function test_php_runtime_meets_november_2026_security_patch_requirements(): void
    {
        $this->artisan('app:security-check')
            ->assertExitCode(0);
    }
}
