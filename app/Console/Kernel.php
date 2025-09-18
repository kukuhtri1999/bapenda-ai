<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        // register custom test command
        \App\Console\Commands\TestClassifyChats::class,
        \App\Console\Commands\DebugRunAnalytics::class,
        \App\Console\Commands\BackfillKnowledgeBaseSearch::class,
        \App\Console\Commands\TestRagQuery::class,
        \App\Console\Commands\TestChatAsk::class,
        \App\Console\Commands\TestKbAnswer::class,
        \App\Console\Commands\IndexKnowledgeBaseToVector::class,
        \App\Console\Commands\TestNewRagSystem::class,
    ];

    protected function schedule(Schedule $schedule)
    {
        // ...existing scheduled tasks
    }

    protected function commands()
    {
        // ...existing command registration if any
    }
}
