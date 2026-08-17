# Dokumentasi Perubahan: Penguatan Keamanan, Honeypot Anti-Spam & Perbaikan Error 419 CSRF Produksi

**Tanggal & Waktu:** 17 Agustus 2026, 08:10 WIB  
**Modul/Area Terkait:**
- Kernel & Middleware Laravel 11 (`bootstrap/app.php`)
- Frontend Bootstrap & Axios Client (`resources/js/bootstrap.js`)
- Form Wajib Pajak & Honeypot (`WajibPajak/Form.vue`, `WajibPajakController.php`)
- Form Login & Obfuscasi URL Admin (`Auth/Login.vue`, `FortifyServiceProvider.php`, `config/auth.php`)
- Rute Proteksi API Admin (`routes/api.php`, `routes/web.php`)
- Security Headers Middleware (`App\Http\Middleware\SecurityHeadersMiddleware.php`)

---

## 1. Ringkasan Eksekutif & Analisis Akar Masalah (Root Cause)

### A. Mengapa Error 419 (Page Expired / CSRF Token Mismatch) Sering Terjadi di Server Produksi?
Berdasarkan investigasi mendalam terhadap log dan tangkapan layar konsol (`Failed to load resource: 419` pada endpoint AJAX seperti `/knowledge-base/117/score` dan `/cms/update`):
1. **Reverse Proxy / Cloudflare / cPanel HTTPS Mismatch**:
   Pada server cPanel atau Cloudflare, permintaan masuk melalui reverse proxy Nginx/Apache. Tanpa konfigurasi `$middleware->trustProxies(at: '*')`, fungsi `request()->isSecure()` pada Laravel membaca koneksi sebagai plain HTTP. Hal ini menyebabkan cookie sesi dan header CSRF tidak dikirimkan secara sinkron atau ditolak oleh browser yang berada di halaman HTTPS.
2. **Axios XSRF Token Handling**:
   Axios pada frontend SPA/Inertia membutuhkan opsi `withXSRFToken = true` agar otomatis membaca cookie `XSRF-TOKEN` terenkripsi dan melampirkan header `X-XSRF-TOKEN` di setiap request POST/PUT/DELETE. Jika tidak disetel, Axios hanya mengandalkan nilai statis `meta[name="csrf-token"]` saat pertama kali halaman dimuat. Jika sesi ter-refresh atau idle, request AJAX berikutnya langsung gagal dengan status 419.
3. **Ketiadaan Mekanisme Self-Healing 419**:
   Ketika request AJAX gagal 419, frontend sebelumnya tidak memiliki interceptor pemulihan otomatis, menyebabkan error unhandled di konsol dan pesan "Page Expired".

---

## 2. Rincian Solusi & Perubahan yang Diimplementasikan

### A. Perbaikan Permanen Error 419 & Auto-Healing CSRF
1. **`bootstrap/app.php`**:
   - Menambahkan `$middleware->trustProxies(at: '*');` agar Laravel mengenali header SSL/HTTPS asli dari reverse proxy cPanel, Nginx, dan Cloudflare.
   - Menambahkan penanganan khusus exception status 419 untuk me-refresh token secara anggun jika request berbasis JSON/Inertia.
2. **`routes/web.php`**:
   - Mendaftarkan endpoint baru: `GET /refresh-csrf` yang mengembalikan `{ success: true, csrf_token: '...' }` dan memperbarui cookie `XSRF-TOKEN`.
3. **`resources/js/bootstrap.js`**:
   - Mengaktifkan `window.axios.defaults.withCredentials = true;`.
   - Mengaktifkan `window.axios.defaults.withXSRFToken = true;`.
   - Menambahkan **Axios Response Interceptor (Self-Healing Queue)**:
     - Jika sebuah request AJAX menerima status `419`, interceptor secara transparan memanggil `GET /refresh-csrf`, memperbarui header `X-CSRF-TOKEN` dan meta tag, lalu me-retry request yang gagal secara otomatis tanpa mengganggu pengguna.

---

### B. Fitur Obfuscasi URL Login Admin (`ADMIN_LOGIN_PATH`)
Untuk mencegah bot scanner mencari endpoint `/login` atau `/admin` secara brute-force:
1. **`config/auth.php` & `.env.example`**:
   - Menambahkan opsi konfigurasi `'custom_login_path' => env('ADMIN_LOGIN_PATH', null)`.
   - Jika disetel di `.env` (contoh: `ADMIN_LOGIN_PATH=portal-samsat-auth-8x9q`):
     - Rute `GET /login` biasa otomatis diblokir dan mengembalikan **`404 Not Found`** bagi bot.
     - Form login hanya dapat diakses melalui URL rahasia `https://samsatlamongan.com/portal-samsat-auth-8x9q`.
2. **`app/Providers/FortifyServiceProvider.php` & `routes/web.php`**:
   - Mengintegrasikan pemeriksaan `Fortify::loginView(...)` dengan fallback 404 dinamis.

---

### C. Honeypot Anti-Spambot pada Form Publik & Login
Bot otomatis di internet memindai DOM dan mengisi seluruh input yang ada. Kami menerapkan teknik **Invisible Honeypot**:
1. **`resources/js/Pages/WajibPajak/Form.vue` & `WajibPajakController.php`**:
   - Menyisipkan input tersembunyi `website_verification` yang tidak terlihat oleh manusia (`opacity: 0; pointer-events: none;`).
   - Pada `WajibPajakController::startChatSession()`, jika kolom honeypot ini terisi, request ditolak seketika (`422 Unprocessable Entity`), memblokir spambot sebelum menyentuh OpenAI API atau database.
2. **`resources/js/Pages/Auth/Login.vue` & `FortifyServiceProvider.php`**:
   - Menyisipkan input honeypot pada form login dan memvalidasinya di `Fortify::authenticateUsing(...)`.

---

### D. Security Headers Middleware & Penguncian API Admin
1. **`app/Http/Middleware/SecurityHeadersMiddleware.php`**:
   - `X-Frame-Options: SAMEORIGIN` (Perlindungan Clickjacking).
   - `X-Content-Type-Options: nosniff` (Perlindungan MIME sniffing).
   - `Referrer-Policy: strict-origin-when-cross-origin`.
   - `Permissions-Policy: camera=(), microphone=(), geolocation=()`.
   - Didaftarkan secara global ke grup middleware `web` di `bootstrap/app.php`.
2. **`routes/api.php`**:
   - Mengunci rute analitik dan riwayat obrolan admin (`/api/admin/analytics/*` dan `/api/admin/chat-history/*`) menggunakan middleware `['auth:sanctum']`.

---

## 3. Hasil Pengujian & Verifikasi

- [x] **Vite Compilation (`npm run build`)**: Berhasil dikompilasi 100% tanpa error.
- [x] **Endpoint Refresh CSRF (`/refresh-csrf`)**: Mengembalikan token CSRF valid dan respons JSON `{ success: true }`.
- [x] **Form Wajib Pajak (`/wajib-pajak`)**: Pendaftaran data identitas berhasil, honeypot terpasang transparan, dan otomatis redirect ke `/customer-service`.
- [x] **Form Login (`/login`)**: Renders sempurna dengan proteksi honeypot dan dukungan URL kustom.
- [x] **Security Headers**: Terpasang otomatis pada setiap respons HTTP.
