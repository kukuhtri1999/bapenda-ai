# Perubahan Branding Logo Tunggal Samsat Lamongan & Perbaikan Spacing Header

- **Tanggal/Waktu**: 2026-11-24 09:10 WIB
- **Scope**: Frontend UI/UX, Konfigurasi Aplikasi (`.env`), Aset Branding Publik

## 1. Ringkasan Perubahan
1. **Menghilangkan Celah (Gap) antara Announcement Bar dan Header Navbar**:
   - Sebelumnya `.gov-announcement-bar` (`position: fixed; top: 0`) dan `.gov-navbar` (`position: fixed`) diposisikan terpisah dengan offset statis `.gov-navbar--with-bar { top: 32px !important; }` (desktop) dan `top: 24px !important;` (mobile <= 640px), sehingga menimbulkan celah 2–4px di desktop saat tinggi rendered announcement bar berbeda sedikit.
   - Keduanya dibungkus ke dalam container `<div class="gov-header-stack">` (`position: fixed; top: 0; left: 0; right: 0; z-index: 105; display: flex; flex-direction: column;`), lalu `.gov-announcement-bar` dan `.gov-navbar` diubah ke `position: relative; width: 100%;` agar menempel rapat (0px gap) di seluruh ukuran layar (desktop, tablet, mobile).
2. **Menambah Tinggi Header (~20px lebih lega)**:
   - `.gov-navbar__inner` dinaikkan menjadi `min-height: 70px; padding: 14px 20px; gap: 12px;` di desktop (dari sebelumnya ~50px dengan `padding: 10px 18px`) dan `min-height: 58px; padding: 10px 14px;` di layar mobile (`<= 600px`).
   - Padding atas hero `.hero-slider__content` disesuaikan ke `112px` (desktop) dan `92px` (mobile `<= 600px`) agar konten hero tidak tertutup header yang lebih tinggi.
3. **Penggantian 4 Logo Terpisah Menjadi 1 Logo Tunggal Kantor Bersama Samsat Lamongan (+20% lebih besar)**:
   - Menyalin aset emblem bulat Kantor Bersama Samsat Lamongan ke `public/images/logo-samsat-lamongan.png`.
   - Mengganti konfigurasi `APP_LOGO` dan `APP_ICON` di `.env` dan `.env.example` ke `/images/logo-samsat-lamongan.png`.
   - Memperbarui seluruh komponen dan halaman agar menampilkan logo tunggal baru dengan ukuran diperbesar minimal ~20%:
     - `resources/js/Pages/Welcome.vue`: Header navbar menggunakan `.gov-navbar__logo-img` (`42x42` desktop, `36x36` mobile) dan Footer menggunakan `.footer-logo-badge` (`54x54` bulat) dengan `VImg` `46x46`.
     - `resources/js/Components/ApplicationMark.vue` & `resources/js/Layouts/AppLayout.vue`: Sidebar admin logo diperbesar dari `40x40` (`h-10`) menjadi `48x48` (`h-12`).
     - `resources/js/Components/AuthenticationCardLogo.vue`: Logo card auth diperbesar dari `60px` menjadi `72x72`.
     - `resources/js/Layouts/AuthLayout.vue`: 4 `VImg` (`40x40`) diganti 1 `VImg` (`60x60`).
     - `resources/js/Pages/WajibPajak/Form.vue`: 4 logo (`32x32`) diganti 1 logo (`40x40`).
     - `resources/js/Pages/Lotre/Index.vue`: Cluster logo (`60x60`) diganti 1 logo (`72x72`).

## 2. Daftar File yang Diubah
- [public/images/logo-samsat-lamongan.png](file:///c:/laragon/www/bapenda-ai/public/images/logo-samsat-lamongan.png) (baru)
- [.env](file:///c:/laragon/www/bapenda-ai/.env) & [.env.example](file:///c:/laragon/www/bapenda-ai/.env.example)
- [resources/js/Pages/Welcome.vue](file:///c:/laragon/www/bapenda-ai/resources/js/Pages/Welcome.vue)
- [resources/js/Components/ApplicationMark.vue](file:///c:/laragon/www/bapenda-ai/resources/js/Components/ApplicationMark.vue)
- [resources/js/Layouts/AppLayout.vue](file:///c:/laragon/www/bapenda-ai/resources/js/Layouts/AppLayout.vue)
- [resources/js/Components/AuthenticationCardLogo.vue](file:///c:/laragon/www/bapenda-ai/resources/js/Components/AuthenticationCardLogo.vue)
- [resources/js/Layouts/AuthLayout.vue](file:///c:/laragon/www/bapenda-ai/resources/js/Layouts/AuthLayout.vue)
- [resources/js/Pages/WajibPajak/Form.vue](file:///c:/laragon/www/bapenda-ai/resources/js/Pages/WajibPajak/Form.vue)
- [resources/js/Pages/Lotre/Index.vue](file:///c:/laragon/www/bapenda-ai/resources/js/Pages/Lotre/Index.vue)

## 3. Verifikasi
- `npm run build` (termasuk Vite SSR bundle): Sukses tanpa error.
- `php artisan config:cache` (PHP 8.3.37): Konfigurasi `APP_LOGO`/`APP_ICON` berhasil di-cache ulang.
- Uji HTTP ke `/` dan `/images/logo-samsat-lamongan.png`: Keduanya merespons `HTTP 200`.
