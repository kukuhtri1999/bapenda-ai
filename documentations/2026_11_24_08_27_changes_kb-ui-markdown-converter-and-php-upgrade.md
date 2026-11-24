# Dokumentasi Perubahan: KB Markdown Converter, Redesign Tabel KB Compact, & Upgrade Keamanan PHP 8.3.37

**Tanggal & Waktu:** 24 November 2026, 08:27 WIB  
**Author:** Antigravity AI Assistant  
**File Terdampak:**
- `resources/js/utils/markdownToRichHtml.js` (Baru)
- `resources/js/Pages/KnowledgeBase/Create.vue` (Diperbarui)
- `resources/js/Pages/KnowledgeBase/Edit.vue` (Diperbarui)
- `resources/js/Pages/KnowledgeBase/Index.vue` (Diperbarui)
- `resources/js/Pages/Chat/Index.vue` (Diperbarui)
- `app/Models/KnowledgeBase.php` (Diperbarui)
- `app/Services/SalmaPromptService.php` (Diperbarui)
- `app/Console/Commands/SecurityCheckCommand.php` (Baru)
- `composer.json` & `composer.lock` (Diperbarui)
- `tests/Unit/MarkdownHandlingAndSecurityTest.php` (Baru)
- Lingkungan PHP Lokal: `C:\laragon\bin\php\php-8.3.37-Win32-vs16-x64` & `C:\laragon\etc\apache2\mod_php.conf`

---

## 1. Fitur Konversi Raw Markdown → Rich Text di Create & Edit KB

### Latar Belakang
Sebagian artikel Knowledge Base diisi dengan menyalin (*paste*) teks berformat Markdown mentah (misal `### 1. ...`, `**Teks Tebal**`, `* item`, beserta penanda kutipan `[cite: 42, 114]`) ke dalam editor Quill, sehingga tampil mentah tanpa heading/bold/bullet.

### Implementasi
1. **Shared Utility (`resources/js/utils/markdownToRichHtml.js`)**:
   - Fungsi `convertMarkdownToRichHtml(content)`:
     - Mengekstrak baris teks secara bersih dari wrapper `<p>` / `<br>` milik Quill.
     - Mengonversi heading `#`, `##`, `###` menjadi tag HTML semantik `<h1>`, `<h2>`, `<h3>`.
     - Mengonversi `**tebal**` / `__tebal__` ➔ `<strong>`, `*miring*` ➔ `<em>`, `` `kode` `` ➔ `<code>`, `~~coret~~` ➔ `<s>`, `[link](url)` ➔ `<a target="_blank">`, dan blockquote `>` ➔ `<blockquote>`.
     - Mengonversi daftar tak berurut (`*`, `-`, `•`) menjadi `<ul><li>...</li></ul>` dan daftar bernomor (`1.`) menjadi `<ol><li>...</li></ol>`.
     - Mengonversi baris tabel Markdown (`| col1 | col2 |`) menjadi daftar berformat rapi (mengingat tema Snow Quill v1 tidak mendukung `<table>` secara native).
     - Menghapus penanda sitasi `[cite: ...]` secara otomatis melalui `stripCitationMarkers()`.
   - Helper `useMarkdownConverter(refFn, form, quillEditorRef, toast)` yang digunakan bersama oleh `Create.vue` dan `Edit.vue` (zero code duplication), lengkap dengan fitur **Undo** sekali klik.
2. **UI Editor (`KnowledgeBase/Create.vue` & `Edit.vue`)**:
   - Menambahkan tombol **`Konversi Markdown → Format Rapi`** dan tombol **`Undo`** tepat di atas `<QuillEditor ref="quillEditorRef" />`.

---

## 2. Dukungan AI Chat untuk KB Berformat Raw Markdown
Agar AI Chat (`SALMA AI`) tetap memberikan jawaban berformat matang dan bersih meskipun masih ada entri KB lama yang berformat raw Markdown:
1. **`app/Models/KnowledgeBase.php` (`cleanHtml`)**:
   - Mengubah tag penutup blok (`</p>`, `<br>`, `</li>`, dll.) menjadi spasi sebelum `strip_tags()` agar kata tidak saling menempel, serta menghapus pola `[cite: ...]` dari `search_content`.
2. **`app/Services/SalmaPromptService.php` (`formatKbChunks` & `formatGuidelines`)**:
   - Mengutamakan field `content` / `answer` yang terstruktur dan mempertahankan newline antar blok (`</p>`, `<br>`, `</li>`) alih-alih meratakan seluruh teks menjadi 1 baris panjang, sehingga LLM dapat membaca hierarki heading & bullet Markdown mentah secara akurat.
   - Menghapus `[cite: ...]` dari potongan konteks KB sebelum masuk ke prompt LLM.
   - Menambahkan instruksi khusus di `formatGuidelines()` agar LLM selalu merapikan konteks KB raw Markdown menjadi output Markdown matang tanpa pernah menampilkan `[cite: ...]` atau tag mentah.
3. **`resources/js/Pages/Chat/Index.vue` (`renderMarkdownToChatHtml`)**:
   - Menyatukan logika format `formatMessage` dan `getFormattedContent` ke satu fungsi bersama.
   - Memperbaiki urutan eksekusi regex: heading (`#` s.d. `####`), link, bold (`**`), dan bullet list (`* ` / `- `) diproses **sebelum** konversi `\n` ➔ `<br>`, sekaligus membersihkan sisa `[cite: ...]`.

