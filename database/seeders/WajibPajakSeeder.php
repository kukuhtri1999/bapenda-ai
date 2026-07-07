<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class WajibPajakSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(ExampleDataSeeder::class);
    }
}
