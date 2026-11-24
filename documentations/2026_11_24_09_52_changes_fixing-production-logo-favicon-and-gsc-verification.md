# Perbaikan Logo Production, Regenerasi Favicon/PWA Icons, dan Google Site Verification

- **Tanggal/Waktu**: 2026-11-24 09:52 WIB
- **Scope**: Production Branding Fix, Favicon/PWA Icons, Blade Head Metadata

## 1. Analisis Akar Masalah (Troubleshooting)
1. **Logo Production Masih Menampilkan `/images/logo-bapenda-jatim.png`**:
   - Komponen Vue (`Welcome.vue`, `ApplicationMark.vue`, `AuthenticationCardLogo.vue`, `AppLayout.vue`, `AuthLayout.vue`, `Lotre/Index.vue`, `WajibPajak/Form.vue`) sebelumnya membaca `import.meta.env.VITE_APP_LOGO` sebagai prioritas utama sebelum fallback `'/images/logo-samsat-lamongan.png'`.
   - Karena `.env` berada di `.gitignore` dan `public/build` juga di-ignore oleh Git, ketika kode di-push ke production cPanel lalu menjalankan `npm run build`, Vite membaca `VITE_APP_LOGO` dari `.env` production yang masih berisi `/images/logo-bapenda-jatim.png`.
   - Selain itu, Workbox Service Worker (`vite.config.js`) menggunakan `CacheFirst` pada `images-cache` selama 7 hari sehingga aset lama ikut ter-cache di browser klien.
2. **Icon Web (Favicon & PWA Icons) Masih Memakai Icon Lama**:
   - Seluruh berkas ikon di `public/icons/icon-*.png` (`72x72` s/d `512x512` dan varian `maskable`) masih berisi gambar logo lama, sementara `public/favicon.ico` berukuran `0 byte`.
   - Pada `resources/views/app.blade.php`, tag `<link rel="icon" type="image/x-icon" href="{{ asset(env('APP_ICON')) }}">` memakai `env()` langsung di Blade yang mengembalikan `null` saat `php artisan config:cache` aktif, sehingga browser fallback ke `/icons/icon-96x96.png` yang masih lama.

## 2. Perubahan yang Dilakukan
1. **Fix Referensi Logo di Seluruh Komponen Vue + Cache-Busting**:
   - Mengubah `logoUrl` langsung ke `'/images/logo-samsat-lamongan.png?v=20261124'` (tidak bergantung pada `.env` production yang belum sempat diubah) pada:
     - [resources/js/Pages/Welcome.vue](file:///c:/laragon/www/bapenda-ai/resources/js/Pages/Welcome.vue)
     - [resources/js/Components/ApplicationMark.vue](file:///c:/laragon/www/bapenda-ai/resources/js/Components/ApplicationMark.vue)
     - [resources/js/Components/AuthenticationCardLogo.vue](file:///c:/laragon/www/bapenda-ai/resources/js/Components/AuthenticationCardLogo.vue)
     - [resources/js/Layouts/AppLayout.vue](file:///c:/laragon/www/bapenda-ai/resources/js/Layouts/AppLayout.vue)
     - [resources/js/Layouts/AuthLayout.vue](file:///c:/laragon/www/bapenda-ai/resources/js/Layouts/AuthLayout.vue)
     - [resources/js/Pages/WajibPajak/Form.vue](file:///c:/laragon/www/bapenda-ai/resources/js/Pages/WajibPajak/Form.vue)
     - [resources/js/Pages/Lotre/Index.vue](file:///c:/laragon/www/bapenda-ai/resources/js/Pages/Lotre/Index.vue)
   - Menaikkan `cacheName` runtime image cache di [vite.config.js](file:///c:/laragon/www/bapenda-ai/vite.config.js) dari `images-cache` ke `images-cache-v2` dan memperbarui regex URL agar cocok juga dengan query parameter `?v=...`.
2. **Regenerasi Seluruh Favicon & PWA Icons dari Logo Baru**:
   - Menghasilkan ulang seluruh ukuran di `public/icons/` (`72`, `96`, `128`, `144`, `152`, `180`, `192`, `384`, `512`, plus `192x192-maskable` dan `512x512-maskable` dengan safe-zone putih 82%) serta membuat [public/favicon.ico](file:///c:/laragon/www/bapenda-ai/public/favicon.ico) multi-resolusi (16×16, 32×32, 48×48; `10.499 byte`) dari `public/images/logo-samsat-lamongan.png`.
3. **Update `config/app.php` & `resources/views/app.blade.php` + Google Site Verification**:
   - Menambahkan konfigurasi `'logo'` dan `'icon'` di [config/app.php](file:///c:/laragon/www/bapenda-ai/config/app.php).
   - Memperbarui [resources/views/app.blade.php](file:///c:/laragon/www/bapenda-ai/resources/views/app.blade.php):
     - Menambahkan `<meta name="google-site-verification" content="U2b8LtcV2OaWO0Fhlwk75pIquUB_N4b38iFUgt1oZNc" />`.
     - Mengganti tag favicon & Apple Touch Icon ke `/favicon.ico?v=20261124` dan `/icons/icon-*.png?v=20261124`.
     - Memperbarui fallback `meta description` dan `og:title`/`og:description`/`og:image`.

## 3. Verifikasi
- `npm run build` (`vite build && vite build --ssr`) sukses membangun bundle produksi; hasil bundle `public/build/assets/Welcome-*.js` memuat `logo-samsat-lamongan` dan tidak lagi mengandung `logo-bapenda-jatim`.
- `php artisan config:cache` sukses.
- Pemeriksaan HTTP pada `http://127.0.0.1:8000/` (`200`), `/favicon.ico?v=20261124` (`200`, `10499` bytes), `/icons/icon-96x96.png?v=20261124` (`200`), serta keberadaan tag `<meta name="google-site-verification" ...>` terkonfirmasi lolos.
