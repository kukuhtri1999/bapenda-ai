<?php

namespace Database\Factories;

use App\Models\ChatMessage;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ChatMessageFactory extends Factory
{
  protected $model = ChatMessage::class;

  public function definition(): array
  {
    // casual Indonesian/Javanese message templates
    $userTemplates = [
      'Tolong dong info cara bayar pajak motor di Samsat Lamongan?',
      'Jam buka Samsat hari ini sampai jam berapa ya?',
      'Saya mau cek denda pajak, nopol {nopol}, bisa bantu?',
      'Ada syarat apa buat balik nama motor?',
      'Lha, berapa sih tarif pajak buat motor 250cc?',
      'Permisi, proses perpanjangan STNK butuh apa saja?',
      'Mboten saged online, napa wonten masalah sistem?',
      'Kulo arep takon, carane ngurus STNK sing ilang?',
      'Bro, pake plat L apa kudu ke kantor langsung?',
      'Apa bisa bayar pajak lewat online banking? gimana caranya?'
    ];

    $assistantTemplates = [
      'Silakan datang ke kantor Samsat Lamongan jam operasional, bawa KTP, STNK asli, dan BPKB bila diperlukan.',
      'Untuk cek denda bisa hubungi kami dengan nopol dan 5 digit no rangka terakhir.',
      'Bisa lewat e-Samsat, atau datang langsung ke loket, pilih yang paling mudah buat Anda.',
      'Prosedur balik nama memerlukan KTP, STNK, BPKB, kwitansi jual beli, dan surat balik nama dari samsat.',
      'Iya bisa, banyak bank mendukung pembayaran pajak via virtual account atau e-billing.',
      'Mohon maaf jika sistem sedang sibuk, coba kembali beberapa saat lagi atau datang ke kantor.',
    ];

    // choose role (favor user); generate inline answer/topic/sentiment when user
    $isUser = $this->faker->boolean(75); // 75% user messages
    $role = $isUser ? 'user' : 'assistant';

    $nopol = 'L ' . $this->faker->numberBetween(100, 9999) . ' ' . strtoupper($this->faker->randomLetter());

    $template = $isUser ? $this->faker->randomElement($userTemplates) : $this->faker->randomElement($assistantTemplates);
    $content = str_replace('{nopol}', $nopol, $template);

    // randomize with short colloquial additions
    if ($isUser && $this->faker->boolean(40)) {
      $extras = ['Makasi ya', 'Matur nuwun', 'Tolong banget', 'Cepet dibalas dong', 'Gimana caranya?'];
      $content .= ' - ' . $this->faker->randomElement($extras);
    }

    $answer = null;
    $topic = null;
    $sentiment = null;
    if ($isUser) {
      // simple synthetic classification
      $lc = mb_strtolower($content);
      if (str_contains($lc, 'cara bayar') || str_contains($lc, 'bayar pajak')) {
        $topic = 'tanya_cara_bayar_pajak';
      } elseif (str_contains($lc, 'syarat')) {
        $topic = 'tanya_syarat_bayar_pajak';
      } elseif (str_contains($lc, 'denda') || str_contains($lc, 'telat')) {
        $topic = 'denda_keterlambatan';
      } elseif (str_contains($lc, 'stnk')) {
        $topic = 'informasi_stnk';
      } elseif (str_contains($lc, 'samsat keliling')) {
        $topic = 'tanya_samsat_keliling';
      } elseif (str_contains($lc, 'jam') && str_contains($lc, 'buka')) {
        $topic = 'lokasi_jam_operasional';
      } else {
        $topic = 'lain_lain';
      }
      $sentiment = str_contains($lc, 'tolong') || str_contains($lc, 'gimana') ? 'neutral' : 'neutral';
      if (str_contains($lc, 'terima kasih') || str_contains($lc, 'makasi')) $sentiment = 'positive';
      if (str_contains($lc, 'susah') || str_contains($lc, 'error') || str_contains($lc, 'ribet')) $sentiment = 'negative';

      // basic canned answer
      $answer = $this->faker->randomElement($assistantTemplates);
    }

    return [
      'chat_id' => $this->faker->numberBetween(1, 800),
      'role' => $role,
      'content' => $content,
      'answer' => $isUser ? $answer : null,
      'topic' => $isUser ? $topic : null,
      'sentiment' => $isUser ? $sentiment : null,
      'metadata' => ['nopol' => $nopol],
      'sent_at' => now()->subMinutes($this->faker->numberBetween(0, 60 * 24 * 365)),
    ];
  }
}
