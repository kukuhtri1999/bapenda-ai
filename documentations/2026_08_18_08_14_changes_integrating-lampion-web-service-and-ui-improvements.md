# Dokumentasi Perubahan: Integrasi Layanan LAMPION Online & Penyempurnaan UI/UX

**Tanggal / Waktu:** 18 Agustus 2026, 08:24 WIB  
**Modul Terdampak:** Homepage, CMS Dashboard, Floating Chat Assistant, Footer & Branding, Database Seeder  
**Tipe Perubahan:** Penambahan Fitur Baru (LAMPION Linktree Gateway), Refactoring Desain Grid, Pembaruan Tema UI/UX, & Penyederhanaan Footer

---

## 1. Ringkasan Eksekutif

Pengembangan ini bertujuan mengintegrasikan layanan mandiri resmi **LAMPION (LAyanan sAMsat melalui aPliKasi ONline)** yang bersumber dari portal Linktree resmi (`https://linktr.ee/ilayanankbsamsatlamongan`) ke dalam Beranda KB Samsat Lamongan.

Sesuai arahan, section LAMPION dirancang **minimalis, bersih, dan langsung mengarahkan traffic pengguna ke Linktree resmi** tanpa membebani beranda dengan daftar formulir/link yang bertumpuk. Bersamaan dengan itu, dilakukan penyelarasan identitas visual instansi (Red Theme), penyempurnaan grid galeri pemutihan pajak, pembersihan menu dan tautan sosial media footer, serta pembuatan seeder independen (`HomepageContentLampionSeeder`) agar aman dieksekusi di server production tanpa menimpa data CMS yang telah dimodifikasi sebelumnya.

---

## 2. Rincian Perubahan Berdasarkan Komponen

### A. Portal Layanan LAMPION Online (Direct Linktree Gateway)

1. **Konsep Desain Minimalis & Terfokus:**
   - Section LAMPION diletakkan tepat setelah section **Pembayaran Digital**.
   - Terdiri dari satu **Kartu Gateway Terpadu (`.lampion-card-direct`)**:
     - **Sisi Kiri:** Badge Tag (`Layanan Online Terpadu`), Judul (`Portal Layanan Mandiri LAMPION Online`), Akronim Resmi (`LAyanan sAMsat melalui aPliKasi ONline`), Deskripsi Pengantar, dan **Chip/Pill Tag Layanan** yang mencakup:
       - 💬 Chat Admin Layanan Pengaduan (WhatsApp)
       - 🚗 Cek E-TBPKB & Info PKB Jatim
       - 💰 Cek Nilai Jual (NJKB)
       - 🔔 Ingatkan Pajak & Blokir Lapor Jual
       - 📝 Formulir Pendaftaran Sewa Lahan
     - **Sisi Kanan:** Box Aksi CTA yang menyajikan tombol utama **"Buka Portal LAMPION (Linktree Resmi) ↗"** yang langsung membuka URL Linktree resmi di tab baru (`target="_blank"`), disertai badge jaminan resmi instansi.
2. **Pengelolaan Konten Mandiri (CMS Admin - `Admin/Cms/Index.vue`):**
   - Menambahkan tab navigasi **"LAMPION Online"** dengan badge `BARU`.
   - Menyediakan switcher aktif/non-aktif (`lampion_is_active`).
   - Menyediakan input teks untuk Badge Tag, Judul Utama, Highlight Merah, Kepanjangan Akronim, dan Deskripsi Pengantar.
   - Menyediakan input **Tautan URL Linktree** (`lampion_url`) dan **Teks Label Tombol** (`lampion_btn_text`).
   - Menyediakan pengelola tag layanan dinamis (tambah, edit label, pilih icon, dan hapus tag).

---

### B. Pembaruan Tema Visual Floating Chat Widget (`FloatingChat.vue`)

- **Palet Government Red:** Mengubah seluruh elemen warna ungu pada widget floating chat menjadi merah resmi Samsat Jawa Timur (`#C0392B`, `#E74C3C`, `#962D22`):
  - **Floating Action Button:** Gradien `linear-gradient(135deg, #C0392B 0%, #E74C3C 100%)` dengan bayangan `rgba(192, 57, 43, 0.45)`.
  - **Header Widget:** Gradien `linear-gradient(135deg, #962D22 0%, #C0392B 50%, #E74C3C 100%)`.
  - **Bubble Pesan Pengguna:** Gradien merah `linear-gradient(135deg, #C0392B, #E74C3C)`.
  - **Avatar & Indikator Ketik:** Ikon robot berlatar gradien merah tua dan titik animasi ketik berwarna `#C0392B`.
  - **Input Focus Ring:** Border fokus dan glow diselaraskan ke warna `#C0392B`.

