<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\HomepageContent;
use Illuminate\Support\Facades\Cache;

class HomepageContentLampionSeeder extends Seeder
{
    /**
     * Run the database seeds for LAMPION Web Service and Footer Social updates.
     * Safe to run without overwriting other existing CMS sections.
     */
    public function run(): void
    {
        $contents = [
            // ── Section: LAMPION Online Web Service ─────────────────────────
            [
                'section' => 'lampion',
                'key' => 'lampion_is_active',
                'value' => 'true',
                'type' => 'boolean',
                'label' => 'Status Section LAMPION',
                'description' => 'Aktifkan untuk menampilkan section Portal Layanan LAMPION Online pada halaman utama.',
            ],
            [
                'section' => 'lampion',
                'key' => 'lampion_badge',
                'value' => 'Layanan Online Terpadu',
                'type' => 'text',
                'label' => 'Badge Tag LAMPION',
                'description' => 'Label kecil di atas judul section LAMPION',
            ],
            [
                'section' => 'lampion',
                'key' => 'lampion_title',
                'value' => 'Portal Layanan Mandiri',
                'type' => 'text',
                'label' => 'Judul Section LAMPION',
                'description' => 'Judul utama section LAMPION',
            ],
            [
                'section' => 'lampion',
                'key' => 'lampion_title_highlight',
                'value' => 'LAMPION Online',
                'type' => 'text',
                'label' => 'Highlight Judul LAMPION (Merah)',
                'description' => 'Teks judul dengan penekanan warna merah',
            ],
            [
                'section' => 'lampion',
                'key' => 'lampion_subtitle',
                'value' => 'LAyanan sAMsat melalui aPliKasi ONline',
                'type' => 'text',
                'label' => 'Kepanjangan Akronim LAMPION',
                'description' => 'Teks sub-judul atau akronim resmi LAMPION',
            ],
            [
                'section' => 'lampion',
                'key' => 'lampion_desc',
                'value' => 'Akses seluruh formulir pengaduan, pengingat masa pajak, cek E-TBPKB, info PKB, hingga cek NJKB resmi KB Samsat Lamongan langsung melalui portal Linktree LAMPION.',
                'type' => 'textarea',
                'label' => 'Deskripsi Singkat Section LAMPION',
                'description' => 'Paragraf pengantar layanan online LAMPION',
            ],
            [
                'section' => 'lampion',
                'key' => 'lampion_url',
                'value' => 'https://linktr.ee/ilayanankbsamsatlamongan',
                'type' => 'text',
                'label' => 'Tautan URL Linktree LAMPION',
                'description' => 'URL tujuan portal LAMPION Linktree resmi',
            ],
            [
                'section' => 'lampion',
                'key' => 'lampion_btn_text',
                'value' => 'Buka Portal LAMPION (Linktree Resmi)',
                'type' => 'text',
                'label' => 'Teks Tombol Aksi LAMPION',
                'description' => 'Label teks pada tombol CTA utama',
            ],
            [
                'section' => 'lampion',
                'key' => 'lampion_feature_tags',
                'value' => json_encode([
                    ['label' => 'Chat Admin Layanan Pengaduan', 'icon' => 'mdi-whatsapp', 'color' => '#25D366'],
                    ['label' => 'Cek E-TBPKB & Info PKB Jatim', 'icon' => 'mdi-file-certificate-outline', 'color' => '#2563EB'],
                    ['label' => 'Cek Nilai Jual (NJKB)', 'icon' => 'mdi-cash-multiple', 'color' => '#D97706'],
                    ['label' => 'Ingatkan Pajak & Blokir Lapor Jual', 'icon' => 'mdi-bell-ring-outline', 'color' => '#C0392B'],
                    ['label' => 'Formulir Pendaftaran Sewa Lahan', 'icon' => 'mdi-file-document-edit-outline', 'color' => '#059669'],
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
                'type' => 'json',
                'label' => 'Daftar Tag Layanan LAMPION',
                'description' => 'Tag/chip layanan utama yang tercakup di dalam Linktree LAMPION',
            ],
            // ── Update Social Media: Keep Only Instagram & WhatsApp ────────
            [
                'section' => 'footer',
                'key' => 'footer_social_links',
                'value' => json_encode([
                    ['platform' => 'instagram', 'icon' => 'mdi-instagram', 'url' => 'https://www.instagram.com/samsat_lamongan'],
                    ['platform' => 'whatsapp', 'icon' => 'mdi-whatsapp', 'url' => 'https://wa.me/6282232161707'],
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
                'type' => 'json',
                'label' => 'Tautan Media Sosial Footer',
                'description' => 'Daftar tautan akun media sosial resmi (Instagram & WhatsApp)',
            ],
        ];

        foreach ($contents as $data) {
            HomepageContent::updateOrCreate(
                ['key' => $data['key']],
                $data
            );
        }

        Cache::forget('homepage_contents_grouped');
    }
}
