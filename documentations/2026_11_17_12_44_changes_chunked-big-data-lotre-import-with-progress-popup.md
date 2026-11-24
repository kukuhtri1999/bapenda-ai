# Dokumentasi Perubahan: Chunked Big-Data Lotre Import (44k+ Rows) & Interactive Progress Popup

**Tanggal & Waktu:** 17 November 2026, 12:44 WIB  
**Author:** Antigravity AI Assistant  
**File Terdampak:**
- `database/migrations/2026_11_17_000001_add_nopol_index_to_peserta_lotre.php` (Baru)
- `routes/web.php` (Diperbarui)
- `app/Http/Controllers/Admin/LotreImportController.php` (Diperbarui)
- `app/Http/Controllers/LotreController.php` (Diperbarui)
- `resources/js/Pages/Admin/LotreManagement/Import.vue` (Diperbarui)
- `resources/js/Pages/Lotre/Index.vue` (Diperbarui)

---

## 1. Latar Belakang & Masalah
Saat mengimport file Excel berukuran besar (~44.933 baris), endpoint lama `/admin/lotre/import` mengalami **500 Internal Server Error** karena:
1. `PesertaLotreImport` melakukan 1 query `SELECT` (cek duplikat `nopol`) + 1 query `INSERT` per baris (~90.000 query terpisah dalam 1 request HTTP), yang melampaui batas waktu eksekusi (`max_execution_time`) dan memori PHP.
2. Kolom `peserta_lotre.nopol` belum memiliki index di database, sehingga pengecekan duplikat melakukan *full table scan*.
3. Tidak ada indikator progres real-time di frontend saat memproses puluhan ribu data.

---

## 2. Rincian Implementasi

### A. Database Index (`2026_11_17_000001_add_nopol_index_to_peserta_lotre.php`)
- Menambahkan index `peserta_lotre_nopol_index` pada kolom `peserta_lotre.nopol` sehingga query `WHERE nopol IN (...)` berjalan instan meski data mencapai puluhan hingga ratusan ribu baris.

### B. Backend Chunked Import (`LotreImportController.php` & `routes/web.php`)
Menambahkan 3 endpoint baru di bawah `admin/lotre`:
1. **`POST /admin/lotre/import/prepare` (`admin.lotre.import.prepare`)**:
   - Menerima file hingga 50MB (`max:51200`), menaikkan `@set_time_limit(300)` & `@ini_set('memory_limit', '512M')`.
   - Membaca Excel menggunakan `PhpOffice\PhpSpreadsheet` dalam mode **ReadDataOnly (`setReadDataOnly(true)`)** agar hemat memori dan jauh lebih cepat.
   - Menormalisasi setiap baris (`nama`, `nopol`, `alamat`, `kecamatan`) dan memecahnya ke dalam file JSON bertahap **1.000 baris per part** (`storage/app/lotre_imports/{importId}/chunk_{i}.json`).
2. **`POST /admin/lotre/import/chunk` (`admin.lotre.import.chunk`)**:
   - Memproses 1 part (1.000 baris) per request dalam transaksi `DB::transaction`:
     - **1x Batch Lookup**: `PesertaLotre::whereIn('nopol', $nopols)->pluck('nopol')` sekaligus pengecekan duplikat intra-chunk.
     - **Batch Insert**: `PesertaLotre::insert($subBatch)` per 500 baris (hanya 2 query `INSERT` untuk 1.000 baris).
   - Menghapus file chunk segera setelah selesai diproses dan membersihkan direktori sementara jika seluruh part selesai.
3. **`POST /admin/lotre/import/cleanup` (`admin.lotre.import.cleanup`)**:
   - Membersihkan direktori sementara jika admin membatalkan import di tengah jalan.
- Method `preview()` juga dioptimalkan dengan `setReadDataOnly(true)` dan limit 50MB.

### C. Interactive Real-Time Progress Popup (`resources/js/Pages/Admin/LotreManagement/Import.vue`)
- **Dialog Progres (`VDialog` persistent)**:
  - **Fase 1**: Progres unggah file (`onUploadProgress`) & pemecahan part di server.
  - **Fase 2**: Iterasi *part-by-part* berurutan (`Part X dari Y`) dengan:
    - `VProgressLinear` persentase real-time (`0% – 100%`).
    - Counter live: **Berhasil**, **Duplikat**, **Gagal / Kosong**.
    - Kecepatan proses (`data/detik`) dan **Estimasi Sisa Waktu (ETA)**.
    - Tombol **Batalkan Import** yang menghentikan iterasi secara aman dan membersihkan file sementara.
  - **Fase 3**: Ringkasan akhir & tombol **Lihat Ringkasan Hasil**.
- Memperbaiki styling tombol SweetAlert2 (`buttonsStyling: false`) pada dialog konfirmasi import dan hapus semua data.

### D. Optimasi Halaman Undian untuk Skala 45.000+ Data (`LotreController.php` & `Lotre/Index.vue`)
- `LotreController::index()` kini mengirimkan `totalParticipantsCount` (hasil `count()`) dan maksimal 150 sampel peserta untuk gulungan visual mesin undian, sehingga `/lotre-undian` tetap termuat dalam `< 0,5 detik` meski database berisi 45.000+ peserta.
