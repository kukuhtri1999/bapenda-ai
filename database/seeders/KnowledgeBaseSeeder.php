<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\KnowledgeBase;

class KnowledgeBaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $knowledgeData = [
            // Kategori: Pajak Kendaraan
            [
                'title' => 'Cara Bayar Pajak Kendaraan',
                'question' => 'Bagaimana cara bayar pajak kendaraan?',
                'answer' => 'Pajak kendaraan dapat dibayar dengan beberapa cara:

1. **Datang Langsung ke Samsat Lamongan**
   - Bawa STNK asli dan fotokopi
   - Bawa KTP asli dan fotokopi
   - Siapkan uang tunai atau bisa transfer

2. **Melalui e-Samsat (Online)**
   - Akses website e-samsat.jatimprov.go.id
   - Login dengan NIK dan nomor polisi
   - Bayar via bank transfer atau VA

3. **Melalui Bank/ATM**
   - Bank Jatim, BNI, BRI, Mandiri
   - Pilih menu pembayaran pajak kendaraan
   - Masukkan nomor polisi kendaraan

**Jam Pelayanan:**
Senin - Jumat: 08.00 - 15.00 WIB
Sabtu: 08.00 - 12.00 WIB',
                'category' => 'pajak',
                'type' => 'faq',
                'keywords' => ['bayar pajak', 'pembayaran', 'e-samsat', 'bank', 'atm'],
                'priority' => 10
            ],
            [
                'title' => 'Denda Keterlambatan Pajak',
                'question' => 'Berapa denda jika terlambat bayar pajak kendaraan?',
                'answer' => 'Denda keterlambatan pembayaran pajak kendaraan bermotor:

**Perhitungan Denda:**
- Denda = 25% x PKB + 25% x SWDKLLJ
- Berlaku untuk keterlambatan 1 hari sampai dengan 1 tahun
- Jika lebih dari 1 tahun, ada denda tambahan

**Contoh:**
- PKB: Rp 500.000
- SWDKLLJ: Rp 143.000
- Total Pajak: Rp 643.000
- Denda (25%): Rp 160.750
- **Total Bayar: Rp 803.750**

**Tips:** Bayar sebelum tanggal jatuh tempo untuk menghindari denda.',
                'category' => 'pajak',
                'type' => 'faq',
                'keywords' => ['denda', 'terlambat', 'keterlambatan', 'pkb', 'swdkllj'],
                'priority' => 9
            ],

            // Kategori: STNK
            [
                'title' => 'Syarat Pengesahan STNK',
                'question' => 'Apa saja syarat untuk pengesahan STNK?',
                'answer' => 'Syarat pengesahan STNK:

**Dokumen yang Diperlukan:**
1. STNK asli + fotokopi
2. KTP asli + fotokopi (sesuai alamat di STNK)
3. BPKB asli + fotokopi (jika STNK habis masa berlaku)
4. Kendaraan (untuk pengecekan fisik)
5. Bukti bayar pajak terbaru

**Biaya:**
- Biaya administrasi: Rp 200.000
- Pajak kendaraan sesuai tarif
- SWDKLLJ sesuai jenis kendaraan

**Proses:**
1. Cek fisik kendaraan
2. Verifikasi dokumen
3. Pembayaran
4. Cetak STNK baru

**Estimasi Waktu:** 2-3 jam (jika dokumen lengkap)',
                'category' => 'stnk',
                'type' => 'faq',
                'keywords' => ['pengesahan stnk', 'syarat', 'dokumen', 'biaya', 'proses'],
                'priority' => 10
            ],
            [
                'title' => 'STNK Hilang',
                'question' => 'Bagaimana jika STNK hilang?',
                'answer' => 'Prosedur pengurusan STNK hilang:

**Langkah-langkah:**
1. **Lapor Polisi**
   - Buat surat kehilangan di Polres/Polsek
   - Bawa KTP dan BPKB asli

2. **Ke Samsat Lamongan**
   - Bawa surat kehilangan dari polisi
   - KTP asli + fotokopi
   - BPKB asli + fotokopi
   - Kendaraan untuk cek fisik

**Biaya:**
- Biaya penggantian STNK: Rp 375.000
- Pajak kendaraan (jika belum dibayar)
- Biaya cek fisik: Rp 50.000

**Estimasi Waktu:** 1 hari kerja

**Catatan:** Pastikan semua pajak sudah lunas sebelum mengurus STNK pengganti.',
                'category' => 'stnk',
                'type' => 'faq',
                'keywords' => ['stnk hilang', 'pengganti', 'lapor polisi', 'kehilangan'],
                'priority' => 8
            ],

            // Kategori: Lokasi & Jam Operasional
            [
                'title' => 'Lokasi dan Jam Operasional Samsat Lamongan',
                'question' => 'Dimana lokasi Samsat Lamongan dan jam operasionalnya?',
                'answer' => 'Informasi lokasi dan jam operasional Samsat Lamongan:

**Alamat Lengkap:**
Jl. Veteran No. 1A, Tumenggungan, Kec. Lamongan, Kabupaten Lamongan, Jawa Timur 62211

**Jam Operasional:**
- **Senin - Jumat:** 08.00 - 15.00 WIB
- **Sabtu:** 08.00 - 12.00 WIB
- **Minggu & Hari Libur:** TUTUP

**Kontak:**
- Telepon: (0322) 311234
- WhatsApp: 081234567890

**Fasilitas:**
- Parkir luas
- Ruang tunggu ber-AC
- Toilet umum
- Kantin
- Mushola
- ATM Center

**Transportasi:**
- Angkutan umum: Bus/angkot jurusan terminal
- Ojek online tersedia
- Parkir motor dan mobil gratis',
                'category' => 'lokasi',
                'type' => 'faq',
                'keywords' => ['lokasi', 'alamat', 'jam operasional', 'kontak', 'fasilitas'],
                'priority' => 10
            ],

            // Kategori: Balik Nama
            [
                'title' => 'Syarat Balik Nama Kendaraan',
                'question' => 'Apa syarat untuk balik nama kendaraan?',
                'answer' => 'Syarat balik nama kendaraan bermotor:

**Dokumen dari Penjual (Pemilik Lama):**
1. STNK asli
2. BPKB asli
3. KTP asli + fotokopi
4. Faktur/kwitansi jual beli bermaterai
5. Surat kuasa jika diwakilkan

**Dokumen dari Pembeli (Pemilik Baru):**
1. KTP asli + fotokopi
2. KK asli + fotokopi (jika beda alamat dengan KTP)
3. Surat keterangan domisili (jika KTP luar daerah)

**Syarat Kendaraan:**
- Pajak harus lunas
- Tidak ada tilang yang belum diselesaikan
- STNK masih berlaku
- Kendaraan sesuai dengan dokumen

**Biaya:**
- Biaya balik nama: Rp 375.000 - Rp 500.000
- Pajak progresif (jika ada)
- Biaya cek fisik: Rp 50.000

**Estimasi Waktu:** 1-2 hari kerja',
                'category' => 'balik_nama',
                'type' => 'faq',
                'keywords' => ['balik nama', 'syarat', 'dokumen', 'penjual', 'pembeli', 'biaya'],
                'priority' => 9
            ],

            // Kategori: Layanan Online
            [
                'title' => 'Cara Cek Pajak Online',
                'question' => 'Bagaimana cara cek pajak kendaraan secara online?',
                'answer' => 'Cara mengecek pajak kendaraan secara online:

**Melalui Website e-Samsat Jatim:**
1. Buka website: e-samsat.jatimprov.go.id
2. Pilih menu "Info Pajak Kendaraan"
3. Masukkan nomor polisi kendaraan
4. Masukkan nomor NIK pemilik
5. Klik "Cari"

**Melalui Aplikasi Mobile:**
1. Download aplikasi "e-Samsat Jatim" di PlayStore/AppStore
2. Registrasi dengan NIK dan nomor polisi
3. Login dan pilih "Cek Pajak"

**Informasi yang Ditampilkan:**
- Nilai PKB (Pajak Kendaraan Bermotor)
- Nilai SWDKLLJ (Sumbangan Wajib Dana Kecelakaan Lalu Lintas Jalan)
- Denda keterlambatan (jika ada)
- Tanggal jatuh tempo
- Total yang harus dibayar

**Keuntungan Cek Online:**
- Tidak perlu datang ke Samsat
- Bisa mengecek kapan saja (24 jam)
- Bisa langsung bayar online',
                'category' => 'online',
                'type' => 'faq',
                'keywords' => ['cek pajak online', 'e-samsat', 'website', 'aplikasi', 'info pajak'],
                'priority' => 8
            ],

            // Kategori: Tarif dan Biaya
            [
                'title' => 'Tarif Pajak Kendaraan Bermotor',
                'question' => 'Bagaimana perhitungan tarif pajak kendaraan bermotor?',
                'answer' => 'Perhitungan tarif pajak kendaraan bermotor:

**Komponen Pajak:**
1. **PKB (Pajak Kendaraan Bermotor)**
   - Tarif: 2% x NJKB x Bobot
   - NJKB = Nilai Jual Kendaraan Bermotor
   - Bobot tergantung jenis dan umur kendaraan

2. **SWDKLLJ (Sumbangan Wajib Dana Kecelakaan)**
   - Motor 50-250cc: Rp 35.000/tahun
   - Motor >250cc: Rp 143.000/tahun
   - Mobil penumpang: Rp 143.000/tahun
   - Mobil barang: Rp 143.000/tahun

**Faktor yang Mempengaruhi:**
- Tahun pembuatan kendaraan
- Kapasitas mesin (cc)
- Harga pasaran kendaraan
- Kepemilikan (pertama/kedua/ketiga - pajak progresif)

**Contoh Perhitungan Motor 150cc tahun 2020:**
- NJKB: Rp 15.000.000
- PKB (2%): Rp 300.000
- SWDKLLJ: Rp 35.000
- **Total: Rp 335.000/tahun**

**Pajak Progresif:**
- Kendaraan ke-2: +100% dari PKB
- Kendaraan ke-3: +200% dari PKB
- Dan seterusnya',
                'category' => 'tarif',
                'type' => 'faq',
                'keywords' => ['tarif', 'perhitungan', 'pkb', 'swdkllj', 'njkb', 'progresif'],
                'priority' => 7
            ]
        ];

        foreach ($knowledgeData as $data) {
            KnowledgeBase::create($data);
        }
    }
}
