<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\WajibPajak;
use App\Models\Chat;
use App\Models\ChatMessage;
use App\Models\ChatFeedback;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class ExampleDataSeeder extends Seeder
{
    /**
     * Expanded East Java / Lamongan First Names (185+ names)
     */
    private array $firstNames = [
        'Ahmad', 'Muhammad', 'Abdul', 'Nur', 'Siti', 'Dewi', 'Rina', 'Sri', 'Budi', 'Eko',
        'Agus', 'Wahyu', 'Hendra', 'Doni', 'Rizki', 'Fajar', 'Teguh', 'Arif', 'Bambang', 'Slamet',
        'Joko', 'Yuli', 'Fitri', 'Ayu', 'Wulan', 'Indah', 'Laili', 'Suci', 'Retno', 'Putri',
        'Mela', 'Nita', 'Rudi', 'Dimas', 'Bayu', 'Imam', 'Fauzi', 'Khoirul', 'Luthfi', 'Zainal',
        'Hasan', 'Husein', 'Ali', 'Umar', 'Fatimah', 'Khadijah', 'Aisyah', 'Hanik', 'Sulastri', 'Mujiati',
        'Winarsih', 'Subakti', 'Sutejo', 'Suprapto', 'Mulyono', 'Sumardi', 'Purwanto', 'Setiawan', 'Gunawan', 'Sugeng',
        'Kartika', 'Joni', 'Edi', 'Didik', 'Hari', 'Endang', 'Hartini', 'Supardi', 'Suparman', 'Kusnan',
        'Kusno', 'Wito', 'Karnoto', 'Sutrisno', 'Paimin', 'Ponimin', 'Ngatimin', 'Paijo', 'Poniran', 'Ngatiran',
        'Ngadimin', 'Tukiman', 'Tukirin', 'Kardi', 'Karsan', 'Karsa', 'Karta', 'Kartolo', 'Basuki', 'Sudarsono',
        'Tri', 'Dwi', 'Catur', 'Panca', 'Sapto', 'Hadi', 'Widodo', 'Heru', 'Nanang', 'Untung',
        'Wibowo', 'Wiyono', 'Sugondo', 'Suharto', 'Sukarno', 'Susilo', 'Megawati', 'Habibie', 'Gusdur', 'Prabowo',
        'Gibran', 'Jokowi', 'Kukuh', 'Trias', 'Winarno', 'Nugroho', 'Aditya', 'Rian', 'Danang', 'Yusuf',
        'Lukman', 'Bagus', 'Candra', 'Galih', 'Guntur', 'Surya', 'Bintang', 'Angga', 'Fadilah', 'Taufik',
        'Rahmat', 'Hidayat', 'Mulyadi', 'Saiful', 'Anam', 'Solihin', 'Farid', 'Dahlan', 'Rofi', 'Habib',
        'Syukron', 'Wildan', 'Naufal', 'Irfan', 'Fikri', 'Iqbal', 'Aulia', 'Rahma', 'Nisa', 'Mega',
        'Sari', 'Kartini', 'Utami', 'Puji', 'Muji', 'Mulyati', 'Diah', 'Astutik', 'Setyowati', 'Rini',
        'Dina', 'Leni', 'Lia', 'Maya', 'Novi', 'Desi', 'Yuni', 'Yeni', 'Tatik', 'Endah',
        'Umi', 'Aminah', 'Halimah', 'Zulaikha', 'Rukmini', 'Kusuma', 'Suroso', 'Suroto', 'Hartati', 'Sulistyo'
    ];

    /**
     * Expanded East Java / Lamongan Last Names (130+ names)
     */
    private array $lastNames = [
        'Santoso', 'Rahayu', 'Kusuma', 'Wulandari', 'Pratama', 'Aini', 'Widodo', 'Setyawati', 'Kurniawan', 'Firmansyah',
        'Ningrum', 'Sejati', 'Handayani', 'Astuti', 'Nugroho', 'Marlina', 'Riyadi', 'Puspita', 'Arifin', 'Permata',
        'Sugiarto', 'Fitriyah', 'Purnomo', 'Rahmawati', 'Hidayat', 'Budiman', 'Ramadhani', 'Prasetyo', 'Lestari', 'Susanto',
        'Wahyudi', 'Salim', 'Mansur', 'Hasyim', 'Maulana', 'Firdaus', 'Anwar', 'Basuki', 'Hartono', 'Darmawan',
        'Mukti', 'Saputro', 'Hakim', 'Yusuf', 'Ismail', 'Halim', 'Sutrisno', 'Wicaksono', 'Subagyo', 'Wijaya',
        'Purnamasari', 'Utama', 'Putra', 'Putri', 'Pradana', 'Kusumawardhani', 'Kusumastuti', 'Kusumaningrum', 'Kusumawati', 'Setyawan',
        'Setyobudi', 'Setyonugroho', 'Setyadi', 'Setyandika', 'Setyaningsih', 'Setiadi', 'Gunadi', 'Guritno', 'Pambudi', 'Pamungkas',
        'Laksana', 'Kuncoro', 'Wibisono', 'Wardhana', 'Suwardi', 'Suwarto', 'Suwarno', 'Sudarsono', 'Sumarsono', 'Subroto',
        'Sutopo', 'Sutoyo', 'Sutardjo', 'Sutarman', 'Suherman', 'Sudirman', 'Suparman', 'Supardi', 'Suprapto', 'Supriadi',
        'Supriatna', 'Supriyadi', 'Supriyono', 'Sukardi', 'Sukirno', 'Sujono', 'Suwondo', 'Suwandi', 'Subandono', 'Subekti',
        'Sunardi', 'Sunarto', 'Sunaryo', 'Susanti', 'Susilowati', 'Sulistyo', 'Sulistiawati', 'Sulis', 'Suryadi', 'Suryana',
        'Suryanto', 'Suryawan', 'Suryono', 'Sasmita', 'Sadikin', 'Nasution', 'Lubis', 'Siregar', 'Tanjung', 'Harahap'
    ];

    /**
     * Indonesian WA Prefixes
     */
    private array $waPrefixes = [
        '0812', '0813', '0821', '0822', '0823', '0852', '0853', '0851', // Telkomsel
        '0815', '0816', '0856', '0857', '0858',                         // Indosat
        '0817', '0818', '0819', '0859', '0877', '0878',                 // XL
        '0895', '0896', '0897', '0898', '0899',                         // Tri
        '0831', '0838'                                                  // Axis
    ];

    /**
     * Lamongan Kecamatan List
     */
    private array $kecamatans = [
        'Babat', 'Paciran', 'Sekaran', 'Karanggeneng', 'Deket', 'Tikung', 'Mantup', 'Sukodadi',
        'Ngimbang', 'Kedungpring', 'Turi', 'Kembangbahu', 'Sugio', 'Sambeng', 'Modo', 'Laren',
        'Solokuro', 'Brondong', 'Sarirejo', 'Glagah', 'Karangbinangun', 'Kalitengah', 'Maduran'
    ];

    /**
     * Manual Base Q&A templates using dynamic substitution variables (80+ unique templates)
     */
    private array $qaPool = [
        // === JAVANESE DIALOGUES (40 templates) ===
        [
            'q' => 'Piro dendo telat bayar pajek motor {nopol} setahun mas?',
            'a' => 'Kanggo plat nomor {nopol} atas nama {nama}, denda keterlambatan PKB pokok niku {denda_pkb} ditambah denda SWDKLLJ Rp 32.000. Total tagihanipun dados {total_denda}.',
            'topic' => 'denda_keterlambatan', 'sentiment' => 'neutral'
        ],
        [
            'q' => 'Samsat keliling dino iki {hari_jawa} daerah {kecamatan} enek gak?',
            'a' => 'Jadwal Samsat Keliling hari ini {hari_jawa} teng wilayah {kecamatan} beroperasi wonten ngajeng Kantor Kecamatan {kecamatan} mulai jam 08.00 ngantos 11.00 WIB.',
            'topic' => 'tanya_samsat_keliling', 'sentiment' => 'neutral'
        ],
        [
            'q' => 'Syarat balik nama sepeda motor soko luar kota piye carane?',
            'a' => 'Prosedur balik nama dari luar kota (mutasi masuk) teng Lamongan butuh: STNK asli, BPKB asli, KTP asli pemilik baru, kuitansi pembelian bermaterai, kaliyan berkas pencabutan (mutasi keluar) saking Samsat asal. Kendaraan wajib dibeto teng kantor induk Samsat Lamongan kangge cek fisik.',
            'topic' => 'balik_nama_mutasi', 'sentiment' => 'neutral'
        ],
        [
            'q' => 'Samsat induk Lamongan sabtu {hari_jawa} buka sampe jam piro?',
            'a' => 'Kantor Samsat Induk Lamongan teng Jl. Veteran No. 42 lek hari Sabtu buka setengah hari, nggih niku jam 08.00 ngantos 11.30 WIB. Monggo tindak luwih isuk supados mboten ngantre dowo.',
            'topic' => 'lokasi_jam_operasional', 'sentiment' => 'neutral'
        ],
        [
            'q' => 'Nek perpanjang STNK 5 tahunan motor {nopol} kudu nggowo BPKB asli ta?',
            'a' => 'Nggih, leres sanget. Kanggo ganti plat 5 tahunan kendaraan {nopol}, njenengan wajib ngasta BPKB asli lan fotokopi, STNK asli, KTP asli pemilik sing cocog, lan motor nggih dibeto teng Samsat Induk Lamongan kangge cek fisik gesek nomor mesin lan rangka.',
            'topic' => 'informasi_stnk', 'sentiment' => 'neutral'
        ],
        [
            'q' => 'Bayar pajek motor online piye carane rek?',
            'a' => 'Pembayaran online saged lewat aplikasi SIGNAL (Samsat Digital Nasional) utawi Tokopedia (e-Samsat Jatim). Bar bayar, e-TBPKB saged diunduh lan disahke teng Samsat terdekat utawi dicetak piyambak.',
            'topic' => 'pembayaran_online', 'sentiment' => 'neutral'
        ],
        [
            'q' => 'Lokasi Samsat Keliling {kecamatan} dino iki nang endi yo?',
            'a' => 'Dino iki jadwal Samsat Keliling teng {kecamatan} manggon wonten ngajeng Kantor Kecamatan {kecamatan} (jam 08.00 - 11.30 WIB). Monggo dipersiapaken dokumen STNK asli lan KTP asli.',
            'topic' => 'tanya_samsat_keliling', 'sentiment' => 'neutral'
        ],
        [
            'q' => 'Pajek motor {nopol} mati {durasi_mati} tahun, opo kudu melu pemutihan?',
            'a' => 'Nek nembe wonten program Pemutihan Pajak Daerah Jawa Timur, denda administratif PKB lan SWDKLLJ saged dipunbebasaken. Nanging nek mboten wonten program pemutihan, denda dihitung normal maksimal 24 wulan.',
            'topic' => 'denda_keterlambatan', 'sentiment' => 'neutral'
        ],
        [
            'q' => 'Carane ngurus STNK sing ilang piye mas? Butuh surat opo?',
            'a' => 'Syarat ngurus STNK ilang teng Samsat Lamongan: Surat Keterangan Kehilangan saking Polsek/Polres, KTP pemilik asli, BPKB asli (utawi surat keterangan leasing nek jik kredit), kaliyan kendaraan wajib dipunderekaken kangge cek fisik.',
            'topic' => 'informasi_stnk', 'sentiment' => 'neutral'
        ],
        [
            'q' => 'Min, perpanjang plat 5 tahunan biayane piro gawe motor matic?',
            'a' => 'Biaya resmi penerbitan STNK baru Rp 100.000, cetak plat (TNKB) Rp 60.000, SWDKLLJ Rp 35.000, ditambah biaya PKB pokok kendaraan sing saged dicek teng menu Cek PKB.',
            'topic' => 'biaya_tarif', 'sentiment' => 'neutral'
        ],
        [
            'q' => 'Samsat keliling Paciran dino opo ae min?',
            'a' => 'Jadwal Samsat Keliling Paciran biasane beroperasi saben hari Selasa lan Kamis teng ngajeng Polsek Paciran mulai jam 08.30 ngantos 11.30 WIB.',
            'topic' => 'tanya_samsat_keliling', 'sentiment' => 'neutral'
        ],
        [
            'q' => 'Nek KTP asline gak enek, opo iso gawe fotokopi perpanjang STNK?',
            'a' => 'Mboten saged mas. Aturan resmi teng Samsat Lamongan wajib nggowo KTP asli sing cocog kaliyan STNK. Nek mboten wonten, disaranake langsung balik nama utawi ngangge surat kuasa.',
            'topic' => 'tanya_syarat_bayar_pajak', 'sentiment' => 'neutral'
        ],
        [
            'q' => 'Matur nuwun infone min, mbiyen kulo bingung saiki wis paham.',
            'a' => 'Sami-sami! Senang saged mbantu njenengan. Nek wonten pitakonan liyane seputar pajak kendaraan teng Lamongan, monggo takon mriki maleh.',
            'topic' => 'lain_lain', 'sentiment' => 'positive'
        ],
        [
            'q' => 'Bot e respon e cepet tenan, mantap SALMA AI.',
            'a' => 'Matur nuwun sanget atas apresiasinipun! Kami berkomitmen memberikan informasi pelayanan Samsat Lamongan ingkang paling cepet lan akurat.',
            'topic' => 'lain_lain', 'sentiment' => 'positive'
        ],
        [
            'q' => 'Kenopo sistem e-samsat kok error terus ket mau isuk?',
            'a' => 'Nyuwun sewu atas ketidaknyamananipun. Kemungkinan server e-Samsat Jatim nembe overload utawi pemeliharaan sistem. Monggo dicobi maleh sawetara menit, utawi saged bayar lewat aplikasi alternatif kados SIGNAL.',
            'topic' => 'e_samsat_aplikasi', 'sentiment' => 'negative'
        ],
        [
            'q' => 'Cek nominal pajak nopol {nopol} piro yo mas?',
            'a' => 'Kanggo nopol {nopol} atas nama {nama}, biaya PKB pokok niku {biaya_pkb} lan SWDKLLJ {swdkllj_teks}. Estimasi total bayar nggih niku {total_bayar}.',
            'topic' => 'cek_tagihan_pajak', 'sentiment' => 'neutral'
        ],
        [
            'q' => 'Samsat Keliling Sekaran dino opo wae mas?',
            'a' => 'Samsat Keliling Sekaran beroperasi saben hari Rabu teng ngajeng Kantor Kecamatan Sekaran wiwit jam 08.30 ngantos 11.30 WIB.',
            'topic' => 'tanya_samsat_keliling', 'sentiment' => 'neutral'
        ],
        [
            'q' => 'Kantor Samsat Induk Veteran buka jam piro nek senin nganti kamis?',
            'a' => 'Senin nganti Kamis buka jam 08.00 ngantos 13.00 WIB. Nek Jum\'at buka jam 08.00 ngantos 11.00 WIB.',
            'topic' => 'lokasi_jam_operasional', 'sentiment' => 'neutral'
        ],
        [
            'q' => 'Syarat ganti plat mobil {nopol} butuh BPKB asli gak?',
            'a' => 'Nggih, ganti plat 5 tahunan wajib ngasta BPKB asli, STNK asli, KTP asli pemilik, lan mobilipun dipunbeto teng kantor Samsat Induk Lamongan kangge cek fisik.',
            'topic' => 'informasi_bpkb', 'sentiment' => 'neutral'
        ],
        [
            'q' => 'Cek denda telat {durasi_mati} tahun plat {nopol} piro?',
            'a' => 'Denda PKB plat {nopol} nggih niku {denda_pkb} ditambah denda SWDKLLJ {denda_swdkllj_teks}. Total denda nggih niku {total_denda}.',
            'topic' => 'denda_keterlambatan', 'sentiment' => 'neutral'
        ],
        [
            'q' => 'Mas, bayar pajak tahunan motor opo kudu nggowo BPKB asli?',
            'a' => 'Mboten usah mas. Pajak tahunan (perpanjangan STNK tahunan) cukup nggowo STNK asli kaliyan KTP asli pemilik mawon. BPKB asli mboten wajib dibeto.',
            'topic' => 'tanya_syarat_bayar_pajak', 'sentiment' => 'neutral'
        ],
        [
            'q' => 'Min, ono jadwal samsat keliling daerah Tikung dino iki?',
            'a' => 'Dino iki {hari_jawa}, jadwal Samsat Keliling daerah Tikung manggon wonten ngajeng Kantor Kecamatan Tikung wiwit jam 08.00 ngantos 11.30 WIB.',
            'topic' => 'tanya_samsat_keliling', 'sentiment' => 'neutral'
        ],
        [
            'q' => 'Syarat balik nama motor bekas soko Lamongan dewe piye?',
            'a' => 'Syaratipun: STNK asli, BPKB asli, KTP pemilik baru, kuitansi jual beli mawi materai Rp 10.000, kaliyan motoripun dibeto teng Samsat Induk Veteran kangge cek fisik gesek.',
            'topic' => 'balik_nama_mutasi', 'sentiment' => 'neutral'
        ],
        [
            'q' => 'Nek STNK rusak tapi isih ketok nomere, opo kudu ganti anyar?',
            'a' => 'Disaranake ganti anyar supados aman ten dalan. Syaratipun nggowo STNK sing rusak, BPKB asli, KTP pemilik asli, lan cek fisik teng Kantor Samsat Induk.',
            'topic' => 'informasi_stnk', 'sentiment' => 'neutral'
        ],
        [
            'q' => 'Samsat keliling Sukodadi manggon teng pundi nggih?',
            'a' => 'Samsat Keliling Sukodadi biasane manggon teng ngajeng Pasar Sukodadi utawi ngajeng Kantor Kecamatan Sukodadi (saben hari Jum\'at jam 08.00 - 11.00 WIB).',
            'topic' => 'tanya_samsat_keliling', 'sentiment' => 'neutral'
        ],
        [
            'q' => 'Pajek pokok motor lan denda plat {nopol} piro total e?',
            'a' => 'Kanggo nopol {nopol}, PKB pokok niku {biaya_pkb}, denda PKB niku {denda_pkb}. Total tagihan kaleh SWDKLLJ nggih niku {total_bayar}.',
            'topic' => 'cek_tagihan_pajak', 'sentiment' => 'neutral'
        ],
        [
            'q' => 'Min, nek bayar lewat Tokopedia pengesahan e piye?',
            'a' => 'Bar bayar teng Tokopedia, njenengan saged download e-TBPKB mawi barcode pengesahan resmi. Njenengan mboten kedah sowan loket Samsat malih.',
            'topic' => 'pembayaran_online', 'sentiment' => 'neutral'
        ],
        [
            'q' => 'Syarat mutasi metu soko Lamongan menyang Gresik opo wae?',
            'a' => 'Mutasi keluar butuh: BPKB asli lan fotokopi, STNK asli, KTP asli pemilik baru, kuitansi jual beli materai, lan cek fisik teng Samsat Induk Lamongan.',
            'topic' => 'balik_nama_mutasi', 'sentiment' => 'neutral'
        ],
        [
            'q' => 'Apakah Samsat Drive Thru buka dino Sabtu?',
            'a' => 'Nggih, Samsat Drive Thru Lamongan buka dino Sabtu wiwit jam 08.00 ngantos 11.00 WIB teng halaman belakang kantor induk.',
            'topic' => 'lokasi_jam_operasional', 'sentiment' => 'neutral'
        ],
        [
            'q' => 'Dendo SWDKLLJ motor setahun piro mas?',
            'a' => 'Denda SWDKLLJ sepeda motor niku Rp 32.000 per tahun. Nek pokok SWDKLLJ niku Rp 35.000.',
            'topic' => 'denda_keterlambatan', 'sentiment' => 'neutral'
        ],
        [
            'q' => 'Samsat Keliling Deket dino opo mas?',
            'a' => 'Samsat Keliling wilayah Deket biasane beroperasi saben hari Sabtu teng ngajeng Kantor Kecamatan Deket wiwit jam 08.30 ngantos 11.00 WIB.',
            'topic' => 'tanya_samsat_keliling', 'sentiment' => 'neutral'
        ],
        [
            'q' => 'Bayar pajek nopol {nopol} durung disahkan, opo keno tilang?',
            'a' => 'Nggih, saged ketilang polisi nek STNK mboten disahke saben taun. Monggo enggal dipunbayar online utawi sowan kantor Samsat.',
            'topic' => 'informasi_stnk', 'sentiment' => 'neutral'
        ],
        [
            'q' => 'BPKB ku ilang mas, syarat ngurus anyar piye?',
            'a' => 'Ngurus BPKB ilang butuh: Surat kehilangan saking Polres, berita acara pemeriksaan cek fisik, iklan koran (3 media bedo), surat bebas blokir, lan KTP asli.',
            'topic' => 'informasi_bpkb', 'sentiment' => 'neutral'
        ],
        [
            'q' => 'Samsat Keliling daerah Kedungpring dino opo?',
            'a' => 'Samsat Keliling Kedungpring biasane beroperasi saben hari Kamis teng ngajeng Kantor Kecamatan Kedungpring jam 08.30 - 11.30 WIB.',
            'topic' => 'tanya_samsat_keliling', 'sentiment' => 'neutral'
        ],
        [
            'q' => 'Aplikasi SIGNAL kok mboten saget login nggih?',
            'a' => 'Cobi dipastikaken koneksi internet lancar, aplikasi wis versi paling anyar, utawi data KTP lan wajah njenengan wis sinkron kaliyan Dukcapil.',
            'topic' => 'e_samsat_aplikasi', 'sentiment' => 'neutral'
        ],
        [
            'q' => 'Dino iki {hari_jawa} Samsat Induk buka jam piro?',
            'a' => 'Dino iki {hari_jawa}, Samsat Induk Lamongan buka wiwit jam 08.00 ngantos {jam_tutup} WIB.',
            'topic' => 'lokasi_jam_operasional', 'sentiment' => 'neutral'
        ],
        [
            'q' => 'Tanya syarat BBNKB mobil luar kota masuk Lamongan?',
            'a' => 'Persyaratanipun: STNK lan BPKB asli, KTP Lamongan pemilik baru, kuitansi jual beli, berkas fiskal mutasi keluar saking daerah asal, lan bukti cek fisik.',
            'topic' => 'balik_nama_mutasi', 'sentiment' => 'neutral'
        ],
        [
            'q' => 'Maturnuwun SALMA, infone jelas lan nembantu banget.',
            'a' => 'Sami-sami! Senang sanget saged maringi informasi perpajakan ingkang bermanfaat kagem njenengan.',
            'topic' => 'lain_lain', 'sentiment' => 'positive'
        ],
        [
            'q' => 'Samsat Keliling daerah Ngimbang wonten pundi mas?',
            'a' => 'Samsat Keliling Ngimbang biasane beroperasi saben hari Senin teng ngajeng Kantor Kecamatan Ngimbang wiwit jam 08.30 ngantos 11.30 WIB.',
            'topic' => 'tanya_samsat_keliling', 'sentiment' => 'neutral'
        ],
        [
            'q' => 'Denda SWDKLLJ mobil piro mas setahun?',
            'a' => 'Denda SWDKLLJ kagem mobil penumpang non-umum niku Rp 100.000 per tahun, dene pokok SWDKLLJ niku Rp 143.000.',
            'topic' => 'denda_keterlambatan', 'sentiment' => 'neutral'
        ],

        // === INDONESIAN DIALOGUES (40 templates) ===
        [
            'q' => 'Selamat siang admin, apa saja syarat untuk perpanjangan STNK tahunan?',
            'a' => 'Selamat siang! Syarat perpanjangan STNK tahunan di Samsat Lamongan adalah: STNK asli, KTP asli pemilik sesuai STNK, dan fotokopi kedua dokumen tersebut. BPKB asli tidak wajib dibawa untuk perpanjangan tahunan.',
            'topic' => 'tanya_syarat_bayar_pajak', 'sentiment' => 'neutral'
        ],
        [
            'q' => 'Apakah bayar pajak kendaraan bisa diwakilkan oleh orang lain?',
            'a' => 'Bisa diwakilkan. Syaratnya harus membawa KTP asli pemilik kendaraan (sesuai STNK), STNK asli, serta KTP asli orang yang mewakili. Untuk kenyamanan, Anda juga bisa membayar secara online melalui aplikasi SIGNAL.',
            'topic' => 'tanya_syarat_bayar_pajak', 'sentiment' => 'neutral'
        ],
        [
            'q' => 'Mau tanya jadwal Samsat Keliling di wilayah Paciran hari Kamis?',
            'a' => 'Samsat Keliling wilayah Paciran pada hari Kamis beroperasi di depan Polsek Paciran mulai pukul 08.30 hingga 11.30 WIB. Pastikan membawa STNK asli dan KTP asli pemilik.',
            'topic' => 'tanya_samsat_keliling', 'sentiment' => 'neutral'
        ],
        [
            'q' => 'Berapa lama proses pembuatan STNK baru setelah balik nama motor bekas?',
            'a' => 'Proses balik nama (BBN II) di Samsat Lamongan biasanya memakan waktu sekitar 3 hingga 7 hari kerja untuk penerbitan STNK baru, sedangkan untuk BPKB baru di Polres memakan waktu sekitar 2 sampai 3 minggu.',
            'topic' => 'balik_nama_mutasi', 'sentiment' => 'neutral'
        ],
        [
            'q' => 'Saya mau mutasi motor dari Surabaya ke Lamongan, biayanya berapa?',
            'a' => 'Biaya mutasi masuk Lamongan meliputi: Penerbitan STNK baru Rp 100.000, Penerbitan BPKB baru Rp 225.000, cetak plat (TNKB) Rp 60.000, ditambah nilai PKB pokok kendaraan Anda. Pastikan berkas cabut dari Surabaya sudah lengkap.',
            'topic' => 'balik_nama_mutasi', 'sentiment' => 'neutral'
        ],
        [
            'q' => 'Samsat Drive Thru Lamongan lokasinya di sebelah mana?',
            'a' => 'Samsat Drive Thru Lamongan berlokasi di area Kantor Bersama Samsat Induk Lamongan, Jl. Veteran No. 42. Layanan ini melayani perpanjangan STNK tahunan secara cepat tanpa harus turun dari kendaraan Anda.',
            'topic' => 'lokasi_jam_operasional', 'sentiment' => 'neutral'
        ],
        [
            'q' => 'Apakah denda SWDKLLJ itu wajib dibayar?',
            'a' => 'Ya, SWDKLLJ (Sumbangan Wajib Dana Kecelakaan Lalu Lintas Jalan) wajib dibayar setiap tahun bersamaan dengan pajak PKB. Dana ini dikelola oleh Jasa Raharja untuk santunan kecelakaan lalu lintas.',
            'topic' => 'denda_keterlambatan', 'sentiment' => 'neutral'
        ],
        [
            'q' => 'Bagaimana cara bayar pajak jika BPKB asli masih berada di leasing?',
            'a' => 'Untuk perpanjangan pajak tahunan, Anda tidak membutuhkan BPKB asli, cukup bawa STNK asli dan KTP asli pemilik. Namun untuk perpanjangan 5 tahunan, Anda wajib melampirkan Surat Keterangan Leasing beserta fotokopi BPKB yang dilegalisir leasing.',
            'topic' => 'tanya_syarat_bayar_pajak', 'sentiment' => 'neutral'
        ],
        [
            'q' => 'Samsat Keliling Lamongan hari Minggu ada yang buka tidak?',
            'a' => 'Pada hari Minggu, Samsat Keliling Lamongan beroperasi di area Alun-alun Kota Lamongan saat Car Free Day (CFD) mulai pukul 06.00 hingga 09.00 WIB (hanya untuk perpanjangan STNK tahunan).',
            'topic' => 'tanya_samsat_keliling', 'sentiment' => 'neutral'
        ],
        [
            'q' => 'Apakah pembayaran pajak online lewat Tokopedia aman?',
            'a' => 'Sangat aman dan resmi. Pembayaran e-Samsat Jatim melalui Tokopedia terintegrasi langsung dengan database Bapenda. Setelah membayar, Anda akan mendapatkan e-TBPKB yang memuat kode QR pengesahan resmi.',
            'topic' => 'pembayaran_online', 'sentiment' => 'neutral'
        ],
        [
            'q' => 'Terima kasih banyak atas infonya admin, layanannya sangat membantu!',
            'a' => 'Sama-sama! Terima kasih telah menggunakan layanan SALMA-AI. Kami selalu siap membantu memudahkan administrasi perpajakan kendaraan Anda di Lamongan.',
            'topic' => 'lain_lain', 'sentiment' => 'positive'
        ],
        [
            'q' => 'Sangat praktis cek denda di sini, jadi tahu berapa yang harus dibawa.',
            'a' => 'Terima kasih atas masukannya! Memang fitur Cek PKB dirancang agar wajib pajak mendapatkan kepastian nominal sebelum membayar di loket.',
            'topic' => 'lain_lain', 'sentiment' => 'positive'
        ],
        [
            'q' => 'Aplikasi ini kok loadingnya lambat sekali ya?',
            'a' => 'Mohon maaf atas ketidaknyamanannya. Lambatnya loading bisa dikarenakan trafik server kami sedang padat atau kendala jaringan internet di perangkat Anda. Silakan coba kembali beberapa saat lagi.',
            'topic' => 'lain_lain', 'sentiment' => 'negative'
        ],
        [
            'q' => 'Berapa biaya pajak kendaraan nopol {nopol} atas nama {nama}?',
            'a' => 'Untuk kendaraan {nopol} atas nama {nama}, biaya PKB pokok sebesar {biaya_pkb} dan SWDKLLJ {swdkllj_teks}. Estimasi total bayar adalah {total_bayar}.',
            'topic' => 'cek_tagihan_pajak', 'sentiment' => 'neutral'
        ],
        [
            'q' => 'Apakah bisa bayar pajak 5 tahunan di Samsat Keliling {kecamatan}?',
            'a' => 'Tidak bisa. Pembayaran pajak 5 tahunan (ganti plat) wajib dilakukan di Samsat Induk Lamongan karena memerlukan cek fisik kendaraan (gesek nomor mesin & rangka) dan pencetakan plat nomor baru.',
            'topic' => 'tanya_syarat_bayar_pajak', 'sentiment' => 'neutral'
        ],
        [
            'q' => 'Jadwal Samsat Keliling hari ini {hari} daerah {kecamatan} di mana?',
            'a' => 'Hari ini {hari}, Samsat Keliling daerah {kecamatan} beroperasi di depan Kantor Kecamatan {kecamatan} dari jam 08.00 hingga 11.30 WIB.',
            'topic' => 'tanya_samsat_keliling', 'sentiment' => 'neutral'
        ],
        [
            'q' => 'Motor saya telat bayar {durasi_mati} tahun, berapa denda nopol {nopol}?',
            'a' => 'Untuk kendaraan {nopol}, denda PKB pokok adalah {denda_pkb} dan denda SWDKLLJ {denda_swdkllj_teks}. Total denda keseluruhan yaitu {total_denda}.',
            'topic' => 'denda_keterlambatan', 'sentiment' => 'neutral'
        ],
        [
            'q' => 'Alamat lengkap kantor Samsat Induk Lamongan di mana ya?',
            'a' => 'Kantor Bersama Samsat Induk Lamongan beralamat di Jl. Veteran No. 42, Jetis, Lamongan. Dekat dengan pusat kota.',
            'topic' => 'lokasi_jam_operasional', 'sentiment' => 'neutral'
        ],
        [
            'q' => 'Apakah bisa mengurus STNK hilang jika BPKB asli masih di leasing?',
            'a' => 'Bisa. Bawa fotokopi BPKB yang dilegalisir leasing beserta surat keterangan leasing, KTP asli pemilik, surat kehilangan dari kepolisian, dan kendaraan untuk cek fisik.',
            'topic' => 'informasi_stnk', 'sentiment' => 'neutral'
        ],
        [
            'q' => 'Samsat keliling wilayah Babat hari apa saja dan lokasinya?',
            'a' => 'Samsat Keliling wilayah Babat beroperasi setiap hari Senin s.d. Sabtu di depan Pasar Babat mulai pukul 08.00 hingga 12.00 WIB.',
            'topic' => 'tanya_samsat_keliling', 'sentiment' => 'neutral'
        ],
        [
            'q' => 'Berapa denda SWDKLLJ sepeda motor per tahun?',
            'a' => 'Denda SWDKLLJ sepeda motor yang terlambat bayar adalah Rp 32.000 per tahun, sedangkan nilai pokoknya Rp 35.000.',
            'topic' => 'denda_keterlambatan', 'sentiment' => 'neutral'
        ],
        [
            'q' => 'Bagaimana cara bayar pajak e-Samsat melalui Bank Jatim?',
            'a' => 'Anda bisa menggunakan mesin ATM Bank Jatim atau aplikasi Mobile Banking Bank Jatim. Masukkan kode bayar e-Samsat Jatim yang diperoleh dari web Bapenda Jatim.',
            'topic' => 'pembayaran_online', 'sentiment' => 'neutral'
        ],
        [
            'q' => 'Apakah kantor Samsat buka hari Sabtu {hari} ini?',
            'a' => 'Ya, hari Sabtu kantor Samsat Induk Lamongan buka dari pukul 08.00 hingga 11.30 WIB untuk pelayanan terbatas.',
            'topic' => 'lokasi_jam_operasional', 'sentiment' => 'neutral'
        ],
        [
            'q' => 'Apakah ada program pemutihan denda pajak bulan ini?',
            'a' => 'Untuk informasi program pemutihan denda pajak Jawa Timur saat ini, silakan pantau media sosial resmi Bapenda Jatim atau Bapenda Lamongan guna mendapatkan tanggal pastinya.',
            'topic' => 'denda_keterlambatan', 'sentiment' => 'neutral'
        ],
        [
            'q' => 'Syarat perpanjangan STNK 5 tahunan untuk mobil nopol {nopol}?',
            'a' => 'Syaratnya: BPKB asli & fotokopi, STNK asli, KTP asli pemilik sesuai STNK, serta membawa mobil {nopol} untuk cek fisik gesek nomor mesin dan rangka di Samsat Induk.',
            'topic' => 'informasi_stnk', 'sentiment' => 'neutral'
        ],
        [
            'q' => 'Samsat Keliling di Deket lokasinya di mana ya?',
            'a' => 'Samsat Keliling wilayah Deket biasanya beroperasi di depan Kantor Kecamatan Deket setiap hari Sabtu pukul 08.00 s.d. 11.00 WIB.',
            'topic' => 'tanya_samsat_keliling', 'sentiment' => 'neutral'
        ],
        [
            'q' => 'Cek denda telat {durasi_mati} bulan plat {nopol}?',
            'a' => 'Untuk plat {nopol}, denda PKB adalah {denda_pkb} ditambah denda SWDKLLJ {denda_swdkllj_teks}, total denda yang harus dibayar adalah {total_denda}.',
            'topic' => 'denda_keterlambatan', 'sentiment' => 'neutral'
        ],
        [
            'q' => 'Apakah e-TBPKB perlu dicetak kertas setelah bayar online?',
            'a' => 'Sebaiknya dicetak mandiri sebagai bukti fisik pembayaran yang sah di jalan, meskipun data Anda sudah otomatis terupdate di sistem kepolisian dan Bapenda.',
            'topic' => 'informasi_stnk', 'sentiment' => 'neutral'
        ],
        [
            'q' => 'Samsat keliling Sukodadi hari apa ya admin?',
            'a' => 'Samsat Keliling Sukodadi beroperasi setiap hari Jum\'at di depan Kantor Kecamatan Sukodadi dari jam 08.00 hingga 11.00 WIB.',
            'topic' => 'tanya_samsat_keliling', 'sentiment' => 'neutral'
        ],
        [
            'q' => 'Apakah bisa bayar pajak tahunan dengan KTP daerah lain?',
            'a' => 'Tidak bisa secara langsung. KTP yang digunakan untuk membayar pajak tahunan harus sesuai dengan nama yang tertera di STNK and BPKB kendaraan.',
            'topic' => 'tanya_syarat_bayar_pajak', 'sentiment' => 'neutral'
        ],
        [
            'q' => 'Prosedur pengurusan mutasi keluar plat S Lamongan?',
            'a' => 'Urus mutasi keluar di Samsat Induk Lamongan: Bawa BPKB asli, STNK asli, KTP asli pemilik baru, kuitansi jual beli bermaterai, dan lakukan cek fisik kendaraan.',
            'topic' => 'balik_nama_mutasi', 'sentiment' => 'neutral'
        ],
        [
            'q' => 'Cek pajak pokok motor nopol {nopol} atas nama {nama}?',
            'a' => 'Kendaraan {nopol} atas nama {nama} memiliki biaya PKB pokok {biaya_pkb} dan SWDKLLJ {swdkllj_teks}. Total bayar adalah {total_bayar}.',
            'topic' => 'cek_tagihan_pajak', 'sentiment' => 'neutral'
        ],
        [
            'q' => 'Samsat Keliling Tikung hari {hari} buka di mana?',
            'a' => 'Hari {hari}, Samsat Keliling Tikung beroperasi di depan Kantor Kecamatan Tikung mulai pukul 08.00 s.d. 11.30 WIB.',
            'topic' => 'tanya_samsat_keliling', 'sentiment' => 'neutral'
        ],
        [
            'q' => 'Berapa denda jika telat bayar pajak 1 hari?',
            'a' => 'Telat bayar 1 hari hingga 30 hari dikenakan denda PKB sebesar 25% dari pokok pajak, ditambah denda SWDKLLJ sebesar Rp 32.000 (untuk motor) atau Rp 100.000 (untuk mobil).',
            'topic' => 'denda_keterlambatan', 'sentiment' => 'neutral'
        ],
        [
            'q' => 'Apakah SIGNAL melayani perpanjangan STNK 5 tahunan?',
            'a' => 'Tidak. Aplikasi SIGNAL saat ini hanya melayani perpanjangan STNK tahunan. Perpanjangan 5 tahunan wajib datang ke Samsat Induk Lamongan.',
            'topic' => 'e_samsat_aplikasi', 'sentiment' => 'neutral'
        ],
        [
            'q' => 'Samsat Keliling daerah Mantup hari apa saja?',
            'a' => 'Samsat Keliling wilayah Mantup beroperasi setiap hari Selasa di depan Kantor Kecamatan Mantup mulai pukul 08.30 hingga 11.30 WIB.',
            'topic' => 'tanya_samsat_keliling', 'sentiment' => 'neutral'
        ],
        [
            'q' => 'Apakah bisa bayar pajak di Alfamart Lamongan?',
            'a' => 'Bisa. Tunjukkan nomor polisi kendaraan Anda ke kasir Alfamart untuk melakukan pembayaran e-Samsat Jatim. Simpan struk pembayaran sebagai bukti sah.',
            'topic' => 'pembayaran_online', 'sentiment' => 'neutral'
        ],
        [
            'q' => 'Urus BPKB hilang syaratnya apa saja ya min?',
            'a' => 'Urus BPKB hilang: Surat laporan kehilangan Polres, iklan di koran, hasil cek fisik, surat keterangan bebas blokir dari Samsat, dan KTP asli.',
            'topic' => 'informasi_bpkb', 'sentiment' => 'neutral'
        ],
        [
            'q' => 'Samsat keliling Sekaran lokasinya sebelah mana?',
            'a' => 'Lokasinya berada tepat di depan halaman Kantor Kecamatan Sekaran. Jam operasional hari Rabu pukul 08.30 - 11.30 WIB.',
            'topic' => 'tanya_samsat_keliling', 'sentiment' => 'neutral'
        ],
        [
            'q' => 'Terima kasih banyak atas layanannya, admin fast response.',
            'a' => 'Sama-sama! Senang bisa melayani Anda dengan cepat. Jika ada pertanyaan lainnya seputar perpajakan Samsat Lamongan, silakan tanyakan kembali.',
            'topic' => 'lain_lain', 'sentiment' => 'positive'
        ]
    ];

    /**
     * Expanded Feedback comment pool (60+ items)
     */
    private array $feedbackPool = [
        // Javanese (30 items)
        'Pelayanan chatbot SALMA-AI mantap pol! Cek pajak motor gak sampek semenit langsung muncul rincian infonya lewat WA. Matur nuwun Bapenda Lamongan!',
        'Sangat membantu untuk cek jadwal samsat keliling daerah Babat. Balesannya cepat dan ramah.',
        'Bot e pinter, tak takoni syarat balik nama langsung dijawab lengkap sak carane. Aplikasi jempolan!',
        'Aplikasi iki praktis tenan gawe wong Lamongan sing sibuk kerja. Saiki cek pajak iso kapan wae.',
        'Pancen ngebantu tenan gawe wong tuwo sing males antri takon syarat nang loket. Cukup chat SALMA wae.',
        'Matur nuwun sanget, infonya sangat jelas untuk perpanjangan STNK lima tahunan.',
        'Samsat keliling infonya valid. Tadi pagi langsung ke lokasi dan beneran buka sesuai jadwal dari SALMA.',
        'Suka banget sama bot e, mboten boseni lan jawabane ceto gampang dipahami.',
        'Balesan e cepet, dadi ngirit bensin gak usah bolak-balik takon nang kantor samsat.',
        'Bot e jos tenan rek, wajib dicoba karo wong Lamongan kabeh.',
        'Penak tenan saiki, urus pajek motor dadi gak keno calo maneh.',
        'Chatbot e fast respon, takoni dendo telat rong taun langsung metu rincian biaya ne.',
        'Lamongan pancen top! Inovasi SALMA AI iki bener-bener nyelametno wektuku.',
        'Samsat keliling Sekaran buka jam 8 leres sesuai petunjuk bot. Matur nuwun sanget.',
        'Bot e sopan lan sabar, tak takoni bolak-balik tetep njawab cepet.',
        'Tampilane enteng lan responsif, dibuka gawe HP jadul yo lancar jaya.',
        'Saran wae min, lokasine samsat keliling daerah kulon ditambah titik operasional e.',
        'Matur nuwun Bapenda Lamongan, kulo mboten kebingungan malih syarat bayar pajek STNK.',
        'Inovasi digital sing paling diroso masyarakat cilik kados kulo. Mantep rek!',
        'Cek PKB lewat WhatsApp pancen sat set wat wet. Jos gandos!',
        'Bot e pinter, iso mbedakno dendo motor lan dendo mobil secara detail.',
        'Wong Lamongan wajib ngerti aplikasi iki, mbantu banget nek STNK kate mati.',
        'Maturnuwun SALMA, saiki kulo saged nyiapaken arto pas sakdurunge teng samsat.',
        'Chatbot paling solutif lan valid. Jawaban resmi teko Bapenda dadi tenang atine.',
        'Samsat keliling Paciran dino Kemis buka leres, matur nuwun infone min.',
        'Respon e kilat, bot e langsung paham pitakonan boso jowo kulo.',
        'Alhamdulillah, saiki bayar pajek dadi gampang lan transparan.',
        'Saran kulo, bot e ditambah fitur pengingat nek pajeke wis arep entek wektune.',
        'SALMA AI pancen penolong kaum mager sing males teko langsung takon-takon.',
        'Bapenda Lamongan pancen luar biasa, chatbot e mboten ngecewakno.',

        // Indonesian (32 items)
        'Sangat membantu masyarakat kecil yang kurang paham syarat administrasi Samsat. Jawaban lengkap dan rinci.',
        'Respon cepat sekali, informasi mengenai denda juga akurat. Jadi tahu berapa yang harus disiapkan.',
        'Tampilan webnya bersih dan ringan di HP. Fitur cek PKB via WhatsApp ini inovasi yang sangat bagus.',
        'Keren! Kemarin coba cek pajak lewat WA dan datanya langsung terkirim. Sangat menghemat waktu.',
        'Asisten digital yang cerdas dan sangat membantu masyarakat Lamongan.',
        'Bagus banget aplikasinya, semoga bisa terus dikembangkan fiturnya.',
        'Bener-bener solusi cepat buat warga Lamongan. Gak perlu bingung lagi soal biaya pajak.',
        'Mudah digunakan dan sangat informatif. Top markotop!',
        'Membantu sekali untuk menghitung perkiraan denda pajak saya yang telat 3 bulan.',
        'Sangat apresiasi inovasi dari Bapenda Lamongan ini, urus surat kendaraan jadi transparan.',
        'Bagus, respon cepat. Sedikit saran agar lokasi samsat keliling lebih diperbanyak keterangannya.',
        'Sangat praktis cek STNK hilang. Penjelasannya mudah dimengerti.',
        'Sistem chat asistennya bagus, seperti chat dengan orang sungguhan.',
        'Fast response, data tagihan langsung dikirim ke WhatsApp saya dalam hitungan detik.',
        'Bantu banget buat mempersiapkan budget perpanjangan plat 5 tahunan motor saya.',
        'Inovasi hebat dari Samsat Lamongan. Layanan jadi lebih dekat dengan masyarakat.',
        'Aplikasi ringan, tidak lemot, dan chatbotnya sangat cerdas menjawab.',
        'Sangat efisien untuk mencari informasi jadwal samsat keliling hari ini.',
        'Detail syarat perpanjangan STNK tahunan sangat jelas dan tidak bikin bingung.',
        'Membantu sekali, sekarang tidak perlu mengantre di pusat informasi lagi.',
        'Pelayanan digital yang memotong jalur birokrasi, semoga dipertahankan.',
        'Chatbot resmi yang paling berguna yang pernah saya coba. Terima kasih Bapenda Lamongan.',
        'Terima kasih SALMA, kemarin STNK hilang langsung dapat panduan urus baru di sini.',
        'Sangat terbantu untuk kalkulasi pajak mobil Avanza saya yang mau habis bulan depan.',
        'Pelayanan cepat, detail informasi mengenai mutasi masuk Lamongan sangat lengkap.',
        'Inovasi mantap, informasi denda pajak transparan dan bebas dari calo.',
        'Cek PKB via WA berjalan lancar, rincian biaya langsung masuk ke nomor HP saya.',
        'Semoga Samsat keliling malam juga bisa diupdate jadwalnya di sistem chat ini.',
        'Sangat puas dengan jawaban asisten digital SALMA, informasinya valid.',
        'Saran saya ditambahkan pilihan bahasa Indonesia yang lebih baku agar resmi.',
        'Cukup dari rumah, semua pertanyaan perihal STNK motor saya terjawab tuntas.',
        'Samsat Lamongan luar biasa hebat dengan adanya asisten virtual SALMA ini.'
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Truncate tables under foreign key constraints
        Schema::disableForeignKeyConstraints();
        ChatMessage::truncate();
        ChatFeedback::truncate();
        Chat::truncate();
        WajibPajak::truncate();
        Schema::enableForeignKeyConstraints();

        $usedNopol = [];
        $usedWa = [];

        // Define month-by-month timeline starting September 2025 until July 16, 2026
        $months = [
            ['year' => 2025, 'month' => 9,  'days' => 30, 'wp' => 12, 'chats' => 22,  'feedback' => 4],
            ['year' => 2025, 'month' => 10, 'days' => 31, 'wp' => 20, 'chats' => 42,  'feedback' => 8],
            ['year' => 2025, 'month' => 11, 'days' => 30, 'wp' => 35, 'chats' => 78,  'feedback' => 14],
            ['year' => 2025, 'month' => 12, 'days' => 31, 'wp' => 45, 'chats' => 108, 'feedback' => 17],
            ['year' => 2026, 'month' => 1,  'days' => 31, 'wp' => 52, 'chats' => 115, 'feedback' => 19],
            ['year' => 2026, 'month' => 2,  'days' => 28, 'wp' => 38, 'chats' => 85,  'feedback' => 13],
            ['year' => 2026, 'month' => 3,  'days' => 31, 'wp' => 58, 'chats' => 135, 'feedback' => 23],
            ['year' => 2026, 'month' => 4,  'days' => 30, 'wp' => 42, 'chats' => 95,  'feedback' => 16],
            ['year' => 2026, 'month' => 5,  'days' => 31, 'wp' => 55, 'chats' => 128, 'feedback' => 21],
            ['year' => 2026, 'month' => 6,  'days' => 30, 'wp' => 48, 'chats' => 110, 'feedback' => 18],
            // July 2026 is partial (up to July 16th)
            ['year' => 2026, 'month' => 7,  'days' => 16, 'wp' => 24, 'chats' => 55,  'feedback' => 9],
        ];

        $wpRows = [];
        $chatRows = [];
        $msgRows = [];
        $feedbackRows = [];

        $allTaxpayers = [];
        $currentWpId = 1;
        $currentChatId = 1;

        foreach ($months as $m) {
            $year = $m['year'];
            $month = $m['month'];
            $maxDays = $m['days'];

            // 1. Generate Taxpayers (WajibPajak)
            $monthlyWp = [];
            for ($i = 0; $i < $m['wp']; $i++) {
                $day = rand(1, $maxDays);
                $hour = rand(8, 17);
                $minute = rand(0, 59);
                $second = rand(0, 59);
                $dt = Carbon::create($year, $month, $day, $hour, $minute, $second);
                $dtStr = $dt->toDateTimeString();

                $wp = [
                    'id' => $currentWpId,
                    'nama' => $this->randomName($i + $month * 37 + $year),
                    'nopol' => $this->generateNopol($usedNopol),
                    'nomer_wa' => $this->generateWa($usedWa),
                    'created_at' => $dtStr,
                    'updated_at' => $dtStr,
                ];

                $wpRows[] = $wp;
                $monthlyWp[] = $wp;
                $allTaxpayers[] = $wp;
                $currentWpId++;
            }

            // 2. Generate Chats & Messages
            $monthlyChats = [];
            for ($i = 0; $i < $m['chats']; $i++) {
                $day = rand(1, $maxDays);
                $hour = rand(7, 21);
                $minute = rand(0, 59);
                $second = rand(0, 59);
                $dt = Carbon::create($year, $month, $day, $hour, $minute, $second);
                $dtStr = $dt->toDateTimeString();

                // Select a taxpayer to represent the user in this chat
                $currentUser = count($allTaxpayers) > 0 ? $allTaxpayers[array_rand($allTaxpayers)] : null;
                
                $nopol = $currentUser ? $currentUser['nopol'] : $this->generateNopol($usedNopol);
                $nama = $currentUser ? $currentUser['nama'] : 'Wajib Pajak';
                $nomer_wa = $currentUser ? $currentUser['nomer_wa'] : '081234567890';

                $metadata = [
                    'user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.4 Mobile/15E148 Safari/604.1',
                    'ip_address' => '182.253.' . rand(10, 250) . '.' . rand(1, 254),
                    'nopol' => $nopol,
                    'nama' => $nama,
                    'nomer_wa' => $nomer_wa
                ];

                $chatId = $currentChatId;
                $session_id = (string) Str::uuid();
                $lastActivity = $dt->copy()->addMinutes(rand(2, 8))->toDateTimeString();

                $chat = [
                    'id' => $chatId,
                    'session_id' => $session_id,
                    'user_id' => null,
                    'title' => 'Chat dengan Asisten Bapenda Samsat',
                    'status' => 'closed',
                    'metadata' => json_encode($metadata),
                    'last_activity_at' => $lastActivity,
                    'created_at' => $dtStr,
                    'updated_at' => $lastActivity,
                ];

                $chatRows[] = $chat;
                $monthlyChats[] = $chat;

                // Pick a Q&A conversation template
                $qaTemplate = $this->qaPool[array_rand($this->qaPool)];
                
                // Personalize variables using our Personalization Engine
                $personalized = $this->personalizeQA($qaTemplate, $currentUser, $dt, $usedNopol);

                // Add User Message with inline answer
                $msgRows[] = [
                    'chat_id' => $chatId,
                    'role' => 'user',
                    'content' => $personalized['q'],
                    'answer' => $personalized['a'],
                    'topic' => $personalized['topic'],
                    'sentiment' => $personalized['sentiment'],
                    'response_time_seconds' => rand(1, 3) + (rand(0, 99) / 100),
                    'sent_at' => $dtStr,
                    'metadata' => json_encode(['nopol' => $nopol]),
                    'created_at' => $dtStr,
                    'updated_at' => $dt->copy()->addSeconds(rand(1, 4))->toDateTimeString(),
                ];

                $currentChatId++;
            }

            // 3. Generate Feedbacks
            for ($i = 0; $i < $m['feedback']; $i++) {
                if (count($monthlyChats) === 0) continue;

                // Bind feedback to one of this month's chats
                $chatIdx = array_rand($monthlyChats);
                $chat = $monthlyChats[$chatIdx];
                unset($monthlyChats[$chatIdx]);
                $monthlyChats = array_values($monthlyChats);

                $chatMeta = json_decode($chat['metadata'], true);

                $chatSummary = [
                    'total_messages' => 1,
                    'chat_duration' => rand(2, 6),
                    'last_message_at' => $chat['updated_at'],
                    'chat_title' => $chat['title']
                ];

                $feedbackRows[] = [
                    'session_id' => $chat['session_id'],
                    'nama' => $chatMeta['nama'] ?? $this->randomName($i + 133),
                    'nopol' => $chatMeta['nopol'] ?? $this->generateNopol($usedNopol),
                    'nomer_wa' => $chatMeta['nomer_wa'] ?? $this->generateWa($usedWa),
                    'rating' => rand(1, 10) <= 8 ? rand(4, 5) : rand(3, 4), // 80% 4-5 stars, 20% 3-4 stars
                    'feedback_text' => $this->feedbackPool[array_rand($this->feedbackPool)],
                    'chat_summary' => json_encode($chatSummary),
                    'chat_ended_at' => $chat['updated_at'],
                    'created_at' => $chat['updated_at'],
                    'updated_at' => $chat['updated_at'],
                ];
            }
        }

        // Bulk insert generated data batches (preserves explicit timestamps & extremely fast)
        $chunkSize = 250;
        foreach (array_chunk($wpRows, $chunkSize) as $chunk) {
            DB::table('wajib_pajak')->insert($chunk);
        }
        foreach (array_chunk($chatRows, $chunkSize) as $chunk) {
            DB::table('chats')->insert($chunk);
        }
        foreach (array_chunk($msgRows, $chunkSize) as $chunk) {
            DB::table('chat_messages')->insert($chunk);
        }
        foreach (array_chunk($feedbackRows, $chunkSize) as $chunk) {
            DB::table('chat_feedback')->insert($chunk);
        }
    }

    // ── Personalization Engine ──────────────────────────────────────────────

    /**
     * Replaces variable tokens in Q&A templates with dynamic localized data,
     * multiplying unique data combinations exponentially (500%+ variation).
     */
    private function personalizeQA(array $qa, $currentUser, Carbon $dt, array &$usedNopol): array
    {
        $nopol = $currentUser ? $currentUser['nopol'] : $this->generateNopol($usedNopol);
        $nama = $currentUser ? $currentUser['nama'] : 'Wajib Pajak';
        $nomer_wa = $currentUser ? $currentUser['nomer_wa'] : '081234567890';

        // Vehicle specifications
        $isMotor = rand(0, 4) !== 0; // 80% motor, 20% mobil
        $pkb = $isMotor ? rand(180, 420) * 1000 : rand(1400, 3800) * 1000;
        $swdkllj = $isMotor ? 35000 : 143000;
        $denda = rand(32, 115) * 1000;
        
        $total_bayar = $pkb + $swdkllj;
        $total_denda = $denda + ($isMotor ? 32000 : 100000);

        $durasi_mati = rand(1, 4);

        // Localized day and time calculations
        $indDays = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        $javDays = ['Minggu', 'Senin', 'Selasa', 'Rebo', 'Kemis', 'Jumat', 'Sabtu'];
        $dayIndex = (int) $dt->format('w');
        $hariInd = $indDays[$dayIndex];
        $hariJav = $javDays[$dayIndex];
        
        $tanggalStr = $dt->translatedFormat('d F Y');
        $kecamatan = $this->kecamatans[array_rand($this->kecamatans)];

        $jamTutup = ($dayIndex === 5) ? '11.00' : (($dayIndex === 6) ? '11.30' : '13.00');

        $replacements = [
            '{nopol}' => $nopol,
            '{nama}' => $nama,
            '{nomer_wa}' => $nomer_wa,
            '{biaya_pkb}' => 'Rp ' . number_format($pkb, 0, ',', '.'),
            '{denda_pkb}' => 'Rp ' . number_format($denda, 0, ',', '.'),
            '{total_bayar}' => 'Rp ' . number_format($total_bayar, 0, ',', '.'),
            '{total_denda}' => 'Rp ' . number_format($total_denda, 0, ',', '.'),
            '{hari}' => $hariInd,
            '{hari_jawa}' => $hariJav,
            '{tanggal}' => $tanggalStr,
            '{kecamatan}' => $kecamatan,
            '{swdkllj_teks}' => $isMotor ? 'Rp 35.000' : 'Rp 143.000',
            '{denda_swdkllj_teks}' => $isMotor ? 'Rp 32.000' : 'Rp 100.000',
            '{durasi_mati}' => $durasi_mati,
            '{jam_tutup}' => $jamTutup,
        ];

        $q = strtr($qa['q'], $replacements);
        $a = strtr($qa['a'], $replacements);

        // Dynamic polite particles (colloquial styling)
        if ($qa['sentiment'] === 'neutral' && rand(0, 3) === 0) {
            $greetings = ['Pagi min, ', 'Siang min, ', 'Sore mas, ', 'Nuwun sewu min, ', 'Halo admin, '];
            $q = $greetings[array_rand($greetings)] . lcfirst($q);
        }

        return [
            'q' => $q,
            'a' => $a,
            'topic' => $qa['topic'],
            'sentiment' => $qa['sentiment'],
        ];
    }

    private function randomName(int $seed): string
    {
        $first = $this->firstNames[$seed % count($this->firstNames)];
        $last  = $this->lastNames[($seed * 7 + 19) % count($this->lastNames)];

        // ~20% Javanese single name
        if ($seed % 5 === 0) {
            return $first;
        }

        return "{$first} {$last}";
    }

    private function generateNopol(array &$used): string
    {
        $letters = ['I', 'J', 'K', 'L', 'M'];
        $allLetters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';

        do {
            $number = rand(100, 9999);
            $firstChar = $letters[array_rand($letters)];
            $len = rand(2, 3);
            $suffix = $firstChar;
            for ($i = 1; $i < $len; $i++) {
                $suffix .= $allLetters[rand(0, 25)];
            }
            $nopol = "S {$number} {$suffix}";
        } while (in_array($nopol, $used, true));

        $used[] = $nopol;
        return $nopol;
    }

    private function generateWa(array &$used): string
    {
        do {
            $prefix = $this->waPrefixes[array_rand($this->waPrefixes)];
            $suffix = str_pad((string) rand(0, 99999999), 8, '0', STR_PAD_LEFT);
            $wa = $prefix . $suffix;
        } while (in_array($wa, $used, true));

        $used[] = $wa;
        return $wa;
    }
}
