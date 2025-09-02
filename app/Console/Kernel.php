<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
  protected $commands = [
    // register custom test command
    \App\Console\Commands\TestClassifyChats::class,
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
