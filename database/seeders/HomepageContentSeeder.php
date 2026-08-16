<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\HomepageContent;

class HomepageContentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $contents = [
            // ── Section 1: Hero Slider ───────────────────────────────────────
            [
                'section' => 'hero',
                'key' => 'hero_badge',
                'value' => 'Pelayanan Publik Resmi',
                'type' => 'text',
                'label' => 'Badge Tag Hero',
                'description' => 'Label kecil di atas judul utama banner',
            ],
            [
                'section' => 'hero',
                'key' => 'hero_title',
                'value' => "Layanan Pajak\nKendaraan Modern",
                'type' => 'textarea',
                'label' => 'Judul Utama Hero',
                'description' => 'Judul besar yang tampil statis di bagian banner hero',
            ],
            [
                'section' => 'hero',
                'key' => 'hero_subtitle',
                'value' => 'Bayar pajak kendaraan bermotor dari mana saja, kapan saja melalui berbagai kanal digital dan layanan resmi Samsat Lamongan.',
                'type' => 'textarea',
                'label' => 'Deskripsi / Subjudul Hero',
                'description' => 'Teks penjelas di bawah judul hero',
            ],
            [
                'section' => 'hero',
                'key' => 'hero_cta_primary_text',
                'value' => 'Cara Bayar',
                'type' => 'text',
                'label' => 'Teks Tombol Utama Hero',
                'description' => 'Teks pada tombol merah di hero banner',
            ],
            [
                'section' => 'hero',
                'key' => 'hero_cta_primary_target',
                'value' => 'pembayaran',
                'type' => 'text',
                'label' => 'Target Scroll Tombol Utama',
                'description' => 'ID section target scroll (contoh: pembayaran, layanan, salma)',
            ],
            [
                'section' => 'hero',
                'key' => 'hero_cta_secondary_text',
                'value' => 'Hubungi Kami',
                'type' => 'text',
                'label' => 'Teks Tombol Sekunder Hero',
                'description' => 'Teks pada tombol outline di hero banner',
            ],
            [
                'section' => 'hero',
                'key' => 'hero_cta_secondary_target',
                'value' => 'kontak',
                'type' => 'text',
                'label' => 'Target Scroll Tombol Sekunder',
                'description' => 'ID section target scroll (contoh: kontak, jadwal)',
            ],
            [
                'section' => 'hero',
                'key' => 'hero_backgrounds',
                'value' => json_encode([
                    'https://picsum.photos/id/1076/1920/800',
                    'https://picsum.photos/id/1048/1920/800',
                    'https://picsum.photos/id/180/1920/800',
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
                'type' => 'json',
                'label' => 'Daftar Gambar Background Hero Slider',
                'description' => 'Daftar URL gambar latar yang akan berotasi secara otomatis',
            ],

            // ── Section 2: Layanan Unggulan ──────────────────────────────────
            [
                'section' => 'services',
                'key' => 'services_badge',
                'value' => 'Layanan Kami',
                'type' => 'text',
                'label' => 'Badge Layanan',
                'description' => 'Label kecil di atas judul Layanan Unggulan',
            ],
            [
                'section' => 'services',
                'key' => 'services_title',
                'value' => 'Layanan Unggulan',
                'type' => 'text',
                'label' => 'Judul Layanan',
                'description' => 'Judul utama bagian Layanan Unggulan',
            ],
            [
                'section' => 'services',
                'key' => 'services_title_highlight',
                'value' => 'KB Samsat Lamongan',
                'type' => 'text',
                'label' => 'Teks Highlight Merah',
                'description' => 'Teks yang diberi warna aksen merah pada judul',
            ],
            [
                'section' => 'services',
                'key' => 'services_desc',
                'value' => 'Berbagai layanan perpajakan dan kesamsatan untuk memudahkan masyarakat Lamongan dan Jawa Timur',
                'type' => 'textarea',
                'label' => 'Deskripsi Bagian Layanan',
                'description' => 'Paragraf pengantar bagian Layanan Unggulan',
            ],
            [
                'section' => 'services',
                'key' => 'services_list',
                'value' => json_encode([
                    [
                        'icon' => 'mdi-car-side',
                        'title' => 'Pajak Tahunan',
                        'desc' => 'Pembayaran Pajak Kendaraan Bermotor (PKB) tahunan dengan mudah dan cepat tanpa antri lama.',
                        'color' => '#C0392B',
                    ],
                    [
                        'icon' => 'mdi-card-account-details-outline',
                        'title' => 'STNK 5 Tahunan',
                        'desc' => 'Perpanjangan masa berlaku STNK dan penggantian plat nomor kendaraan (TNKB) 5 tahunan.',
                        'color' => '#1B2838',
                    ],
                    [
                        'icon' => 'mdi-swap-horizontal-bold',
                        'title' => 'Balik Nama (BBNKB)',
                        'desc' => 'Proses Bea Balik Nama Kendaraan Bermotor antar pemilik pertama ke pemilik berikutnya.',
                        'color' => '#2980B9',
                    ],
                    [
                        'icon' => 'mdi-file-document-swap-outline',
                        'title' => 'Mutasi Masuk / Keluar',
                        'desc' => 'Proses administrasi perpindahan berkas kendaraan bermotor antar wilayah kabupaten atau provinsi.',
                        'color' => '#27AE60',
                    ],
                    [
                        'icon' => 'mdi-bus-clock',
                        'title' => 'Samsat Keliling',
                        'desc' => 'Layanan pembayaran pajak tahunan bergerak yang hadir di berbagai kecamatan di Lamongan.',
                        'color' => '#8E44AD',
                    ],
                    [
                        'icon' => 'mdi-weather-night',
                        'title' => 'BELOK WANGI',
                        'desc' => 'Beda Lokasi Wayah Bengi — Layanan Samsat Keliling Malam setiap pukul 18.00–20.00 WIB.',
                        'color' => '#E67E22',
                    ],
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
                'type' => 'json',
                'label' => 'Daftar Kartu Layanan Unggulan',
                'description' => 'Daftar item kartu layanan lengkap dengan icon, judul, deskripsi, dan warna',
            ],

            // ── Section 3: Jadwal & Lokasi + Google Maps ──────────────────────
            [
                'section' => 'schedules',
                'key' => 'schedules_badge',
                'value' => 'Jadwal & Lokasi',
                'type' => 'text',
                'label' => 'Badge Jadwal',
                'description' => 'Label kecil bagian jadwal layanan',
            ],
            [
                'section' => 'schedules',
                'key' => 'schedules_title',
                'value' => 'Jadwal Layanan',
                'type' => 'text',
                'label' => 'Judul Jadwal',
                'description' => 'Judul utama bagian jadwal',
            ],
            [
                'section' => 'schedules',
                'key' => 'schedules_title_highlight',
                'value' => '& Lokasi Samsat',
                'type' => 'text',
                'label' => 'Teks Highlight Jadwal',
                'description' => 'Teks highlight berwarna merah pada judul jadwal',
            ],
            [
                'section' => 'schedules',
                'key' => 'schedules_desc',
                'value' => 'Pilih lokasi layanan untuk melihat peta dan petunjuk arah langsung',
                'type' => 'textarea',
                'label' => 'Deskripsi Bagian Jadwal',
                'description' => 'Paragraf pengantar bagian jadwal layanan dan peta',
            ],
            [
                'section' => 'schedules',
                'key' => 'keliling_schedules',
                'value' => json_encode([
                    [
                        'day' => 'Senin',
                        'short' => 'Sen',
                        'locations' => [
                            'Pertigaan Sambopinggir (Karangbinangun)',
                            'Depan Pantai Lorena (Paciran)',
                            'Depan Terminal MPU Sukodadi',
                        ],
                    ],
                    [
                        'day' => 'Selasa',
                        'short' => 'Sel',
                        'locations' => [
                            'Depan Kantor Kec. Karanggeneng',
                            'Jl. Raya Pangean (Maduran)',
                            'Depan Kantor Kec. Kembangbahu',
                        ],
                    ],
                    [
                        'day' => 'Rabu',
                        'short' => 'Rab',
                        'locations' => [
                            'Depan Kantor Kec. Mantup',
                            'Balai Desa Sugio',
                            'Jl. Raya Pangean (Maduran)',
                        ],
                    ],
                    [
                        'day' => 'Kamis',
                        'short' => 'Kam',
                        'locations' => [
                            'Desa Kandangrejo (Kedungpring)',
                            'Kantor Kec. Modo',
                            'Depan Masjid Moropelang (Babat)',
                        ],
                    ],
                    [
                        'day' => 'Jumat',
                        'short' => 'Jum',
                        'locations' => [
                            'Balai Desa Puter (Kembangbahu)',
                            'Depan Pantai Lorena (Paciran)',
                            'Samping Koramil Sugio',
                        ],
                    ],
                    [
                        'day' => 'Sabtu',
                        'short' => 'Sab',
                        'locations' => [
                            'Depan Kantor Kec. Mantup',
                            'Kantor Kec. Karanggeneng',
                            'Pertigaan Lonjong (Glagah)',
                        ],
                    ],
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
                'type' => 'json',
                'label' => 'Jadwal Samsat Keliling Pagi (Per Hari)',
                'description' => 'Jadwal per hari (Senin-Sabtu) dengan daftar titik lokasi',
            ],
            [
                'section' => 'schedules',
                'key' => 'layanan_menetap',
                'value' => json_encode([
                    [
                        'name' => 'Samsat Walkthru',
                        'address' => 'Jl. Veteran No. 2, Lamongan',
                        'hours' => 'Senin – Sabtu',
                        'icon' => 'mdi-office-building-marker',
                        'color' => '#C0392B',
                    ],
                    [
                        'name' => 'Mal Pelayanan Publik (MPP)',
                        'address' => 'Jl. Lamongrejo No. 120, Lamongan',
                        'hours' => 'Senin – Jumat',
                        'icon' => 'mdi-domain',
                        'color' => '#1B2838',
                    ],
                    [
                        'name' => 'Payment Point Ngimbang',
                        'address' => 'Kantor Kec. Ngimbang, Lamongan',
                        'hours' => 'Senin – Jumat',
                        'icon' => 'mdi-map-marker-radius-outline',
                        'color' => '#2980B9',
                    ],
                    [
                        'name' => 'Payment Point Babat',
                        'address' => 'Bank Jatim KCP Babat, Lamongan',
                        'hours' => 'Senin – Jumat',
                        'icon' => 'mdi-map-marker-radius-outline',
                        'color' => '#2980B9',
                    ],
                    [
                        'name' => 'Payment Point Brondong',
                        'address' => 'Bank Jatim KCP Brondong, Lamongan',
                        'hours' => 'Senin – Jumat',
                        'icon' => 'mdi-map-marker-radius-outline',
                        'color' => '#2980B9',
                    ],
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
                'type' => 'json',
                'label' => 'Daftar Lokasi Payment Point Menetap',
                'description' => 'Daftar titik lokasi kantor / payment point tetap',
            ],
            [
                'section' => 'schedules',
                'key' => 'belok_wangi_schedules',
                'value' => json_encode([
                    [
                        'days' => 'Senin & Kamis',
                        'location' => 'Depan Kantor KB Samsat Lamongan',
                        'icon' => 'mdi-office-building',
                    ],
                    [
                        'days' => 'Selasa & Jumat',
                        'location' => 'Alun-Alun Lamongan',
                        'icon' => 'mdi-city-variant-outline',
                    ],
                    [
                        'days' => 'Rabu',
                        'location' => 'Terminal Sukodadi Lamongan',
                        'icon' => 'mdi-bus-stop',
                    ],
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
                'type' => 'json',
                'label' => 'Jadwal Samsat Malam (BELOK WANGI)',
                'description' => 'Jadwal dan titik lokasi operasional Samsat Keliling Malam',
            ],

            // ── Section 4: SALMA AI Showcase ─────────────────────────────────
            [
                'section' => 'salma',
                'key' => 'salma_badge',
                'value' => 'AI-Powered',
                'type' => 'text',
                'label' => 'Badge SALMA AI',
                'description' => 'Label kecil di atas judul SALMA AI',
            ],
            [
                'section' => 'salma',
                'key' => 'salma_title',
                'value' => 'SALMA AI',
                'type' => 'text',
                'label' => 'Judul SALMA AI',
                'description' => 'Nama besar asisten AI',
            ],
            [
                'section' => 'salma',
                'key' => 'salma_full_name',
                'value' => 'Samsat Lamongan Modern Assistant',
                'type' => 'text',
                'label' => 'Kepanjangan SALMA AI',
                'description' => 'Kepanjangan resmi nama SALMA',
            ],
            [
                'section' => 'salma',
                'key' => 'salma_desc',
                'value' => 'Asisten cerdas berbasis kecerdasan buatan yang siap menjawab seluruh pertanyaan Anda seputar pajak kendaraan bermotor, prosedur STNK, jadwal Samsat, dan informasi resmi lainnya secara instan — kapan saja, di mana saja.',
                'type' => 'textarea',
                'label' => 'Deskripsi SALMA AI',
                'description' => 'Teks penjelasan fungsi dan keunggulan asisten AI',
            ],
            [
                'section' => 'salma',
                'key' => 'salma_features',
                'value' => json_encode([
                    ['icon' => 'mdi-clock-fast', 'text' => 'Respon Instan 24/7'],
                    ['icon' => 'mdi-shield-check', 'text' => 'Informasi Resmi & Akurat'],
                    ['icon' => 'mdi-brain', 'text' => 'Didukung GPT-5.6 AI'],
                    ['icon' => 'mdi-translate', 'text' => 'Bahasa Indonesia & Jawa'],
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
                'type' => 'json',
                'label' => 'Fitur-Fitur Keunggulan SALMA AI',
                'description' => 'Daftar 4 fitur keunggulan SALMA AI',
            ],
            [
                'section' => 'salma',
                'key' => 'salma_mascot_image',
                'value' => '/images/salma2.gif',
                'type' => 'image',
                'label' => 'Gambar / GIF Maskot SALMA AI',
                'description' => 'URL maskot animasi SALMA AI yang ditampilkan',
            ],
            [
                'section' => 'salma',
                'key' => 'salma_cta_text',
                'value' => 'Mulai Percakapan dengan SALMA',
                'type' => 'text',
                'label' => 'Teks Tombol CTA SALMA AI',
                'description' => 'Teks tombol untuk memulai percakapan',
            ],

            // ── Section 5: Pembayaran Digital ────────────────────────────────
            [
                'section' => 'payment',
                'key' => 'payment_badge',
                'value' => 'E-Samsat',
                'type' => 'text',
                'label' => 'Badge Pembayaran Digital',
                'description' => 'Label kecil di atas judul Pembayaran Digital',
            ],
            [
                'section' => 'payment',
                'key' => 'payment_title',
                'value' => 'Pembayaran Digital',
                'type' => 'text',
                'label' => 'Judul Pembayaran',
                'description' => 'Judul utama bagian kanal pembayaran',
            ],
            [
                'section' => 'payment',
                'key' => 'payment_title_highlight',
                'value' => 'Pajak Kendaraan',
                'type' => 'text',
                'label' => 'Teks Highlight Pembayaran',
                'description' => 'Teks berwarna merah pada judul pembayaran',
            ],
            [
                'section' => 'payment',
                'key' => 'payment_desc',
                'value' => 'Bayar pajak kendaraan kapan saja dan di mana saja tanpa perlu antri',
                'type' => 'textarea',
                'label' => 'Deskripsi Pembayaran',
                'description' => 'Paragraf penjelasan pembayaran digital e-Samsat',
            ],
            [
                'section' => 'payment',
                'key' => 'payment_categories',
                'value' => json_encode([
                    [
                        'name' => 'E-Commerce',
                        'icon' => 'mdi-shopping-outline',
                        'color' => '#00AA5B',
                        'platforms' => [
                            ['name' => 'Tokopedia', 'logo' => '/images/payment/tokopedia.png', 'bg' => '#FFFFFF'],
                            ['name' => 'Shopee', 'logo' => '/images/payment/shopee.png', 'bg' => '#ffffff'],
                            ['name' => 'Alfamart', 'logo' => '/images/payment/alfamart.png', 'bg' => '#CC192B'],
                            ['name' => 'Indomaret', 'logo' => '/images/payment/indomaret.png', 'bg' => '#003F8E'],
                        ],
                    ],
                    [
                        'name' => 'E-Wallet',
                        'icon' => 'mdi-wallet-outline',
                        'color' => '#00AED6',
                        'platforms' => [
                            ['name' => 'GoPay', 'logo' => '/images/payment/gopay.png', 'bg' => '#00AED6'],
                            ['name' => 'LinkAja', 'logo' => '/images/payment/linkaja.svg', 'bg' => '#E82529'],
                            ['name' => 'iSaku', 'logo' => '/images/payment/isaku.png', 'bg' => '#ffffff'],
                            ['name' => 'QRIS', 'logo' => '/images/payment/qris.svg', 'bg' => '#FFFFFF'],
                        ],
                    ],
                    [
                        'name' => 'Perbankan',
                        'icon' => 'mdi-bank-outline',
                        'color' => '#003087',
                        'platforms' => [
                            ['name' => 'Bank Jatim', 'logo' => '/images/payment/bankjatim.png', 'bg' => '#FFFFFF'],
                            ['name' => 'Bukopin', 'logo' => '/images/payment/new/bank-bukopin.png', 'bg' => '#FFFFFF'],
                            ['name' => 'BTN', 'logo' => '/images/payment/btn.png', 'bg' => '#FFFFFF'],
                            ['name' => 'Pos Indonesia', 'logo' => '/images/payment/pos-indonesia.png', 'bg' => '#Ffffff'],
                        ],
                    ],
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
                'type' => 'json',
                'label' => 'Kategori & Platform Pembayaran Digital',
                'description' => 'Daftar kategori dan platform beserta logo dan background color',
            ],

            // ── Section 6: Kontak & Jam Operasional ───────────────────────────
            [
                'section' => 'contact',
                'key' => 'contact_badge',
                'value' => 'Hubungi Kami',
                'type' => 'text',
                'label' => 'Badge Kontak',
                'description' => 'Label kecil bagian kontak',
            ],
            [
                'section' => 'contact',
                'key' => 'contact_title',
                'value' => 'Kontak &',
                'type' => 'text',
                'label' => 'Judul Kontak',
                'description' => 'Judul utama bagian kontak',
            ],
            [
                'section' => 'contact',
                'key' => 'contact_title_highlight',
                'value' => 'KB Samsat Lamongan',
                'type' => 'text',
                'label' => 'Teks Highlight Kontak',
                'description' => 'Subjudul highlight bagian kontak',
            ],
            [
                'section' => 'contact',
                'key' => 'contact_address',
                'value' => 'Jl. Veteran No. 1A, Tumenggungan, Lamongan',
                'type' => 'text',
                'label' => 'Alamat Kantor Utama',
                'description' => 'Alamat fisik kantor KB Samsat Lamongan',
            ],
            [
                'section' => 'contact',
                'key' => 'contact_city_postal',
                'value' => 'Kabupaten Lamongan, Jawa Timur 62211',
                'type' => 'text',
                'label' => 'Kabupaten & Kode Pos',
                'description' => 'Keterangan wilayah dan kode pos',
            ],
            [
                'section' => 'contact',
                'key' => 'contact_hours_weekday',
                'value' => 'Senin – Kamis, Sabtu: 08.00 – 12.00 WIB',
                'type' => 'text',
                'label' => 'Jam Layanan Hari Biasa',
                'description' => 'Jam operasional Senin s.d. Kamis dan Sabtu',
            ],
            [
                'section' => 'contact',
                'key' => 'contact_hours_friday',
                'value' => 'Jumat: 08.00 – 11.00 WIB',
                'type' => 'text',
                'label' => 'Jam Layanan Hari Jumat',
                'description' => 'Jam operasional khusus hari Jumat',
            ],
            [
                'section' => 'contact',
                'key' => 'contact_phone',
                'value' => '(0322) 311234',
                'type' => 'text',
                'label' => 'Nomor Telepon Kantor',
                'description' => 'Nomor telepon resmi kantor',
            ],
            [
                'section' => 'contact',
                'key' => 'contact_help_card_title',
                'value' => 'Butuh Bantuan?',
                'type' => 'text',
                'label' => 'Judul Kartu Bantuan Chat',
                'description' => 'Judul kartu CTA chat di samping informasi kontak',
            ],
            [
                'section' => 'contact',
                'key' => 'contact_help_card_desc',
                'value' => 'Tanyakan apa saja kepada SALMA AI — Asisten pintar yang siap membantu Anda 24 jam nonstop.',
                'type' => 'textarea',
                'label' => 'Deskripsi Kartu Bantuan Chat',
                'description' => 'Teks penjelas kartu bantuan chat',
            ],

            // ── Section 7: Footer & Branding ─────────────────────────────────
            [
                'section' => 'footer',
                'key' => 'footer_agency_name',
                'value' => 'KB Samsat Lamongan',
                'type' => 'text',
                'label' => 'Nama Lembaga Footer',
                'description' => 'Nama lembaga di bagian footer',
            ],
            [
                'section' => 'footer',
                'key' => 'footer_agency_sub',
                'value' => 'Badan Pendapatan Daerah Provinsi Jawa Timur',
                'type' => 'text',
                'label' => 'Sub-nama Lembaga Footer',
                'description' => 'Keterangan instansi induk di footer',
            ],
            [
                'section' => 'footer',
                'key' => 'footer_agency_desc',
                'value' => 'Kantor Bersama Samsat Lamongan melayani pembayaran Pajak Kendaraan Bermotor, pengesahan STNK, dan layanan kesamsatan lainnya bagi masyarakat Kabupaten Lamongan dan Jawa Timur.',
                'type' => 'textarea',
                'label' => 'Deskripsi Profil Lembaga Footer',
                'description' => 'Paragraf profil singkat lembaga di kolom pertama footer',
            ],
            [
                'section' => 'footer',
                'key' => 'footer_social_links',
                'value' => json_encode([
                    ['platform' => 'instagram', 'icon' => 'mdi-instagram', 'url' => 'https://instagram.com'],
                    ['platform' => 'facebook', 'icon' => 'mdi-facebook', 'url' => 'https://facebook.com'],
                    ['platform' => 'youtube', 'icon' => 'mdi-youtube', 'url' => 'https://youtube.com'],
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
                'type' => 'json',
                'label' => 'Tautan Media Sosial Footer',
                'description' => 'Daftar tautan akun media sosial resmi',
            ],
            [
                'section' => 'footer',
                'key' => 'footer_copyright_text',
                'value' => '© 2026 KB Samsat Lamongan — Bapenda Provinsi Jawa Timur. All rights reserved.',
                'type' => 'text',
                'label' => 'Teks Hak Cipta (Copyright)',
                'description' => 'Teks copyright yang tampil di baris paling bawah footer',
            ],
        ];

        foreach ($contents as $data) {
            HomepageContent::updateOrCreate(
                ['key' => $data['key']],
                $data
            );
        }
    }
}
