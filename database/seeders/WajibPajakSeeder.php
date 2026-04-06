<?php

namespace Database\Seeders;

use App\Models\WajibPajak;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class WajibPajakSeeder extends Seeder
{
  /**
   * Lamongan-area first names (common Javanese + Islamic combination).
   */
  private array $firstNames = [
    'Ahmad',
    'Muhammad',
    'Abdul',
    'Nur',
    'Siti',
    'Dewi',
    'Rina',
    'Sri',
    'Budi',
    'Eko',
    'Agus',
    'Wahyu',
    'Hendra',
    'Doni',
    'Rizki',
    'Fajar',
    'Teguh',
    'Arif',
    'Bambang',
    'Slamet',
    'Joko',
    'Yuli',
    'Fitri',
    'Ayu',
    'Wulan',
    'Indah',
    'Laili',
    'Suci',
    'Retno',
    'Putri',
    'Mela',
    'Nita',
    'Rudi',
    'Dimas',
    'Bayu',
    'Imam',
    'Fauzi',
    'Khoirul',
    'Luthfi',
    'Zainal',
    'Hasan',
    'Husein',
    'Ali',
    'Umar',
    'Fatimah',
    'Khadijah',
    'Aisyah',
    'Hanik',
    'Sulastri',
    'Mujiati',
    'Winarsih',
    'Subakti',
    'Sutejo',
    'Suprapto',
    'Mulyono',
    'Sumardi',
    'Narwanto',
    'Purwanto',
    'Setiawan',
    'Gunawan',
  ];

  private array $lastNames = [
    'Santoso',
    'Rahayu',
    'Kusuma',
    'Wulandari',
    'Pratama',
    'Aini',
    'Widodo',
    'Setyawati',
    'Kurniawan',
    'Firmansyah',
    'Ningrum',
    'Sejati',
    'Handayani',
    'Astuti',
    'Nugroho',
    'Marlina',
    'Riyadi',
    'Puspita',
    'Arifin',
    'Permata',
    'Sugiarto',
    'Fitriyah',
    'Purnomo',
    'Rahmawati',
    'Hidayat',
    'Budiman',
    'Ramadhani',
    'Prasetyo',
    'Lestari',
    'Susanto',
    'Wahyudi',
    'Salim',
    'Mansur',
    'Hasyim',
    'Maulana',
    'Firdaus',
    'Anwar',
    'Basuki',
    'Hartono',
    'Darmawan',
    'Mukti',
    'Saputro',
    'Hakim',
    'Yusuf',
    'Ismail',
    'Halim',
  ];

  /**
   * Valid 3-letter nopol suffixes for S-plate Lamongan area that end with J/K/L/M.
   * Pattern: [any letter][any letter][J|K|L|M]
   */
  private array $nopolSuffix = [
    // Ends with J
    'BAJ',
    'CAJ',
    'DAJ',
    'EAJ',
    'FAJ',
    'GAJ',
    'HAJ',
    'JAJ',
    'KAJ',
    // Ends with K
    'ABK',
    'BBK',
    'CBK',
    'DBK',
    'EBK',
    'FBK',
    'GBK',
    'HBK',
    'IBK',
    // Ends with L
    'ACL',
    'BCL',
    'CCL',
    'DCL',
    'ECL',
    'FCL',
    'GCL',
    'HCL',
    'ICL',
    // Ends with M
    'ADM',
    'BDM',
    'CDM',
    'DDM',
    'EDM',
    'FDM',
    'GDM',
    'HDM',
    'IDM',
    // Mixed — as shown in examples: JSR(wrong example?), KSE, LIA, MTI
    // User examples start with J/K/L/M — keep both conventions
    'JSR',
    'KSE',
    'LIA',
    'MTI',
    'KAM',
    'LAJ',
    'MAK',
    'JAL',
    'KBL',
    'LCK',
    'MCJ',
    'JAK',
    'KAL',
    'LAM',
    'MAJ',
  ];

  /**
   * WA provider prefixes (Telkomsel/Indosat/XL/Tri) per provider.
   * All are valid Indonesian 4-digit WA prefixes.
   */
  private array $waPrefixes = [
    '0812',
    '0813',
    '0821',
    '0822',
    '0823', // Telkomsel
    '0852',
    '0853',
    '0851',                  // Telkomsel simpati
    '0815',
    '0816',
    '0855',
    '0856',
    '0857',
    '0858', // Indosat
    '0817',
    '0818',
    '0819',
    '0859',
    '0877',
    '0878', // XL
    '0895',
    '0896',
    '0897',
    '0898',
    '0899',  // Tri
    '0831',
    '0832',
    '0833',
    '0838',          // Axis
  ];

  public function run(): void
  {
    $usedNopol = [];
    $usedWa    = [];
    $rows      = [];

    // Spread created_at over the last 180 days
    $baseDate = Carbon::now()->subDays(180);

    for ($i = 0; $i < 60; $i++) {
      $rows[] = [
        'nama'       => $this->randomName($i),
        'nopol'      => $this->uniqueNopol($usedNopol),
        'nomer_wa'   => $this->uniqueWa($usedWa),
        'created_at' => $baseDate->copy()->addDays((int) ($i * 3))->toDateTimeString(),
        'updated_at' => $baseDate->copy()->addDays((int) ($i * 3))->toDateTimeString(),
      ];
    }

    WajibPajak::insert($rows);

    $this->command?->info('✅ Inserted ' . count($rows) . ' wajib pajak records.');
  }

  // ── Helpers ─────────────────────────────────────────────────────────────

  private function randomName(int $seed): string
  {
    $first = $this->firstNames[$seed % count($this->firstNames)];
    $last  = $this->lastNames[($seed * 7 + 3) % count($this->lastNames)];

    // ~30 % chance of a single-word name (common in Javanese culture)
    if ($seed % 3 === 0) {
      return $first;
    }

    return "{$first} {$last}";
  }

  private function uniqueNopol(array &$used): string
  {
    do {
      $number = rand(1000, 9999);
      $suffix = $this->nopolSuffix[array_rand($this->nopolSuffix)];
      $nopol  = "S {$number} {$suffix}";
    } while (in_array($nopol, $used, true));

    $used[] = $nopol;
    return $nopol;
  }

  private function uniqueWa(array &$used): string
  {
    do {
      $prefix = $this->waPrefixes[array_rand($this->waPrefixes)];
      $suffix = str_pad((string) rand(0, 99999999), 8, '0', STR_PAD_LEFT);
      $wa     = $prefix . $suffix;
    } while (in_array($wa, $used, true));

    $used[] = $wa;
    return $wa;
  }
}
