<?php

namespace App\Services;

use App\Models\HomepageContent;
use App\Models\KnowledgeBase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class SeoService
{
    public const DEFAULT_SITE_NAME = 'KB Samsat Lamongan — SALMA AI';
    public const DEFAULT_OG_IMAGE = '/images/og-samsat-lamongan.png';
    public const DEFAULT_LOGO = '/images/logo-samsat-lamongan.png';

    /**
     * Build clean canonical URL without tracking query parameters.
     */
    public function canonicalUrl(Request $request, ?string $explicitPath = null): string
    {
        if ($explicitPath !== null) {
            return url($explicitPath);
        }

        $path = $request->getPathInfo();
        if ($path === '/' || $path === '') {
            return url('/');
        }

        return url(rtrim($path, '/'));
    }

    /**
     * Default SEO payload when a controller does not supply specific metadata.
     */
    public function resolveFallback(Request $request, array $page = []): array
    {
        $path = ltrim($request->getPathInfo(), '/');
        $customLogin = ltrim((string) config('auth.custom_login_path', ''), '/');

        // Mark internal, admin, auth, and transactional endpoints as noindex
        $noIndexPrefixes = array_filter([
            'admin',
            'dashboard',
            'login',
            'register',
            'forgot-password',
            'reset-password',
            'two-factor-challenge',
            'user',
            'users',
            'knowledge-base',
            'settings',
            'products',
            'chat',
            'edit-foto',
            'customer-service',
            $customLogin,
        ]);

        foreach ($noIndexPrefixes as $prefix) {
            if ($path === $prefix || Str::startsWith($path, $prefix . '/')) {
                return $this->forNoIndex(
                    config('app.name', self::DEFAULT_SITE_NAME),
                    $this->canonicalUrl($request)
                );
            }
        }

        // Welcome fallback if needed
        if ($path === '') {
            return $this->forWelcome($request);
        }

        return $this->forPublicPage(
            request: $request,
            title: config('app.name', self::DEFAULT_SITE_NAME),
            description: 'Portal informasi resmi KB Samsat Lamongan & asisten digital SALMA AI untuk layanan pajak kendaraan bermotor, jadwal Samsat Keliling, dan panduan administrasi.',
        );
    }

    /**
     * Rich SEO & JSON-LD metadata for the main homepage (/).
     */
    public function forWelcome(Request $request, ?array $cms = null): array
    {
        $cms ??= HomepageContent::getAllGrouped();

        $agencyName = (string) ($cms['footer_agency_name'] ?? 'KB Samsat Lamongan');
        $address = (string) ($cms['contact_address'] ?? 'Jl. Veteran No. 1A, Tumenggungan, Lamongan');
        $cityPostal = (string) ($cms['contact_city_postal'] ?? 'Kabupaten Lamongan, Jawa Timur 62211');
        $phone = (string) ($cms['contact_phone'] ?? '(0322) 311234');
        $weekdayHours = (string) ($cms['contact_hours_weekday'] ?? 'Senin – Kamis, Sabtu: 08.00 – 12.00 WIB');
        $fridayHours = (string) ($cms['contact_hours_friday'] ?? 'Jumat: 08.00 – 11.00 WIB');

        $title = 'KB Samsat Lamongan — Layanan Pajak Kendaraan, Jadwal Samsat & SALMA AI';
        $description = "Portal resmi {$agencyName} Bapenda Jawa Timur. Cek jadwal Samsat Keliling Lamongan, BELOK WANGI, cara bayar PKB online, info pemutihan pajak, dan tanya SALMA AI 24 jam.";
        $canonical = $this->canonicalUrl($request, '/');

        $socialLinks = [];
        if (!empty($cms['footer_social_links']) && is_array($cms['footer_social_links'])) {
            foreach ($cms['footer_social_links'] as $soc) {
                if (!empty($soc['url']) && filter_var($soc['url'], FILTER_VALIDATE_URL)) {
                    $socialLinks[] = $soc['url'];
                }
            }
        }
        if (empty($socialLinks)) {
            $socialLinks = [
                'https://www.instagram.com/samsat_lamongan',
                'https://wa.me/6282232161707',
            ];
        }

        // 1. GovernmentOffice + LocalBusiness schema
        $orgSchema = [
            '@context' => 'https://schema.org',
            '@type' => ['GovernmentOffice', 'LocalBusiness'],
            '@id' => url('/#organization'),
            'name' => 'Kantor Bersama Samsat Lamongan — Bapenda Provinsi Jawa Timur',
            'alternateName' => ['KB Samsat Lamongan', 'Samsat Lamongan', 'SALMA AI Samsat Lamongan'],
            'url' => $canonical,
            'logo' => url(self::DEFAULT_LOGO),
            'image' => url(self::DEFAULT_OG_IMAGE),
            'description' => "Kantor Bersama Samsat Kabupaten Lamongan melayani pembayaran Pajak Kendaraan Bermotor (PKB), pengesahan STNK tahunan, perpanjangan STNK 5 tahunan, Bea Balik Nama (BBNKB), mutasi kendaraan, serta asisten virtual SALMA AI.",
            'telephone' => $phone,
            'priceRange' => 'Resmi Sesuai Peraturan Daerah',
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => $address,
                'addressLocality' => 'Lamongan',
                'addressRegion' => 'Jawa Timur',
                'postalCode' => '62211',
                'addressCountry' => 'ID',
            ],
            'geo' => [
                '@type' => 'GeoCoordinates',
                'latitude' => -7.1190,
                'longitude' => 112.4167,
            ],
            'openingHoursSpecification' => [
                [
                    '@type' => 'OpeningHoursSpecification',
                    'dayOfWeek' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Saturday'],
                    'opens' => '08:00',
                    'closes' => '12:00',
                ],
                [
                    '@type' => 'OpeningHoursSpecification',
                    'dayOfWeek' => ['Friday'],
                    'opens' => '08:00',
                    'closes' => '11:00',
                ],
            ],
            'areaServed' => [
                '@type' => 'AdministrativeArea',
                'name' => 'Kabupaten Lamongan, Jawa Timur',
            ],
            'sameAs' => $socialLinks,
        ];

        // 2. WebSite schema with SearchAction pointing to public Panduan search
        $websiteSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            '@id' => url('/#website'),
            'url' => $canonical,
            'name' => 'KB Samsat Lamongan — SALMA AI',
            'description' => $description,
            'inLanguage' => 'id-ID',
            'publisher' => ['@id' => url('/#organization')],
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => [
                    '@type' => 'EntryPoint',
                    'urlTemplate' => url('/panduan') . '?q={search_term_string}',
                ],
                'query-input' => 'required name=search_term_string',
            ],
        ];

        // 3. FAQPage schema built from published active KB FAQ items
        $faqs = $this->getTopWelcomeFaqs();
        $schemas = [$orgSchema, $websiteSchema];

        if (!empty($faqs)) {
            $faqEntities = [];
            foreach ($faqs as $faq) {
                $faqEntities[] = [
                    '@type' => 'Question',
                    'name' => $faq['question'],
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => $faq['answer'],
                    ],
                ];
            }

            $schemas[] = [
                '@context' => 'https://schema.org',
                '@type' => 'FAQPage',
                'mainEntity' => $faqEntities,
            ];
        }

        return [
            'title' => $title,
            'description' => Str::limit($description, 160, ''),
            'keywords' => 'samsat lamongan, kb samsat lamongan, cek pajak kendaraan lamongan, jadwal samsat keliling lamongan, pemutihan pajak jatim lamongan, bbnkb lamongan, salma ai, stnk 5 tahunan lamongan, belok wangi samsat, payment point samsat lamongan',
            'canonical' => $canonical,
            'robots' => 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1',
            'og_type' => 'website',
            'og_image' => url(self::DEFAULT_OG_IMAGE),
            'og_image_alt' => 'KB Samsat Lamongan & SALMA AI — Layanan Resmi Pajak Kendaraan Bermotor',
            'schemas' => $schemas,
            'noscript_meta' => [
                'agency' => $agencyName,
                'address' => "{$address}, {$cityPostal}",
                'phone' => $phone,
                'hours' => "{$weekdayHours} | {$fridayHours}",
                'faqs' => array_slice($faqs, 0, 5),
            ],
        ];
    }

    /**
     * SEO & JSON-LD metadata for /panduan index page.
     */
    public function forPanduanIndex(Request $request, ?string $category = null, ?string $query = null, int $total = 0): array
    {
        $titleParts = ['Panduan & FAQ Pajak Kendaraan'];
        if ($category) {
            $titleParts[] = $category;
        }
        $titleParts[] = 'KB Samsat Lamongan';
        $title = implode(' — ', $titleParts);

        $description = $category
            ? "Kumpulan panduan resmi kategori {$category} di KB Samsat Lamongan: syarat, tarif, dasar hukum, dan cara mengurus pajak kendaraan bermotor."
            : "Pusat panduan resmi pajak kendaraan bermotor KB Samsat Lamongan ({$total} artikel & FAQ): BBNKB, PKB tahunan, STNK 5 tahunan, mutasi, SWDKLLJ, dan pemutihan.";

        $canonical = $category
            ? url('/panduan') . '?kategori=' . urlencode($category)
            : $this->canonicalUrl($request, '/panduan');

        // Prevent search result query pages from polluting the index
        $robots = $query
            ? 'noindex, follow'
            : 'index, follow, max-image-preview:large, max-snippet:-1';

        $breadcrumbSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                [
                    '@type' => 'ListItem',
                    'position' => 1,
                    'name' => 'Beranda',
                    'item' => url('/'),
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 2,
                    'name' => 'Panduan Pajak Kendaraan',
                    'item' => url('/panduan'),
                ],
            ],
        ];

        $collectionSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            'name' => $title,
            'description' => $description,
            'url' => $canonical,
            'isPartOf' => ['@id' => url('/#website')],
            'inLanguage' => 'id-ID',
        ];

        return [
            'title' => $title,
            'description' => Str::limit($description, 160, ''),
            'keywords' => 'panduan samsat lamongan, syarat balik nama motor, tarif pajak kendaraan jatim, faq pajak kendaraan bermotor, syarat perpanjang stnk lamongan',
            'canonical' => $canonical,
            'robots' => $robots,
            'og_type' => 'website',
            'og_image' => url(self::DEFAULT_OG_IMAGE),
            'og_image_alt' => 'Panduan Resmi Pajak Kendaraan — KB Samsat Lamongan',
            'schemas' => [$collectionSchema, $breadcrumbSchema],
        ];
    }

    /**
     * SEO & JSON-LD metadata for a single /panduan/{id}-{slug} guide page.
     */
    public function forPanduanShow(KnowledgeBase $kb): array
    {
        $slug = Str::slug($kb->title) ?: 'panduan';
        $canonical = route('panduan.show', ['id' => $kb->id, 'slug' => $slug]);

        $plainAnswer = KnowledgeBase::cleanHtml((string) ($kb->answer ?: $kb->content ?: $kb->excerpt ?: ''));
        $description = Str::limit($plainAnswer ?: $kb->title, 155, '...');

        $keywordsList = array_filter(array_merge(
            [$kb->category, 'samsat lamongan', 'pajak kendaraan lamongan'],
            is_array($kb->keywords) ? $kb->keywords : [],
            is_array($kb->tags) ? $kb->tags : []
        ));
        $keywords = implode(', ', array_unique(array_slice($keywordsList, 0, 12)));

        $category = $kb->category ?: 'Layanan Samsat';

        $breadcrumbSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                [
                    '@type' => 'ListItem',
                    'position' => 1,
                    'name' => 'Beranda',
                    'item' => url('/'),
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 2,
                    'name' => 'Panduan Pajak',
                    'item' => url('/panduan'),
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 3,
                    'name' => Str::limit($kb->title, 70),
                    'item' => $canonical,
                ],
            ],
        ];

        $articleSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => Str::limit($kb->title, 110, ''),
            'description' => $description,
            'articleSection' => $category,
            'inLanguage' => 'id-ID',
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => $canonical,
            ],
            'image' => [url(self::DEFAULT_OG_IMAGE)],
            'author' => [
                '@type' => 'GovernmentOrganization',
                'name' => 'KB Samsat Lamongan — Bapenda Jawa Timur',
                'url' => url('/'),
            ],
            'publisher' => [
                '@type' => 'GovernmentOrganization',
                'name' => 'KB Samsat Lamongan',
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => url(self::DEFAULT_LOGO),
                ],
            ],
            'datePublished' => ($kb->published_at ?? $kb->created_at)?->toAtomString(),
            'dateModified' => $kb->updated_at?->toAtomString(),
        ];

        $schemas = [$articleSchema, $breadcrumbSchema];

        if (!empty($kb->question) && !empty($plainAnswer)) {
            $schemas[] = [
                '@context' => 'https://schema.org',
                '@type' => 'FAQPage',
                'mainEntity' => [[
                    '@type' => 'Question',
                    'name' => trim($kb->question),
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => Str::limit($plainAnswer, 600),
                    ],
                ]],
            ];
        }

        return [
            'title' => "{$kb->title} — Panduan KB Samsat Lamongan",
            'description' => $description,
            'keywords' => $keywords,
            'canonical' => $canonical,
            'robots' => 'index, follow, max-image-preview:large, max-snippet:-1',
            'og_type' => 'article',
            'og_image' => url(self::DEFAULT_OG_IMAGE),
            'og_image_alt' => $kb->title,
            'schemas' => $schemas,
        ];
    }

    /**
     * Generic public indexable page metadata.
     */
    public function forPublicPage(
        Request $request,
        string $title,
        string $description,
        ?string $keywords = null,
        ?string $canonicalPath = null,
        string $robots = 'index, follow'
    ): array {
        return [
            'title' => $title,
            'description' => Str::limit($description, 160, ''),
            'keywords' => $keywords ?? 'samsat lamongan, bapenda jatim, salma ai, pajak kendaraan bermotor',
            'canonical' => $this->canonicalUrl($request, $canonicalPath),
            'robots' => $robots,
            'og_type' => 'website',
            'og_image' => url(self::DEFAULT_OG_IMAGE),
            'og_image_alt' => $title,
            'schemas' => [],
        ];
    }

    /**
     * Private/admin/transactional noindex metadata.
     */
    public function forNoIndex(string $title, ?string $canonical = null): array
    {
        return [
            'title' => $title,
            'description' => 'Halaman layanan internal dan manajemen sistem KB Samsat Lamongan.',
            'keywords' => '',
            'canonical' => $canonical ?? url('/'),
            'robots' => 'noindex, nofollow',
            'og_type' => 'website',
            'og_image' => url(self::DEFAULT_OG_IMAGE),
            'og_image_alt' => $title,
            'schemas' => [],
        ];
    }

    /**
     * Get cached top published FAQs for Homepage FAQPage schema & semantic fallback.
     *
     * @return array<int, array{id: int, title: string, slug: string, question: string, answer: string, url: string}>
     */
    public function getTopWelcomeFaqs(): array
    {
        return Cache::remember('seo_welcome_faqs', 21600, function () {
            try {
                $items = KnowledgeBase::query()
                    ->where('status', 'published')
                    ->where('is_active', true)
                    ->orderByDesc('priority')
                    ->orderByDesc('quality_score')
                    ->orderByDesc('updated_at')
                    ->limit(8)
                    ->get(['id', 'title', 'question', 'answer', 'content', 'excerpt']);

                $faqs = [];
                foreach ($items as $item) {
                    $q = trim((string) ($item->question ?: $item->title));
                    $a = KnowledgeBase::cleanHtml((string) ($item->answer ?: $item->content ?: $item->excerpt ?: ''));
                    if ($q === '' || $a === '') {
                        continue;
                    }
                    $slug = Str::slug($item->title) ?: 'panduan';
                    $faqs[] = [
                        'id' => $item->id,
                        'title' => $item->title,
                        'slug' => $slug,
                        'question' => Str::limit($q, 200),
                        'answer' => Str::limit($a, 500),
                        'url' => route('panduan.show', ['id' => $item->id, 'slug' => $slug]),
                    ];
                }

                return $faqs;
            } catch (\Throwable $e) {
                return [];
            }
        });
    }

    /**
     * Clear all cached SEO data (sitemap & FAQ schema).
     */
    public static function clearCache(): void
    {
        Cache::forget('seo_welcome_faqs');
        Cache::forget('seo_sitemap_xml');
    }
}
