<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PesertaLotre;

class AdHocPesertaLotSeeder extends Seeder
{
    public function run()
    {
        for ($i = 110; $i < 2010; $i++) {
            PesertaLotre::create([
                'nama' => 'Peserta' . ($i + 1),
                'nopol' => 'L' . str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                'apakah_menang' => false,
            ]);
        }
    }
}
