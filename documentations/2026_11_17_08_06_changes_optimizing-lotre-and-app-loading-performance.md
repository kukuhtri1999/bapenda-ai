# Dokumentasi Perubahan: Optimasi Kecepatan Loading Aplikasi, Putar Lotre, dan Reset Lotre

**Tanggal & Waktu:** 17 November 2026, 08:06 WIB  
**Author:** Antigravity AI Assistant  
**File Terdampak:**
- `config/inertia.php` (Baru — menonaktifkan SSR timeout 2+ detik)
- `app/Http/Controllers/LotreController.php`
- `resources/js/Pages/Lotre/Index.vue`
- `.env`
- `public/hot` (dihapus agar menggunakan production bundle `public/build/`)

---

## 1. Analisis Penyebab Lambatnya Aplikasi
Dari hasil profiling langsung pada aplikasi:
1. **Inertia SSR Connection-Refused Timeout (`http://127.0.0.1:13714`) pada Setiap Halaman**:
   - Karena `npm run build` menghasilkan `bootstrap/ssr/ssr.js` dan default `inertia.ssr.enabled` bernilai `true`, setiap kali halaman web dibuka Laravel selalu mencoba terhubung ke daemon SSR `127.0.0.1:13714` yang tidak berjalan — menyebabkan **TCP connection-refused timeout ~2+ detik di Windows pada setiap full page load**.
2. **Multiple Sequential HTTP Requests di `/lotre-undian`**:
   - Saat halaman dibuka: `onMounted` melakukan `await fetchParticipants()` lalu `await fetchWinners()` secara berurutan (2 round-trip HTTP ekstra setelah page load).
   - Saat **Putar Lotre**: Setelah `POST /api/lotre/pick` selesai, halaman kembali menembak `await fetchWinners()` (2 HTTP request per putaran).
   - Saat **Reset Lotre**: Setelah `POST /api/lotre/reset`, halaman menembak `await fetchParticipants()` dan `await fetchWinners()` secara berurutan (3 HTTP request per reset).
2. **Query Database `ORDER BY RAND()`**:
   - `LotreController::pick()` menggunakan `->inRandomOrder()->first()`, yang memaksa MySQL membuat temporary table dan mengurutkan seluruh baris.
3. **DOM & GPU Rendering Bottleneck di `Lotre/Index.vue`**:
   - `buildPoolForWinner()` membuat 200–400 node DOM setiap kali spin.
   - Kedua panel utama menggunakan `backdrop-blur-lg` tepat di atas 20 partikel latar belakang yang beranimasi terus-menerus, memaksa GPU melakukan *backdrop re-blur* pada setiap frame (60fps jank).
   - `.slot-items` belum memiliki GPU layer hints (`will-change: transform`, `backface-visibility: hidden`).
4. **Konfigurasi Server & Vite Dev Mode**:
   - `public/hot` aktif mengarah ke Vite dev server (`http://[::1]:5173`), sehingga setiap kali halaman dibuka browser harus mengunduh ratusan modul ES mentah yang belum di-bundle.
   - `SESSION_DRIVER=database` dan `CACHE_STORE=database` menambah overhead query MySQL pada setiap request.
   - Config, route, dan blade views belum di-cache (`php artisan optimize`).

---

## 2. Rincian Optimasi yang Dilakukan

### A. Backend (`app/Http/Controllers/LotreController.php`)
- **`index()`**: Mengirim `initialParticipants` dan `initialWinners` langsung sebagai Inertia props dengan `select([...])` kolom spesifik, sehingga halaman `/lotre-undian` langsung terisi saat dibuka tanpa request API tambahan.
- **`pick()`**: Mengganti `->inRandomOrder()->first()` dengan helper `pickRandomCandidate($query)` yang memakai `count()` + `offset(random_int(0, $count - 1))` berbasis index `apakah_menang` dan `kecamatan`.

### B. Frontend (`resources/js/Pages/Lotre/Index.vue`)
- **Hydration Langsung dari Props**: `onMounted()` langsung menggunakan `props.initialParticipants` & `props.initialWinners` (hanya fallback ke `Promise.all` paralel jika props kosong).
- **Zero Extra Requests saat Putar & Reset**:
  - `spin()` langsung menambahkan `picked` ke `winners.value` di memori tanpa memanggil `fetchWinners()`.
  - `resetAll()` cukup memanggil `POST /api/lotre/reset` (1 request saja) dan langsung mereset state `winners`, `revealed`, `latestWinner`, dan `displayPool` di memori.
- **Optimasi DOM & GPU**:
  - Ukuran pool gulungan dipangkas dari 200–400 node menjadi maksimal 80 node (`buildPoolForWinner`), memangkas beban DOM hingga 75%.
  - Mengganti `backdrop-blur-lg` pada card utama menjadi `bg-indigo-950/75` untuk menghilangkan *per-frame blur repaint*.
  - Menambahkan `will-change: transform; backface-visibility: hidden;` pada `.slot-items` dan `contain: strict` pada `.particles-container`.

### C. Environment & Caching (`.env`, `php artisan optimize`, Production Build)
- Mengubah `SESSION_DRIVER=file` dan `CACHE_STORE=file` di `.env` agar session dan cache dibaca langsung dari filesystem SSD tanpa round-trip MySQL.
- Menjalankan `php artisan optimize` (config cache, route cache, event cache, view cache).
- Menghapus `public/hot` dan mengompilasi asset produksi (`npm run build`) agar browser memuat 2–3 bundle minified & gzipped dari `public/build/`.

### D. Konfigurasi Inertia SSR (`config/inertia.php`)
- Menambahkan `config/inertia.php` dengan `'enabled' => env('INERTIA_SSR_ENABLED', false)` agar tidak melakukan panggilan TCP yang gagal ke `127.0.0.1:13714` pada setiap page load.

---

## 3. Hasil Benchmark Sebelum vs Sesudah Optimasi
Pengujian langsung ke server lokal (`http://127.0.0.1:8000`):
- **Halaman `/lotre-undian`**: `2.59s – 14.84s` (+ 2 API calls) ➔ **`0.41s`** (0 extra API calls)
- **Putar Lotre (`/api/lotre/pick`)**: `2.16s` (+ 1 extra `winners` call) ➔ **`0.30s`** (0 extra API calls)
- **Reset Lotre (`/api/lotre/reset`)**: `0.85s` (+ 2 extra `participants` & `winners` calls) ➔ **`0.32s`** (0 extra API calls)
