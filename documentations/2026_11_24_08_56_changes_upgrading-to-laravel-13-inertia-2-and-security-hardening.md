# Dokumentasi Perubahan: Upgrade ke Laravel 13, Inertia v2, Vue 3.5+, & Hardening Keamanan/Performa

**Tanggal & Waktu:** 24 November 2026, 08:56 WIB  
**Author:** Antigravity AI Assistant  
**File Terdampak:**
- `composer.json` & `composer.lock`
- `package.json` & `package-lock.json`
- `app/Http/Middleware/SecurityHeadersMiddleware.php`
- `phpunit.xml`

---

## 1. Upgrade Framework & Ecosystem (Laravel 12 ➔ Laravel 13)
1. **Laravel Framework**:
   - Diperbarui dari `v12.73.0` ➔ **`v13.43.0`** (`composer.json`: `"laravel/framework": "^13.0"`).
   - Memerlukan runtime minimum **PHP 8.3+** (sudah berjalan di **PHP 8.3.37** yang menambal PHP2Shell & PHPAfterShell).
2. **Laravel Tinker**:
   - Diperbarui dari `v2.11.1` ➔ **`v3.2.0`** (`"laravel/tinker": "^3.0"`).
3. **Inertia.js Stack (Sinkronisasi Versi Mayor v2)**:
   - Backend `inertiajs/inertia-laravel`: `v1.3.4` ➔ **`v2.0.28`** (`^2.0`).
   - Frontend `@inertiajs/vue3`: `^1.0.14` ➔ **`^2.0`** agar generasi protokol client & server 100% cocok untuk Laravel 13.
4. **Dependency Tambahan yang Ikut Diperbarui**:
   - `brick/math` (`0.14.8 => 1.0.0`), `guzzlehttp/uri-template` (`v1.0.11 => v2.0.1`), `symfony/polyfill-php86` (`v1.43.0`).

---

## 2. Hardening Keamanan (Backend, Headers, & Frontend NPM)
1. **Security Headers ([SecurityHeadersMiddleware.php](file:///c:/laragon/www/bapenda-ai/app/Http/Middleware/SecurityHeadersMiddleware.php))**:
   - Menambahkan header **`Strict-Transport-Security: max-age=31536000; includeSubDomains`** secara otomatis ketika aplikasi berjalan di `production` melalui koneksi HTTPS (`app()->isProduction() && $request->isSecure()`).
   - Tetap mempertahankan `X-Frame-Options: SAMEORIGIN`, `X-Content-Type-Options: nosniff`, `Referrer-Policy: strict-origin-when-cross-origin`, dan `Permissions-Policy`.
2. **Patch Keamanan Frontend (`npm audit fix` & Upgrade Vue)**:
   - Menambal kerentanan pada paket produksi termasuk `axios` (DoS/SSRF/Prototype Pollution), `vue` & `@vue/server-renderer` (diperbarui ke `3.5+` untuk menambal XSS `GHSA-g2v6-rqmx-r4w6`), `quill` / `@vueup/vue-quill`, `qs`, `postcss`, `rollup`, dan `yaml`.
   - Hasil `npm audit --omit=dev` (runtime produksi): **0 kerentanan runtime** (sisa advisory hanya berada pada dev/build tools seperti `esbuild`/`minimatch`/`postcss-selector-parser`).
3. **Isolasi Database Pengujian ([phpunit.xml](file:///c:/laragon/www/bapenda-ai/phpunit.xml))**:
   - Mengaktifkan `<env name="DB_CONNECTION" value="mysql"/>` dan `<env name="DB_DATABASE" value="bapenda_ai_testing"/>` agar `php artisan test` (`RefreshDatabase`) tidak pernah menimpa database aplikasi utama `bapenda-ai`.

---

## 3. Optimasi Performa & Verifikasi
1. **Laravel Optimization Caches**:
   - Menjalankan `php artisan optimize:clear` lalu `php artisan optimize` (`config`, `events`, `routes`, `views` cached) pada **Laravel 13.43.0** + **PHP 8.3.37 + Zend OPcache**.
2. **Vite Production Build (`npm run build`)**:
   - Client bundle & SSR bundle terkompilasi bersih dengan Inertia v2 + Vue 3.5+ (`exit code 0`).
3. **Full Test Suite (`php artisan test`)**:
   - **23 passed (48 assertions)** di seluruh Unit Tests (`MarkdownHandlingAndSecurityTest`, `MultiTurnConversationalTest`, `ExampleTest`) & Feature Tests (`ApiTokenPermissionsTest`, `BrowserSessionsTest`, `CreateApiTokenTest`, `DeleteAccountTest`, `DeleteApiTokenTest`, `ExampleTest`, `ProfileInformationTest`, `TwoFactorAuthenticationSettingsTest`, `UpdatePasswordTest`).