---

### C. Restrukturisasi & Penyederhanaan Footer (`Welcome.vue`)

- **Pembersihan Kolom Layanan:** Menghapus kolom "Layanan" statis yang redundan.
- **Penataan Ulang Grid 3 Kolom Responsif:**
  - **Kolom 1 (Kiri - `md="5"`):** Identitas Lembaga, 4 Logo Instansi Resmi (Bapenda Jatim, Pemprov Jatim, Polda Jatim, Jasa Raharja), nama instansi, dan deskripsi profil kantor.
  - **Kolom 2 (Tengah - `md="3"`):** Informasi & Layanan (Jadwal & Lokasi, Pembayaran Digital, Portal LAMPION Online, Asisten SALMA AI, Pemutihan Pajak, Hubungi Kami).
  - **Kolom 3 (Kanan - `md="4"`):** Kontak & Pelayanan (Alamat fisik, Telepon kantor, Jam layanan operasional weekday & Jumat, serta tombol media sosial).
- **Penyaringan Media Sosial:** Menghapus tautan Facebook dan YouTube; menyisakan **Instagram** (`@samsat_lamongan`) dan **WhatsApp** resmi.

---

### D. Optimasi Grid Galeri Pemutihan Pajak (`Welcome.vue`)

- **Grid 4 Kolom:** Menetapkan `grid-template-columns: repeat(4, 1fr)` pada resolusi desktop (`>= 1024px`), 2 kolom pada tablet, dan 1 kolom pada mobile.
- **Compact Card Design:** Padding kartu dikurangi menjadi `12px 14px` dengan tipografi yang proporsional sehingga brosur tersaji lebih padat dan rapi.

---

### E. Seeder Khusus & Aman untuk Production (`HomepageContentLampionSeeder.php`)

Dibuat seeder terisolasi `database/seeders/HomepageContentLampionSeeder.php` dengan mekanisme `updateOrCreate` pada kunci:
- `lampion_is_active`
- `lampion_badge`
- `lampion_title`
- `lampion_title_highlight`
- `lampion_subtitle`
- `lampion_desc`
- `lampion_url`
- `lampion_btn_text`
- `lampion_feature_tags`
- `footer_social_links` (update ke Instagram & WhatsApp saja)

---

## 3. Tutorial Lengkap Deployment / Update Server Production

Berikut panduan langkah demi langkah yang dapat langsung Anda copy-paste di terminal server production (SSH / cPanel Terminal).

### Langkah 1: Masuk ke Direktori Project & Tarik Kode Terbaru (Git Pull)
```bash
cd /home/username/public_html   # Sesuaikan path direktori project di server Anda
git pull origin main
```

### Langkah 2: Jalankan Seeder LAMPION Khusus (Aman & Tidak Menimpa CMS Lain)
```bash
php artisan db:seed --class=HomepageContentLampionSeeder
```

### Langkah 3: Bersihkan Seluruh Cache Laravel
```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Langkah 4: Build Aset Frontend (Jika Build Dilakukan di Server)
*Catatan: Jika server Anda memiliki Node.js / npm, jalankan:*
```bash
npm run build
```
*(Atau jika Anda mem-build di lokal dan mengunggah folder `public/build`, pastikan folder `public/build` dan `bootstrap/ssr` terunggah sempurna).*

---

## 4. Daftar File yang Dimodifikasi & Ditambahkan

| File | Status | Keterangan |
| :--- | :--- | :--- |
| `database/seeders/HomepageContentLampionSeeder.php` | **Baru** | Seeder terisolasi untuk data LAMPION dan pembaruan media sosial footer. |
| `resources/js/Components/FloatingChat.vue` | **Modifikasi** | Perubahan palet warna dari ungu ke Government Red pada FAB, header, bubble, typing dots, dan focus ring. |
| `resources/js/Pages/Welcome.vue` | **Modifikasi** | Section gateway LAMPION langsung ke Linktree, grid 4-kolom pemutihan, pembersihan kolom Layanan dan sosmed footer. |
| `resources/js/Pages/Admin/Cms/Index.vue` | **Modifikasi** | Tab LAMPION Online di CMS untuk pengaturan URL Linktree, teks tombol, dan tag layanan. |
