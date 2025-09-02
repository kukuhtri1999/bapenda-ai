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

    // choose role
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

    return [
      'chat_id' => $this->faker->numberBetween(1, 800),
      'role' => $role,
      'content' => $content,
      'metadata' => ['nopol' => $nopol],
      'sent_at' => now()->subMinutes($this->faker->numberBetween(0, 60 * 24 * 365)),
    ];
  }
}