---

## 3. Redesign Tabel Knowledge Base & Penghapusan Footer Ganda (`KnowledgeBase/Index.vue`)

### A. Kolom Compact & Gabungan (Atas–Bawah)
- **Title**: Mendapat ruang horizontal jauh lebih lebar (`minWidth: 300px`); tombol download file (jika `file_path` ada) diposisikan rapi di bawah ikon tipe sumber.
- **Category / Type**: Digabung 1 kolom atas–bawah (Kategori di atas, label tipe singkat seperti `FAQ`, `SOP`, `Peraturan`, `Panduan` di bawah).
- **Status / Active**: Digabung 1 kolom atas–bawah (`Published`/`Draft` di atas, `VSwitch` compact dengan skala `0.75` + label `Aktif`/`Nonaktif` di bawah).
- **Quality / Created**: Digabung 1 kolom atas–bawah (skor kualitas Pinecone + tombol refresh di atas, tanggal & nama pembuat di bawah).
- **Actions**: Menggunakan layout **2×2 grid** (`.kb-actions-grid`) untuk 4 tombol utama: Detail (`mdi-eye`), Edit (`mdi-pencil`), Enhance AI (`mdi-auto-fix`), dan Hapus (`mdi-delete`).

### B. Penghapusan Footer Vuetify Redundan (Image 3)
- Pada Vuetify 3.5.10, prop `hide-default-footer` belum didukung penuh sehingga footer bawaan (`Items per page: All | 1-15 of 15`) tetap muncul.
- Ditambahkan `<template #bottom></template>` pada `<VDataTable>` untuk menyembunyikan footer bawaan secara total, sehingga hanya paginasi server-side resmi (`Showing X–Y of Z entries` + `VPagination`) yang ditampilkan.

---

## 4. Mitigasi Keamanan PHP2Shell (`CVE-2026-109618/109619`) & PHPAfterShell (`CVE-2026-126714`)

### A. Upgrade Runtime PHP Lokal ke `PHP 8.3.37` (Rilis Patch 19 Nov 2026)
- Mengunduh dan memasang build resmi **`PHP 8.3.37` (`php-8.3.37-Win32-vs16-x64`)** ke `C:\laragon\bin\php\php-8.3.37-Win32-vs16-x64`.
- Konfigurasi `php.ini`:
  - Mengaktifkan ekstensi wajib aplikasi (`curl`, `mbstring`, `pdo_mysql`, `gd`, `intl`, `openssl`, `sodium`, `fileinfo`, `bcmath`, `exif`, `xsl`, **`zip`**, dan **`opcache`**).
  - Menetapkan `session.upload_progress.enabled = Off` untuk memitigasi vektor interupsi upload pada `CVE-2026-126714`.
  - Memperbarui konfigurasi Apache Laragon `C:\laragon\etc\apache2\mod_php.conf` ke `php-8.3.37-Win32-vs16-x64`.

### B. Proteksi Proyek & Deployment Produksi
1. **`composer.json` & `composer.lock`**:
   - Memperketat constraint PHP menjadi `"php": "^8.3.37|^8.4.28|^8.5.13|^8.6.0"`, sehingga Composer otomatis menolak berjalan pada PHP rentan (termasuk PHP 8.2/8.1 atau lebih lama, 8.3.0–8.3.36, 8.4.0–8.4.27, 8.5.0–8.5.12).
   - Menjalankan `composer update` dengan PHP 8.3.37 untuk memperbarui dependensi (`composer.lock`).
2. **Command Diagnostik (`php artisan app:security-check`)**:
   - Menambahkan [SecurityCheckCommand.php](file:///c:/laragon/www/bapenda-ai/app/Console/Commands/SecurityCheckCommand.php) untuk memverifikasi versi runtime PHP, driver session, dan status `session.upload_progress.enabled` di lokal maupun server produksi.
3. **Panduan Deployment Produksi**:
   - Pastikan server produksi menjalankan **PHP >= 8.3.37 / 8.4.28 / 8.5.13 / 8.6.0**, rebuild container/image jika memakai Docker, **restart worker PHP-FPM / Apache / Nginx**, gunakan `SESSION_DRIVER=database` (atau `redis`), lalu jalankan `php artisan app:security-check` setelah deploy.

---

## 5. Verifikasi & Hasil Pengujian
- **Vite Build (`npm run build`)**: Sukses (`exit code 0`, client & SSR bundle).
- **Markdown Converter Test (`node`)**: Mengonversi sample Markdown + `[cite: 42, 114]` menjadi HTML semantik Quill (`<h1>`–`<h3>`, `<strong>`, `<ul><li>`) tanpa menyisakan `[cite]`.
- **Unit Test (`php artisan test --filter="MarkdownHandlingAndSecurityTest|MultiTurnConversationalTest"`)**: **8 passed (22 assertions)** menggunakan **PHP 8.3.37**.
- **Security Check (`php artisan app:security-check`)**: **PASS** pada PHP `8.3.37` (`Upload Progress: Off`).

