<?php

namespace Tests\Feature;

use App\Models\KnowledgeBase;
use App\Services\SeoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SeoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        SeoService::clearCache();
    }

    protected function makeGuide(array $overrides = []): KnowledgeBase
    {
        return KnowledgeBase::withoutEvents(function () use ($overrides) {
            $title = $overrides['title'] ?? 'Panduan Pajak Kendaraan';
            $answer = $overrides['answer'] ?? 'Penjelasan lengkap layanan KB Samsat Lamongan.';
            $content = $overrides['content'] ?? $answer;

            return KnowledgeBase::create(array_merge([
                'title' => $title,
                'question' => $overrides['question'] ?? 'Bagaimana cara mengurus ' . $title . '?',
                'answer' => $answer,
                'content' => $content,
                'excerpt' => Str::limit(strip_tags($content), 120),
                'search_content' => $title . ' ' . strip_tags($answer . ' ' . $content),
                'category' => 'Pajak Kendaraan',
                'type' => 'FAQ',
                'status' => 'published',
                'is_active' => true,
                'priority' => 10,
            ], $overrides));
        });
    }

    public function test_robots_txt_contains_disallow_rules_and_sitemap(): void
    {
        $response = $this->get('/robots.txt');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/plain; charset=utf-8');
        $response->assertSee('User-agent: *');
        $response->assertSee('Allow: /panduan');
        $response->assertSee('Disallow: /admin/');
        $response->assertSee('Disallow: /customer-service');
        $response->assertSee('Sitemap: ' . url('/sitemap.xml'));
    }

    public function test_sitemap_xml_lists_public_routes_and_only_published_active_guides(): void
    {
        $published = $this->makeGuide([
            'title' => 'Ketentuan Bea Balik Nama Kendaraan Bermotor',
            'question' => 'Bagaimana ketentuan BBNKB?',
            'answer' => 'Penyerahan kedua dan seterusnya bebas BBNKB.',
        ]);

        $draft = $this->makeGuide([
            'title' => 'Panduan Draft Tidak Boleh Tampil',
            'answer' => 'Isi draft',
            'status' => 'draft',
        ]);

        $response = $this->get('/sitemap.xml');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/xml; charset=utf-8');
        $response->assertSee('<loc>' . url('/') . '</loc>', false);
        $response->assertSee('<loc>' . url('/panduan') . '</loc>', false);
        $response->assertSee(
            '<loc>' . route('panduan.show', ['id' => $published->id, 'slug' => Str::slug($published->title)]) . '</loc>',
            false
        );
        $response->assertDontSee((string) $draft->id . '-' . Str::slug($draft->title));
    }

    public function test_homepage_renders_google_verification_canonical_og_and_json_ld(): void
    {
        $this->makeGuide([
            'title' => 'Syarat Perpanjang STNK Tahunan',
            'question' => 'Apa syarat perpanjangan STNK tahunan di Samsat Lamongan?',
            'answer' => 'Membawa e-KTP asli sesuai nama di STNK dan STNK asli.',
            'category' => 'Layanan STNK',
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('name="google-site-verification" content="U2b8LtcV2OaWO0Fhlwk75pIquUB_N4b38iFUgt1oZNc"', false);
        $response->assertSee('rel="canonical" href="' . url('/') . '"', false);
        $response->assertSee('property="og:image"', false);
        $response->assertSee('application/ld+json', false);
        $response->assertSee('GovernmentOffice', false);
        $response->assertSee('FAQPage', false);
        $response->assertSee('index, follow', false);
    }

    public function test_panduan_index_and_show_canonical_redirect_and_draft_protection(): void
    {
        $published = $this->makeGuide([
            'title' => 'Tarif SWDKLLJ Mobil Pribadi',
            'question' => 'Berapa tarif SWDKLLJ mobil pribadi?',
            'answer' => 'Tarif SWDKLLJ mobil pribadi Rp 143.000.',
            'content' => "## Rincian Tarif\n\n* Mobil penumpang non-angkutan umum: **Rp 143.000**.",
            'category' => 'SWDKLLJ',
            'type' => 'Panduan',
        ]);

        $draft = $this->makeGuide([
            'title' => 'Internal Draft KB',
            'content' => 'Rahasia internal',
            'category' => 'Internal',
            'type' => 'Panduan',
            'status' => 'draft',
            'is_active' => false,
        ]);

        // Index returns 200
        $indexRes = $this->get('/panduan');
        $indexRes->assertStatus(200);
        $indexRes->assertSee('Tarif SWDKLLJ Mobil Pribadi', false);

        // Wrong slug 301 redirects to canonical slug
        $canonicalUrl = route('panduan.show', ['id' => $published->id, 'slug' => Str::slug($published->title)]);
        $wrongRes = $this->get("/panduan/{$published->id}-slug-salah");
        $wrongRes->assertStatus(301);
        $wrongRes->assertRedirect($canonicalUrl);

        // Canonical URL renders 200 with Article schema and formatted HTML
        $showRes = $this->get($canonicalUrl);
        $showRes->assertStatus(200);
        $showRes->assertSee('Article', false);
        $showRes->assertSee('BreadcrumbList', false);
        $showRes->assertSee('Rp 143.000', false);

        // Draft KB returns 404
        $this->get("/panduan/{$draft->id}-internal-draft-kb")->assertStatus(404);
    }

    public function test_login_page_has_noindex_robots_meta(): void
    {
        $loginPath = '/' . ltrim((string) config('auth.custom_login_path', 'login'), '/');
        $response = $this->get($loginPath);
        $response->assertStatus(200);
        $response->assertSee('<meta name="robots" content="noindex, nofollow">', false);
    }
}
