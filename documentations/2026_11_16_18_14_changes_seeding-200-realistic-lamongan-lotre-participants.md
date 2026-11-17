# Dokumentasi Perubahan: Seeding 200 Data Dummy Peserta Lotre Realistis Khas Lamongan

**Tanggal & Waktu:** 16 November 2026, 18:14 WIB  
**Author:** Antigravity AI Assistant  
**File Terdampak:**
- `database/seeders/LotrePesertaSeeder.php` (Baru)
- `database/seeders/DatabaseSeeder.php` (Diperbarui)

---

## 1. Latar Belakang & Kebutuhan
User memerlukan data dummy peserta lotre undian sebanyak **200 orang** dengan karakteristik yang menyerupai data warga asli Kabupaten Lamongan:
1. **Nama Realistis**: Menggunakan kombinasi nama Jawa & Indonesia autentik (misal: Moh. Khoirul Anam, Siti Nur Hidayati, Budi Santoso, Sri Wahyuni, Hendra Kurniawan, dll.).
2. **Semua Warga Lamongan**: Setiap data memiliki alamat lengkap di desa/kelurahan asli di Kabupaten Lamongan lengkap dengan format jalan, RT/RW, dan dusun.
3. **Semua 27 Kecamatan di Lamongan Terwakili**:
   - Seluruh 27 kecamatan di Kabupaten Lamongan terdistribusi secara merata:
     - 11 kecamatan memiliki 8 peserta (total 88).
     - 16 kecamatan memiliki 7 peserta (total 112).
     - Total: **200 peserta unik**.
4. **Format Plat Nomor Polisi (Nopol)**:
   - Plat Depan: `S` (Wilayah Lamongan/Bojonegoro/Tuban/Jombang/Mojokerto).
   - Plat Belakang: **Wajib diawali dengan huruf H, I, J, atau K** (kode plat Lamongan S dengan sub-prefix H/I/J/K, misal: `S 4521 JA`, `S 1829 KB`, `S 9045 ICP`, `S 6438 IH`).
   - Angka 1–4 digit unik dan dijamin tidak ada duplikasi nopol.
5. **Dapat Dijalankan Ulang (Idempotent)**:
   - Dibuatkan Seeder Laravel `LotrePesertaSeeder` yang dapat dipanggil kapan saja via `php artisan db:seed --class=LotrePesertaSeeder` atau secara global via `DatabaseSeeder`.

---

## 2. Rincian Implementasi

### A. Pembuatan `database/seeders/LotrePesertaSeeder.php`
- Mendefinisikan 27 kecamatan lengkap dengan nama desa/kelurahan autentik masing-masing:
  `Babat`, `Bluluk`, `Brondong`, `Deket`, `Glagah`, `Kalitengah`, `Karangbinangun`, `Karanggeneng`, `Kedungpring`, `Kembangbahu`, `Lamongan`, `Laren`, `Maduran`, `Mantup`, `Modo`, `Ngimbang`, `Paciran`, `Pucuk`, `Sambeng`, `Sarirejo`, `Sekaran`, `Solokuro`, `Sugio`, `Sukodadi`, `Sukorame`, `Tikung`, `Turi`.
- Generator Nopol dengan pola regex `^S \d{3,4} [HIJK][A-Z]{1,2}$` dengan pemeriksaan keunikan (`$usedNopols`).
- Pembersihan tabel sebelumnya (`PesertaLotre::truncate()`) agar data selalu bersih dan siap pakai.
- Penyimpanan batch insert menggunakan `array_chunk($participants, 50)` untuk efisiensi eksekusi.

### B. Pendaftaran di `database/seeders/DatabaseSeeder.php`
- Menambahkan `$this->call(LotrePesertaSeeder::class);` pada method `run()`.

---

## 3. Hasil Pengujian & Verifikasi
1. **Total Record**: Tepat 200 data pada tabel `peserta_lotre`.
2. **Jumlah Kecamatan Terdaftar**: Tepat 27 kecamatan (100% kecamatan di Lamongan tercakup).
3. **Validasi Regex Plat Nomor**:
   - `preg_match('/^S\s+\d+\s+[HIJK]/', $nopol)` menghasilkan 0 error (seluruh 200 peserta memenuhi kriteria).
4. **Verifikasi API & Fitur Lotre**:
   - Endpoint `/api/lotre/list` mengembalikan status 200 dengan `total: 200`.
   - Endpoint `/api/lotre/pick` berhasil memilih pemenang secara acak dan transaksional.
   - Halaman publik `/lotre-undian` dan admin `/admin/lotre/settings` serta `/admin/lotre/import` langsung membaca data 200 peserta ini secara realtime.
