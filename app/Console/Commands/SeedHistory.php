<?php

namespace App\Console\Commands;

use Database\Seeders\ExampleDataSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SeedHistory extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seed:history';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed database with historical Wajib Pajak, Chat sessions, and Feedbacks (September 2025 - July 16, 2026)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting historical data seeding...');

        $beforeWp = DB::table('wajib_pajak')->count();
        $beforeChats = DB::table('chats')->count();
        $beforeFeedback = DB::table('chat_feedback')->count();

        // Run the seeder
        $this->call(ExampleDataSeeder::class);

        $afterWp = DB::table('wajib_pajak')->count();
        $afterChats = DB::table('chats')->count();
        $afterFeedback = DB::table('chat_feedback')->count();

        $this->info('=========================================');
        $this->info('✅ Historical seeding finished successfully!');
        $this->info("- Wajib Pajak : {$afterWp} records");
        $this->info("- Chat Sessions: {$afterChats} records");
        $this->info("- Chat Feedback: {$afterFeedback} records");
        $this->info('=========================================');

        return self::SUCCESS;
    }
}
