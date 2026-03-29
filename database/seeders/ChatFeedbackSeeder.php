<?php

namespace Database\Seeders;

use App\Models\ChatFeedback;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ChatFeedbackSeeder extends Seeder
{
  /**
   * Seed the chat_feedback table with sample reviews.
   */
  public function run(): void
  {
    // Raw review data: [rating, feedback_text]
    $reviews = [
      [5, 'Mantap banget! Cek pajak jadi sat set nggak perlu ribet lagi.'],
      [5, 'Gokil sih SALMA, balesnya cepet banget kayak kilat. Top pokoknya!'],
      [4, 'Aplikasinya oke, tapi kalau bisa warnanya jangan terlalu kaku ya.'],
      [5, 'Admin bot-nya pinter, tanya apa aja soal pajak langsung dijawab bener.'],
      [5, 'Sumpah ngebantu banget buat liat jadwal Samsat Keliling biar nggak nyasar.'],
      [5, 'Keren pol! Sekarang urus pajak kendaraan di Lamongan nggak pakai lama.'],
      [4, 'Udah bagus sih.'],
      [5, 'Chat jam berapa aja tetep dilayani, bener-bener asisten digital idaman.'],
      [5, 'Info syarat balik nama lengkap banget, nggak perlu bolak-balik kantor samsat lagi.'],
      [5, 'Bener-bener solusi buat kaum mager yang pengen tau pajak motornya.'],
      [4, 'Mayan fast respon dan infonya valid, namun masih agak lama 30 detik an jawabnya.'],
      [5, 'Praktis parah! Cuma modal chat doang info pajak langsung muncul.'],
      [5, 'Suka banget sama sistemnya, nggak bertele-tele dan langsung ke intinya.'],
      [4, 'Okelah buat tanya-tanya, sangat membantu daripada nunggu di loket info.'],
      [5, 'Inovasi jempolan dari Samsat Lamongan.'],
      [5, 'Tampilannya simpel, enteng banget dibuka di HP jadul sekalipun.'],
      [4, 'Sangat membantu masyarakat kecil.'],
      [5, 'Gak perlu bingung lagi kalau telat pajak.'],
      [5, 'Sering-sering bikin fitur keren kayak gini, ngebantu banget asli rek.'],
      [4, 'Bagus sih, tapi kadang responnya rada delay dikit.'],
      [5, 'Ngebantu banget buat bapak-bapak yang nggak mau ribet tanya sana-sini.'],
      [5, 'Asli, ini chatbot paling berguna yang pernah tak coba.'],
      [4, 'Lumayan banget buat cek denda, jadi bisa persiapan uang sebelum bayar.'],
      [3, 'Kadang bot-nya gagal paham kalau nanyanya pake bahasa gaul.'],
      [3, 'Update datanya dong biar makin jos, overall udah lumayan oke.'],
      [5, 'Aplikasi iki pancen jos tenan.'],
      [4, 'Matur nuwun SALMA.'],
      [5, 'Bot e pinter pol, tak takoni opo wae langsung dijawab cepet.'],
      [5, 'Wong Lamongan wajib nyoba.'],
      [4, 'Apik seh, mugo iso tambah cepet maneh respon e.'],
      [4, 'Kadang sek terlalu bertele-tele informasi ne.'],
      [4, 'Kadang masih bingung sama tampilannya.'],
      [4, 'Mungkin bisa di update biar responnya lebih cepat lagi.'],
      [5, 'Penak tenan, kyk ngomong ng wong asli.'],
    ];

    // Realistic Lamongan-area names
    $names = [
      'Budi Santoso',
      'Siti Rahayu',
      'Ahmad Fauzi',
      'Dewi Setyawati',
      'Rizki Pratama',
      'Nur Aini',
      'Agus Widodo',
      'Sri Mulyani',
      'Hendra Kurniawan',
      'Rina Wulandari',
      'Eko Prasetyo',
      'Yuli Astuti',
      'Doni Firmansyah',
      'Lestari Ningrum',
      'Wahyu Sejati',
      'Fitri Handayani',
      'Teguh Santoso',
      'Mela Puspita',
      'Fajar Nugroho',
      'Ayu Marlina',
      'Slamet Riyadi',
      'Wulan Sari',
      'Dimas Arifin',
      'Indah Permata',
      'Bambang Sugiarto',
      'Retno Kusuma',
      'Joko Purnomo',
      'Laili Fitriyah',
      'Surya Darmawan',
      'Nia Rahmawati',
      'Arif Budiman',
      'Putri Ramadhani',
      'Mahmud Hidayat',
      'Suci Wulandari',
    ];

    // Lamongan nopol prefix: S (Bojonegoro/Lamongan)
    $nopolSeries = ['S', 'W'];
    $nopolLetters = ['AA', 'AB', 'AC', 'BA', 'BB', 'BC', 'CA', 'DA', 'EA', 'FA'];

    // Base date: spread over the last 90 days
    $baseDate = Carbon::now()->subDays(90);

    $rows = [];
    foreach ($reviews as $i => [$rating, $feedbackText]) {
      $daysOffset   = (int) round($i * (90 / count($reviews)));
      $chatEndedAt  = $baseDate->copy()->addDays($daysOffset)->addHours(rand(7, 21))->addMinutes(rand(0, 59));
      $prefix       = $nopolSeries[array_rand($nopolSeries)];
      $number       = rand(1000, 9999);
      $suffix       = $nopolLetters[array_rand($nopolLetters)];

      $rows[] = [
        'session_id'   => Str::uuid()->toString(),
        'nama'         => $names[$i] ?? $names[$i % count($names)],
        'nopol'        => "{$prefix} {$number} {$suffix}",
        'nomer_wa'     => '08' . rand(1, 9) . rand(1000000, 9999999),
        'rating'       => $rating,
        'feedback_text' => $feedbackText,
        'chat_summary' => null,
        'chat_ended_at' => $chatEndedAt->toDateTimeString(),
        'created_at'   => $chatEndedAt->toDateTimeString(),
        'updated_at'   => $chatEndedAt->toDateTimeString(),
      ];
    }

    ChatFeedback::insert($rows);

    $this->command?->info('✅ Inserted ' . count($rows) . ' feedback reviews.');
  }
}
