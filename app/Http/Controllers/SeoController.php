<?php

namespace App\Http\Controllers;

use App\Models\KnowledgeBase;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class SeoController extends Controller
{
    /**
     * Dynamically generate robots.txt based on current domain and ADMIN_LOGIN_PATH.
     */
    public function robots(): Response
    {
        $customLoginPath = ltrim((string) config('auth.custom_login_path', ''), '/');
        $sitemapUrl = url('/sitemap.xml');

        $lines = [
            'User-agent: *',
            'Allow: /',
            'Allow: /panduan',
            'Allow: /wajib-pajak',
            'Allow: /lotre-undian',
            '',
            'Disallow: /admin/',
            'Disallow: /dashboard',
            'Disallow: /api/',
            'Disallow: /login',
            'Disallow: /register',
            'Disallow: /forgot-password',
            'Disallow: /reset-password',
            'Disallow: /users',
            'Disallow: /knowledge-base',
            'Disallow: /settings',
            'Disallow: /products',
            'Disallow: /chat',
            'Disallow: /customer-service',
            'Disallow: /edit-foto',
        ];

        if ($customLoginPath !== '' && $customLoginPath !== 'login') {
            $lines[] = "Disallow: /{$customLoginPath}";
        }

        $lines[] = '';
        $lines[] = "Sitemap: {$sitemapUrl}";

        return response(implode("\n", $lines) . "\n", 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    /**
     * Dynamically generate cached XML Sitemap covering homepage, services, and all published guides.
     */
    public function sitemap(): Response
    {
        $xml = Cache::remember('seo_sitemap_xml', 21600, function () {
            $now = now()->toAtomString();

            $urls = [
                [
                    'loc' => url('/'),
                    'lastmod' => $now,
                    'changefreq' => 'daily',
                    'priority' => '1.0',
                ],
                [
                    'loc' => url('/panduan'),
                    'lastmod' => $now,
                    'changefreq' => 'daily',
                    'priority' => '0.9',
                ],
                [
                    'loc' => url('/wajib-pajak'),
                    'lastmod' => $now,
                    'changefreq' => 'weekly',
                    'priority' => '0.8',
                ],
                [
                    'loc' => url('/lotre-undian'),
                    'lastmod' => $now,
                    'changefreq' => 'weekly',
                    'priority' => '0.7',
                ],
            ];

            $kbs = KnowledgeBase::query()
                ->where('status', 'published')
                ->where('is_active', true)
                ->orderByDesc('updated_at')
                ->get(['id', 'title', 'updated_at', 'published_at']);

            foreach ($kbs as $kb) {
                $slug = Str::slug($kb->title) ?: 'panduan';
                $urls[] = [
                    'loc' => route('panduan.show', ['id' => $kb->id, 'slug' => $slug]),
                    'lastmod' => ($kb->updated_at ?? $kb->published_at ?? now())->toAtomString(),
                    'changefreq' => 'monthly',
                    'priority' => '0.8',
                ];
            }

            $out = ['<?xml version="1.0" encoding="UTF-8"?>'];
            $out[] = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

            foreach ($urls as $u) {
                $out[] = '  <url>';
                $out[] = '    <loc>' . e($u['loc']) . '</loc>';
                $out[] = '    <lastmod>' . e($u['lastmod']) . '</lastmod>';
                $out[] = '    <changefreq>' . e($u['changefreq']) . '</changefreq>';
                $out[] = '    <priority>' . e($u['priority']) . '</priority>';
                $out[] = '  </url>';
            }

            $out[] = '</urlset>';

            return implode("\n", $out) . "\n";
        });

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=utf-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
