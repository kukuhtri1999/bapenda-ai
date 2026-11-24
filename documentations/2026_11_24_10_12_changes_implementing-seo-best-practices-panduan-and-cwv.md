# Implementasi SEO Best Practice, Halaman Panduan Publik & Optimasi Core Web Vitals

- **Tanggal/Waktu**: 2026-11-24 10:12 WIB
- **Scope**: Technical SEO, Server-Side Head & JSON-LD Schema, Public Knowledge Guides (`/panduan`), Core Web Vitals & Caching, Test Isolation Guard

## 1. Ringkasan Implementasi (5 Pilar SEO)

### A. Technical SEO & Crawlability
- **Dynamic `robots.txt`** ([SeoController.php](file:///c:/laragon/www/bapenda-ai/app/Http/Controllers/SeoController.php), [routes/web.php](file:///c:/laragon/www/bapenda-ai/routes/web.php)):
  - Mengganti `public/robots.txt` statis dengan route dinamis `/robots.txt`.
  - Mengizinkan crawling pada halaman publik (`/`, `/panduan`, `/wajib-pajak`, `/lotre-undian`).
  - Memblokir jalur admin, autentikasi, API internal, `/customer-service`, `/edit-foto`, dan custom `ADMIN_LOGIN_PATH` dari indeks mesin pencari.
  - Menyertakan pointer `Sitemap: <APP_URL>/sitemap.xml`.
- **Dynamic Cached `sitemap.xml`**:
  - Route `/sitemap.xml` menghasilkan XML valid (di-cache 6 jam pada key `seo_sitemap_xml` dan otomatis di-invalidate saat `KnowledgeBase` atau `HomepageContent` diubah).
  - Memuat URL utama dan seluruh artikel/FAQ `KnowledgeBase` berstatus `published` dan `is_active = true` ke URL `/panduan/{id}-{slug}`.
- **Canonical URL & Per-Route `robots` Meta**:
  - Setiap halaman publik memiliki `<link rel="canonical">` bersih tanpa query tracking.
  - Halaman admin/auth/internal otomatis mendapatkan `<meta name="robots" content="noindex, nofollow">` via `SeoService::resolveFallback()`.

### B. Server-Side Metadata, Open Graph, Geo Tags & Structured Data (JSON-LD)
- **Centralized `SeoService`** ([SeoService.php](file:///c:/laragon/www/bapenda-ai/app/Services/SeoService.php)) & **Blade `<head>`** ([app.blade.php](file:///c:/laragon/www/bapenda-ai/resources/views/app.blade.php)):
  - Google Search Console verification tag `<meta name="google-site-verification" content="U2b8LtcV2OaWO0Fhlwk75pIquUB_N4b38iFUgt1oZNc" />`.
  - Metadata dinamis per route: `title`, `description`, `keywords`, `canonical`, `robots`, Geo Tags Lamongan (`ID-JI`, `-7.1190;112.4167`), Open Graph (`id_ID`, gambar banner resmi 1200×630 [og-samsat-lamongan.png](file:///c:/laragon/www/bapenda-ai/public/images/og-samsat-lamongan.png)), dan Twitter Card (`summary_large_image`).
  - **JSON-LD Structured Data**:
    - `GovernmentOffice` + `LocalBusiness` (alamat, jam operasional Senin–Sabtu & Jumat dari CMS, koordinat peta, area layanan Kabupaten Lamongan, tautan media sosial).
    - `WebSite` dengan `SearchAction` mengarah ke `/panduan?q={search_term_string}`.
    - `FAQPage` pada halaman utama dari entri KB publik teratas.
    - `Article`, `CollectionPage`, dan `BreadcrumbList` pada halaman `/panduan` dan `/panduan/{id}-{slug}`.
  - Blok `<noscript>` semantik di `app.blade.php` agar bot tanpa JS dan pembacaan awal tetap menangkap profil layanan, kontak, dan tautan panduan utama.

### C. Halaman Panduan Publik (`/panduan` & `/panduan/{id}-{slug}`)
- **Controller** ([PanduanController.php](file:///c:/laragon/www/bapenda-ai/app/Http/Controllers/PanduanController.php)):
  - Halaman indeks `/panduan` mendukung filter kategori, pencarian, serta pagination terindeks.
  - Halaman detail `/panduan/{id}-{slug}` memvalidasi canonical slug (301 redirect jika slug tidak sesuai), hanya menampilkan entri `published` + `is_active = true` (404 untuk draft/non-aktif), memformat Markdown/HTML secara aman tanpa artefak `[cite: ...]`, dan melakukan increment `view_count` via query builder tanpa memicu event re-index vektor.
- **Tampilan Vue** ([Panduan/Index.vue](file:///c:/laragon/www/bapenda-ai/resources/js/Pages/Panduan/Index.vue), [Panduan/Show.vue](file:///c:/laragon/www/bapenda-ai/resources/js/Pages/Panduan/Show.vue)) & **Internal Linking di Beranda** ([Welcome.vue](file:///c:/laragon/www/bapenda-ai/resources/js/Pages/Welcome.vue)):
  - Menambahkan tautan `Panduan Pajak` di navbar desktop, menu mobile, dan footer `Welcome.vue`.
  - Menambahkan section *Panduan & Pertanyaan Pajak Kendaraan* (`VExpansionPanels`) di `Welcome.vue` yang selaras 1:1 dengan schema `FAQPage` dan menautkan langsung ke halaman detail `/panduan/{id}-{slug}`.

### D. Core Web Vitals, Kompresi Aset & `.htaccess` Caching
- Mengompresi gambar berat di `public/images/` secara progresif/teroptimasi (total penghematan payload awal >3,3 MB, antara lain `logo-samsat-lamongan.png` 933KB → 242KB, `pemutihan-1.jpg` 661KB → 159KB, `pemutihan-2.jpg` 800KB → 210KB, `Woman_explaining_documents_to_man_7d7037d4.jpg` 771KB → 194KB, `bankjatim.png` 857KB → 23KB).
- Menambahkan `<link rel="preconnect">` untuk `fonts.bunny.net`, `fonts.googleapis.com`, dan `fonts.gstatic.com`.
- Menambahkan aturan Gzip (`mod_deflate`), `mod_expires`, dan header `Cache-Control` `immutable` (1 tahun untuk JS/CSS/font/gambar; `no-cache` untuk `sw.js` & `manifest.webmanifest`) di [public/.htaccess](file:///c:/laragon/www/bapenda-ai/public/.htaccess).

### E. Isolasi Database Testing & Pengujian Otomatis
- Menambahkan `APP_CONFIG_CACHE` terpisah di [phpunit.xml](file:///c:/laragon/www/bapenda-ai/phpunit.xml) serta safety guard di [tests/TestCase.php](file:///c:/laragon/www/bapenda-ai/tests/TestCase.php) agar `php artisan test` tidak pernah menyentuh database utama saat `config:cache` aktif.
- Menulis suite feature test di [tests/Feature/SeoTest.php](file:///c:/laragon/www/bapenda-ai/tests/Feature/SeoTest.php) (`5 passed, 35 assertions`).

## 2. Verifikasi
- `npm run build` (`vite build && vite build --ssr`): Sukses membangun seluruh halaman termasuk `Panduan/Index` & `Panduan/Show`.
- `php artisan test tests/Feature/SeoTest.php`: `5 passed (35 assertions)`.
- Pemeriksaan endpoint HTTP lokal `/`, `/robots.txt`, `/sitemap.xml`, `/panduan`, dan `/panduan/{id}-{slug}` terverifikasi merespons `200`.
