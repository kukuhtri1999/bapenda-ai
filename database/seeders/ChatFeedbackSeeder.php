<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ChatFeedbackSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(ExampleDataSeeder::class);
    }
}
