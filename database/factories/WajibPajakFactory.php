<?php

namespace Database\Factories;

use App\Models\WajibPajak;
use Illuminate\Database\Eloquent\Factories\Factory;

class WajibPajakFactory extends Factory
{
    protected $model = WajibPajak::class;

    public function definition(): array
    {
        // Indonesian / Javanese realistic names using faker
        $name = $this->faker->name();

        // Lamongan plate: 'L' prefix with numbers and letters like 'L 1234 AB' or 'L 1234 A'
        $number = $this->faker->numberBetween(100, 9999);
        $suffix = strtoupper($this->faker->randomLetter() . $this->faker->optional()->randomLetter());
        $nopol = 'S ' . $number . ' ' . trim($suffix);

        // Indonesian mobile number formatting
        $phone = '08' . $this->faker->numberBetween(110000000, 999999999);

        return [
            'nama' => $name,
            'nopol' => $nopol,
            'lima_digit_terakhir_no_rangka' => str_pad($this->faker->numberBetween(0, 99999), 5, '0', STR_PAD_LEFT),
            'nomer_wa' => $phone,
        ];
    }
}
