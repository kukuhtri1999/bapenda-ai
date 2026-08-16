# 📋 Changelog: CMS Beranda & Footer serta Revamp Halaman Wajib Pajak

**Date**: 2026-08-16 09:55 WIB  
**Scope**: Full Stack Development (Database Migration, Eloquent Model, Seeder, Admin Controller, Admin CMS Vue Page, Homepage CMS Dynamic Binding, Wajib Pajak Form Revamp)  
**Status**: ✅ Completed & Verified via Browser Automation

---

## 1. Executive Summary

This release introduces two major features to the KB Samsat Lamongan platform:
1. **Revamp Halaman Wajib Pajak (`/wajib-pajak`)**:
   - Modernized and compacted the user intake form for the AI Chat launcher.
   - Unified design system matching the homepage: deep navy background, government multi-logo top bar, compact glassmorphic card, crisp inputs with icons, and red CTA button.
2. **Comprehensive CMS for Homepage & Footer (`/admin/cms`)**:
   - Built a dynamic content management system storing all text, descriptions, images, schedules, map queries, payment platforms, and footer metadata in MySQL with Redis/File caching.
   - Provided an intuitive multi-tab admin interface for editing all sections without touching source code.
   - Created `HomepageContentSeeder` for one-click production environment seeding.

---

## 2. Architecture & Database Design

### 2.1 Database Migration (`2026_08_16_094500_create_homepage_contents_table.php`)
Table `homepage_contents`:
- `id` (bigint auto-increment)
- `section` (string: `hero`, `services`, `schedules`, `salma`, `payment`, `contact`, `footer`)
- `key` (string, unique index)
- `value` (longText / JSON)
- `type` (string: `text`, `textarea`, `json`, `image`, `boolean`)
- `label` (string nullable)
- `description` (text nullable)
- `timestamps`

### 2.2 Eloquent Model (`App\Models\HomepageContent`)
- **Automatic Cache Invalidation**: `saved` and `deleted` model events automatically purge `homepage_contents_grouped` and `homepage_contents_all` cache tags.
- **`getAllGrouped()`**: Retrieves all records in a single query, decodes JSON structures and booleans, and caches results for 1 hour (3600s) for zero-latency homepage rendering.
- **`getAdminGrouped()`**: Formats records grouped by section for the Admin CMS form.

### 2.3 Database Seeder (`Database\Seeders\HomepageContentSeeder`)
Seeds 25+ comprehensive configuration keys matching the production state of KB Samsat Lamongan:
- **Hero**: `hero_badge`, `hero_title`, `hero_subtitle`, `hero_cta_primary_text`, `hero_cta_primary_target`, `hero_cta_secondary_text`, `hero_cta_secondary_target`, `hero_backgrounds` (JSON array of 3 image URLs).
- **Layanan**: `services_badge`, `services_title`, `services_title_highlight`, `services_desc`, `services_list` (JSON array of 6 service cards).
- **Jadwal & Lokasi**: `schedules_badge`, `schedules_title`, `schedules_title_highlight`, `schedules_desc`, `keliling_schedules` (JSON array of 6 days with locations), `layanan_menetap` (JSON array of 5 payment points), `belok_wangi_schedules` (JSON array of 3 night schedules).
- **SALMA AI**: `salma_badge`, `salma_title`, `salma_full_name`, `salma_desc`, `salma_features` (JSON array of 4 features), `salma_mascot_image`, `salma_cta_text`.
- **Pembayaran**: `payment_badge`, `payment_title`, `payment_title_highlight`, `payment_desc`, `payment_categories` (JSON array of categories and platforms).
- **Kontak**: `contact_badge`, `contact_title`, `contact_title_highlight`, `contact_address`, `contact_city_postal`, `contact_hours_weekday`, `contact_hours_friday`, `contact_phone`, `contact_help_card_title`, `contact_help_card_desc`.
- **Footer**: `footer_agency_name`, `footer_agency_sub`, `footer_agency_desc`, `footer_social_links`, `footer_copyright_text`.

---

## 3. Backend Implementation

