<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PesertaLotre;
use Illuminate\Support\Facades\DB;

class LotrePesertaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Seeds 200 realistic Lamongan participants across all 27 subdistricts (kecamatan).
     * Plate rules:
     * - Front prefix: S
     * - Number: 1 - 4 digits
     * - Rear suffix: Starts with H, I, J, or K
     */
    public function run(): void
    {
        // Truncate existing peserta_lotre to start fresh with clean 200 participants
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        PesertaLotre::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // 27 Kecamatan in Kabupaten Lamongan with their authentic villages/desas
        $kecamatanVillages = [
            'Babat' => ['Ds. Bedahan', 'Kel. Babat', 'Ds. Plaosan', 'Ds. Moropelang', 'Ds. Karangkembang', 'Ds. Pucakwangi', 'Ds. Datinawong'],
            'Bluluk' => ['Ds. Bluluk', 'Ds. Bronjong', 'Ds. Cangkring', 'Ds. Kuwurejo', 'Ds. Songowareng', 'Ds. Banjargondang'],
            'Brondong' => ['Kel. Brondong', 'Ds. Labuhan', 'Ds. Sedayulawas', 'Ds. Brengkok', 'Ds. Lohgung', 'Ds. Tlogoretno'],
            'Deket' => ['Ds. Deket Kulon', 'Ds. Deket Wetan', 'Ds. Rejotengah', 'Ds. Pandanpancur', 'Ds. Dinoyo', 'Ds. Sidorejo'],
            'Glagah' => ['Ds. Glagah', 'Ds. Margoanyar', 'Ds. Pasi', 'Ds. Soko', 'Ds. Meluwur', 'Ds. Jatirenggo'],
            'Kalitengah' => ['Ds. Kalitengah', 'Ds. Pengangsalan', 'Ds. Canditunggal', 'Ds. Dibee', 'Ds. Tiwet', 'Ds. Jelakcatur'],
            'Karangbinangun' => ['Ds. Karangbinangun', 'Ds. Sambopinggir', 'Ds. Pendowolimo', 'Ds. Palangan', 'Ds. Banyuurip', 'Ds. Prijekngablak'],
            'Karanggeneng' => ['Ds. Karanggeneng', 'Ds. Sungelebak', 'Ds. Guci', 'Ds. Sumberwudi', 'Ds. Mertani', 'Ds. Jagran'],
            'Kedungpring' => ['Ds. Kedungpring', 'Ds. Majenang', 'Ds. Mekanderejo', 'Ds. Sidomlangean', 'Ds. Warungering', 'Ds. Karangcangkring'],
            'Kembangbahu' => ['Ds. Kembangbahu', 'Ds. Pelang', 'Ds. Moronyamplung', 'Ds. Sukosengo', 'Ds. Dumpiagung', 'Ds. Lopang'],
            'Lamongan' => ['Kel. Jetis', 'Kel. Tumenggungan', 'Kel. Sidokumpul', 'Kel. Sukorejo', 'Ds. Made', 'Ds. Tanjung', 'Ds. Sidomukti'],
            'Laren' => ['Ds. Laren', 'Ds. Gampangsejati', 'Ds. Pelangwot', 'Ds. Bulubrangsi', 'Ds. Siser', 'Ds. Godog'],
            'Maduran' => ['Ds. Maduran', 'Ds. Pangean', 'Ds. Gumantuk', 'Ds. Turi', 'Ds. Parengan', 'Ds. Siwuran'],
            'Mantup' => ['Ds. Mantup', 'Ds. Sukobendu', 'Ds. Sumberagung', 'Ds. Kedukbembem', 'Ds. Rumpuk', 'Ds. Mojosari'],
            'Modo' => ['Ds. Mojorejo', 'Ds. Yungyang', 'Ds. Pule', 'Ds. Sidomulyo', 'Ds. Kacangan', 'Ds. Medalem'],
            'Ngimbang' => ['Ds. Ngimbang', 'Ds. Sendangrejo', 'Ds. Slaharwangi', 'Ds. Lamongrejo', 'Ds. Kradenan', 'Ds. Ganggangtingan'],
            'Paciran' => ['Ds. Paciran', 'Ds. Kranji', 'Ds. Tunggul', 'Ds. Kandangsemangkon', 'Ds. Banjarwati', 'Ds. Drajat', 'Ds. Weru'],
            'Pucuk' => ['Ds. Pucuk', 'Ds. Kesambi', 'Ds. Wanar', 'Ds. Padenganploso', 'Ds. Warukulon', 'Ds. Paji'],
            'Sambeng' => ['Ds. Sambeng', 'Ds. Ardirejo', 'Ds. Candisari', 'Ds. Jatipandak', 'Ds. Nogojatisari', 'Ds. Selorejo'],
            'Sarirejo' => ['Ds. Sarirejo', 'Ds. Dermolemahbang', 'Ds. Simbatan', 'Ds. Gempoltukmloko', 'Ds. Canggah', 'Ds. Kedungkumpul'],
            'Sekaran' => ['Ds. Sekaran', 'Ds. Miru', 'Ds. Kudikan', 'Ds. Karangsegardi', 'Ds. Simorejo', 'Ds. Besur'],
            'Solokuro' => ['Ds. Solokuro', 'Ds. Dagan', 'Ds. Payaman', 'Ds. Bluri', 'Ds. Banyubang', 'Ds. Tebluru'],
            'Sugio' => ['Ds. Sugio', 'Ds. Bakalrejo', 'Ds. Jubellor', 'Ds. German', 'Ds. Lebakadi', 'Ds. Sekarbagus'],
            'Sukodadi' => ['Ds. Sukodadi', 'Ds. Menongo', 'Ds. Surabayan', 'Ds. Pajangan', 'Ds. Sumberaji', 'Ds. Baturono', 'Ds. Madulegi'],
            'Sukorame' => ['Ds. Sukorame', 'Ds. Pendowokumpul', 'Ds. Sewor', 'Ds. Banggle', 'Ds. Kedungkumpul', 'Ds. Sembung'],
            'Tikung' => ['Ds. Bakalanpule', 'Ds. Pengumbahan', 'Ds. Jatirejo', 'Ds. Wonokromo', 'Ds. Takeranklating', 'Ds. Guminingrejo'],
            'Turi' => ['Ds. Turi', 'Ds. Kemlagilor', 'Ds. Balun', 'Ds. Sukorejo', 'Ds. Gawang', 'Ds. Putatkumpul']
        ];

        // First names (authentic Indonesian / Javanese Lamongan)
        $firstNames = [
            'Ahmad', 'Muhammad', 'Siti', 'Budi', 'Supriyadi', 'Agus', 'Sri', 'Eko',
            'Moh.', 'Dewi', 'Nur', 'Wahyu', 'Hadi', 'Bambang', 'Didik', 'Joko',
            'Rina', 'Tri', 'Kuswanto', 'Nanang', 'Endang', 'Sugeng', 'Imam', 'Sunarto',
            'Mulyono', 'Subari', 'Khoirul', 'Anam', 'Fauzi', 'Dwi', 'Slamet', 'Kartika',
            'Ainur', 'Rofiq', 'Hendra', 'Kurniawan', 'Yuni', 'Astuti', 'Wulan', 'Fitri',
            'Lilik', 'Sulastri', 'Zainal', 'Abidin', 'Rudi', 'Hermawan', 'Totok', 'Sunaryo',
            'Wawan', 'Setiawan', 'Hariyanto', 'Sutrisno', 'Purnomo', 'Basuki', 'Rahmad',
            'Mustofa', 'Iwan', 'Fals', 'Ali', 'Mansur', 'Haris', 'Munandar', 'Lukman',
            'Suroso', 'Suwandi', 'Farida', 'Hanum', 'Miftakhul', 'Huda', 'Shohibul',
            'Aminah', 'Kusnan', 'Widodo', 'Purwanto', 'Kusairi', 'Teguh', 'Santoso'
        ];

        // Middle/Last names
        $lastNames = [
            'Santoso', 'Pratama', 'Wibowo', 'Kurniawan', 'Hidayat', 'Saputra', 'Susanto',
            'Wahyu', 'Nugroho', 'Prasetyo', 'Setiawan', 'Firmansyah', 'Putra', 'Ramadhan',
            'Purnomo', 'Arifin', 'Subagyo', 'Wijaya', 'Sugiarto', 'Darmawan', 'Utomo',
            'Kusuma', 'Astuti', 'Lestari', 'Wulandari', 'Rahmawati', 'Anggraini', 'Fitriani',
            'Handayani', 'Marlina', 'Kurniasari', 'Puspitasari', 'Ningrum', 'Maharani',
            'Sulistyowati', 'Riyadi', 'Hakim', 'Maulana', 'Anwar', 'Sudarsono', 'Gunawan',
            'Suwardi', 'Sutanto', 'Pambudi', 'Pamungkas', 'Rohman', 'Irawan', 'Haryadi'
        ];

        // Street types & patterns
        $streetPrefixes = [
            'Jl. Raya No. ', 'Jl. Sunan Giri No. ', 'Jl. Sunan Drajat No. ', 'Jl. Veteran No. ',
            'Jl. Basuki Rahmat No. ', 'Jl. Pahlawan No. ', 'Jl. Kyai Sahid No. ', 'Jl. Panglima Sudirman No. ',
            'Dsn. Krajan, RT 01 RW 01, ', 'Dsn. Krajan, RT 02 RW 01, ', 'Dsn. Melik, RT 03 RW 02, ',
            'Dsn. Karanganyar, RT 01 RW 02, ', 'Dsn. Geneng, RT 02 RW 03, ', 'Dsn. Sawahan, RT 01 RW 01, ',
            'Dsn. Jetis, RT 04 RW 02, ', 'Dsn. Glodog, RT 02 RW 01, ', 'Dsn. Mulyorejo, RT 03 RW 01, ',
            'Dsn. Sumber, RT 01 RW 04, ', 'Dsn. Tambak, RT 02 RW 02, ', 'Dsn. Kedung, RT 03 RW 03, '
        ];

        // Plate rear prefixes starting strictly with H, I, J, or K
        // Using authentic 2-letter and 3-letter combinations for Lamongan Plat S
        $plateRearPrefixLetters = ['H', 'I', 'J', 'K'];
        $alphabetLetters = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'J', 'K', 'L', 'M', 'N', 'P', 'R', 'S', 'T', 'U', 'W', 'Y', 'Z'];

        // Determine distribution across 27 kecamatan:
        // Total 200 participants:
        // 11 subdistricts get 8 participants (88)
        // 16 subdistricts get 7 participants (112)
        // 88 + 112 = 200 exactly.
        $kecamatanQuota = [];
        $index = 0;
        foreach (array_keys($kecamatanVillages) as $kecName) {
            $kecamatanQuota[$kecName] = ($index < 11) ? 8 : 7;
            $index++;
        }

        $usedNopols = [];
        $usedNames = [];
        $participants = [];

        foreach ($kecamatanQuota as $kecName => $quota) {
            $villages = $kecamatanVillages[$kecName];
            $villageCount = count($villages);

            for ($i = 0; $i < $quota; $i++) {
                // Generate unique realistic name
                do {
                    $fn = $firstNames[array_rand($firstNames)];
                    $ln = $lastNames[array_rand($lastNames)];
                    // Optionally add third name component for extra realism
                    if (rand(1, 10) > 6) {
                        $mn = $lastNames[array_rand($lastNames)];
                        $fullName = "$fn $mn $ln";
                    } else {
                        $fullName = "$fn $ln";
                    }
                } while (isset($usedNames[$fullName]));
                $usedNames[$fullName] = true;

                // Generate unique nopol:
                // S [100-9999] [H/I/J/K][A-Z]{1,2}
                do {
                    $plateNum = rand(101, 9899);
                    $firstSuffixLetter = $plateRearPrefixLetters[array_rand($plateRearPrefixLetters)];
                    $secondSuffixLetter = $alphabetLetters[array_rand($alphabetLetters)];

                    // 40% chance of 3-letter suffix (e.g., S 3421 JAA, S 1829 KBA)
                    if (rand(1, 10) <= 4) {
                        $thirdSuffixLetter = $alphabetLetters[array_rand($alphabetLetters)];
                        $suffix = $firstSuffixLetter . $secondSuffixLetter . $thirdSuffixLetter;
                    } else {
                        $suffix = $firstSuffixLetter . $secondSuffixLetter;
                    }

                    $nopol = "S {$plateNum} {$suffix}";
                } while (isset($usedNopols[$nopol]));
                $usedNopols[$nopol] = true;

                // Generate realistic address in the designated village & kecamatan
                $village = $villages[$i % $villageCount];
                $streetType = $streetPrefixes[array_rand($streetPrefixes)];

                if (str_starts_with($streetType, 'Jl.')) {
                    $rt = rand(1, 8);
                    $rw = rand(1, 4);
                    $alamat = $streetType . rand(1, 150) . ", RT " . sprintf('%02d', $rt) . " RW " . sprintf('%02d', $rw) . ", {$village}, Kec. {$kecName}";
                } else {
                    $alamat = $streetType . "{$village}, Kec. {$kecName}";
                }

                $participants[] = [
                    'nama' => $fullName,
                    'nopol' => $nopol,
                    'alamat' => $alamat,
                    'kecamatan' => $kecName,
                    'apakah_menang' => false,
                    'urutan_menang' => null,
                    'predetermined_winner_order' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        // Chunk insert for high performance
        foreach (array_chunk($participants, 50) as $chunk) {
            PesertaLotre::insert($chunk);
        }

        $this->command->info("Berhasil melakukan seeding 200 data peserta lotre khas Lamongan ke 27 kecamatan!");
    }
}
