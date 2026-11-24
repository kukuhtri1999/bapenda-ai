<?php

namespace App\Http\Controllers;

use App\Models\KnowledgeBase;
use App\Services\SeoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PanduanController extends Controller
{
    public function __construct(protected SeoService $seoService)
    {
    }

    /**
     * Publicly indexable Knowledge Base guides & FAQs directory (/panduan).
     */
    public function index(Request $request): Response
    {
        $category = trim((string) $request->query('kategori', '')) ?: null;
        $search = trim((string) $request->query('q', '')) ?: null;

        $baseQuery = KnowledgeBase::query()
            ->where('status', 'published')
            ->where('is_active', true);

        $categories = (clone $baseQuery)
            ->selectRaw('COALESCE(NULLIF(category, ""), "Umum") as category_name, COUNT(*) as total')
            ->groupBy('category_name')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'name' => $row->category_name,
                'total' => (int) $row->total,
            ])
            ->values();

        $query = (clone $baseQuery);

        if ($category !== null) {
            if ($category === 'Umum') {
                $query->where(function ($q) {
                    $q->whereNull('category')->orWhere('category', '');
                });
            } else {
                $query->where('category', $category);
            }
        }

        if ($search !== null) {
            $like = '%' . addcslashes($search, '%_\\') . '%';
            $query->where(function ($q) use ($like) {
                $q->where('title', 'like', $like)
                    ->orWhere('question', 'like', $like)
                    ->orWhere('excerpt', 'like', $like)
                    ->orWhere('search_content', 'like', $like)
                    ->orWhere('category', 'like', $like);
            });
        }

        $paginated = $query
            ->orderByDesc('priority')
            ->orderByDesc('quality_score')
            ->orderByDesc('updated_at')
            ->paginate(12)
            ->withQueryString();

        $items = $paginated->through(function (KnowledgeBase $kb) {
            $slug = Str::slug($kb->title) ?: 'panduan';
            $plain = KnowledgeBase::cleanHtml((string) ($kb->excerpt ?: $kb->answer ?: $kb->content ?: ''));

            return [
                'id' => $kb->id,
                'title' => $kb->title,
                'question' => $kb->question,
                'slug' => $slug,
                'category' => $kb->category ?: 'Umum',
                'type' => $kb->type ?: 'Panduan',
                'excerpt' => Str::limit($plain, 175),
                'updated_at' => ($kb->updated_at ?? $kb->created_at)?->translatedFormat('d M Y'),
                'url' => route('panduan.show', ['id' => $kb->id, 'slug' => $slug]),
            ];
        });

        $seo = $this->seoService->forPanduanIndex($request, $category, $search, $paginated->total());

        return Inertia::render('Panduan/Index', [
            'guides' => $items,
            'categories' => $categories,
            'activeCategory' => $category,
            'searchQuery' => $search,
            'seoTitle' => $seo['title'],
        ])->withViewData(['seo' => $seo]);
    }

    /**
     * Public SEO-friendly guide detail page (/panduan/{id}-{slug}).
     */
    public function show(Request $request, int $id, ?string $slug = null): Response|RedirectResponse
    {
        $kb = KnowledgeBase::query()
            ->where('status', 'published')
            ->where('is_active', true)
            ->findOrFail($id);

        $canonicalSlug = Str::slug($kb->title) ?: 'panduan';
        if ($slug !== $canonicalSlug) {
            return redirect()->route('panduan.show', ['id' => $kb->id, 'slug' => $canonicalSlug], 301);
        }

        // Increment view count quietly without triggering vector-reindex model events
        DB::table('knowledge_bases')->where('id', $kb->id)->increment('view_count');

        $rawBody = trim((string) ($kb->answer ?: $kb->content ?: $kb->excerpt ?: ''));
        $bodyHtml = $this->formatGuideHtml($rawBody);

        $related = KnowledgeBase::query()
            ->where('status', 'published')
            ->where('is_active', true)
            ->where('id', '!=', $kb->id)
            ->when($kb->category, fn ($q) => $q->where('category', $kb->category))
            ->orderByDesc('priority')
            ->orderByDesc('quality_score')
            ->limit(4)
            ->get(['id', 'title', 'category', 'type', 'excerpt', 'answer', 'content', 'updated_at'])
            ->map(function (KnowledgeBase $item) {
                $itemSlug = Str::slug($item->title) ?: 'panduan';
                return [
                    'id' => $item->id,
                    'title' => $item->title,
                    'category' => $item->category ?: 'Umum',
                    'excerpt' => Str::limit(
                        KnowledgeBase::cleanHtml((string) ($item->excerpt ?: $item->answer ?: $item->content ?: '')),
                        110
                    ),
                    'url' => route('panduan.show', ['id' => $item->id, 'slug' => $itemSlug]),
                ];
            })
            ->values();

        $seo = $this->seoService->forPanduanShow($kb);

        return Inertia::render('Panduan/Show', [
            'guide' => [
                'id' => $kb->id,
                'title' => $kb->title,
                'question' => $kb->question,
                'category' => $kb->category ?: 'Umum',
                'type' => $kb->type ?: 'Panduan',
                'body_html' => $bodyHtml,
                'keywords' => is_array($kb->keywords) ? $kb->keywords : [],
                'tags' => is_array($kb->tags) ? $kb->tags : [],
                'updated_at' => ($kb->updated_at ?? $kb->created_at)?->translatedFormat('d F Y'),
                'updated_iso' => ($kb->updated_at ?? $kb->created_at)?->toAtomString(),
                'canonical_url' => $seo['canonical'],
            ],
            'relatedGuides' => $related,
            'seoTitle' => $seo['title'],
        ])->withViewData(['seo' => $seo]);
    }

    /**
     * Convert stored Markdown or rich HTML into clean, safe semantic HTML without [cite:] artifacts.
     */
    protected function formatGuideHtml(string $raw): string
    {
        if ($raw === '') {
            return '';
        }

        // Strip stray citation artifacts such as [cite: 42, 114]
        $cleaned = preg_replace('/\s*\[cite:\s*[\d,\s]+\]/i', '', $raw) ?? $raw;
        // Strip XML encoding declarations if present
        $cleaned = preg_replace('/<\?[^>]*\?>?/', '', $cleaned) ?? $cleaned;

        // If already HTML markup, strip dangerous tags while preserving structure
        if (preg_match('/<(p|div|h[1-6]|ul|ol|li|table|blockquote|pre)\b/i', $cleaned)) {
            return strip_tags($cleaned, '<p><br><h1><h2><h3><h4><h5><h6><ul><ol><li><strong><b><em><i><u><table><thead><tbody><tr><th><td><blockquote><a><hr><code><pre>');
        }

        // Convert raw Markdown to safe semantic HTML
        return Str::markdown($cleaned, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
    }
}