### 3.1 Admin CMS Controller (`App\Http\Controllers\Admin\CmsController`)
- `index()`: Returns Inertia view `Admin/Cms/Index` with grouped contents and stats.
- `update(Request $request)`: Batch updates key-value pairs and automatically JSON-encodes array objects.
- `uploadImage(Request $request)`: Validates images (up to 5MB: jpeg, png, jpg, gif, svg, webp) and uploads to `public/images/cms/`.
- `resetDefaults()`: Calls `HomepageContentSeeder` programmatically to restore default configurations.

### 3.2 Web Routes (`routes/web.php`)
- `GET /`: Loads `$cms = HomepageContent::getAllGrouped()` and passes it to `Welcome.vue`.
- `GET /admin/cms`: CMS Management view (Auth & Verified middleware).
- `POST /admin/cms/update`: Batch update endpoint.
- `POST /admin/cms/upload-image`: Image upload endpoint.
- `POST /admin/cms/reset-defaults`: Default reset endpoint.

---

## 4. Frontend Implementation

### 4.1 Admin CMS Interface (`resources/js/Pages/Admin/Cms/Index.vue`)
- Built with Vuetify 3 components, tabs navigation, responsive layouts, and clean form controls.
- **7 Section Tabs**:
  1. 🖼️ **Hero Banner Slider**: Text inputs, live image previews, dynamic add/remove slide URLs, file upload button.
  2. ⭐ **Layanan Unggulan**: Card management with color picker, MDI icon, title, description.
  3. 📅 **Jadwal & Lokasi Peta**: Interactive schedule editor with day-by-day add/remove location buttons, payment point editor, and night schedule editor.
  4. 🤖 **SALMA AI Showcase**: Title, full name, description, 4 features editor, mascot image upload, CTA text.
  5. 💳 **Pembayaran Digital**: Payment categories & platform logo URLs management.
  6. 📞 **Kontak & Jam Operasional**: Office address, operational hours, phone, and help card.
  7. 🏛️ **Footer & Branding**: Agency profile, social media links, copyright text.
- Integrated into `AppLayout.vue` sidebar navigation (`CMS Beranda & Footer`).

### 4.2 Dynamic Homepage Binding (`resources/js/Pages/Welcome.vue`)
- Bound all 7 sections to computed properties sourcing from `props.cms` with fallback defaults.
- Live data reactivity: editing any content in `/admin/cms` immediately updates the public homepage upon saving.

### 4.3 Wajib Pajak Page Revamp (`resources/js/Pages/WajibPajak/Form.vue`)
- **Government Top Bar**: Official logo cluster (Bapenda Jatim, Pemprov Jatim, Polda Jatim, Jasa Raharja), back button to `/`.
- **Card Aesthetics**: Sleek 480px max-width card with clean avatar header and privacy badge.
- **Input Styling**: Focused border highlights, auto-formatted uppercase Nopol (`S 1234 ZZ`), clear validation messages.
- **Existing Session Handler**: Compact green status box allowing instant chat continuation or data update.

---

## 5. Verification & Testing

| Test Suite | Scenario | Result |
|------------|----------|--------|
| **Database Migration** | `php artisan migrate` | ✅ Ran `2026_08_16_094500_create_homepage_contents_table` (Done) |
| **Database Seeding** | `php artisan db:seed --class=HomepageContentSeeder` | ✅ Seeded all 25+ keys |
| **Asset Compilation** | `npm run build` (Client + SSR bundle) | ✅ 0 errors, built in 17.77s |
| **Wajib Pajak Layout** | Browser inspection at `/wajib-pajak` | ✅ Compact card, logos, inputs, and button rendered perfectly |
| **CMS Administration** | Login at `/login` and manage `/admin/cms` | ✅ All 7 tabs functional |
| **Live CMS Update Test**| Changed `hero_badge`, saved, checked `/`, reverted | ✅ Live update verified on homepage |

---

## 6. Artifact References
- Recording: `verify_wajib_pajak_and_cms_1786848520271.webp`
- Screenshots:
  - `wajib_pajak_page_1786848548082.png`
  - `cms_panel_top_1786848674938.png`
  - `homepage_badge_updated_1786848835324.png`
  - `homepage_badge_reverted_1786849110639.png`
