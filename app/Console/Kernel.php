<?php

namespace App\Console;

use App\Console\Commands\SyncOrderStatus;
use App\Console\Commands\PruneProviderLogs;
use App\Console\Commands\QueueHealthCheck;
use App\Console\Commands\RunAIMaintenanceCommand;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        // EXISTING: Sync pending orders every 5 minutes
        $schedule->command(SyncOrderStatus::class)
            ->everyFiveMinutes()
            ->withoutOverlapping(10)
            ->runInBackground();

        // EXISTING: Prune old provider logs daily
        $schedule->command('logs:prune-provider-logs --days=90')
            ->daily()->at('03:00')->withoutOverlapping();

        // EXISTING: Prune failed jobs weekly
        $schedule->command('queue:prune-failed --hours=168')
            ->daily()->at('02:00');

        // EXISTING: Queue health check
        $schedule->command('queue:health-check')
            ->everyFifteenMinutes()->withoutOverlapping();

        // NEW: AI supplier health check every 30 minutes
        $schedule->command(RunAIMaintenanceCommand::class, ['--health'])
            ->everyThirtyMinutes()
            ->withoutOverlapping()
            ->runInBackground();

        // NEW: Full AI maintenance (quality scoring + pricing) every 6 hours
        $schedule->command(RunAIMaintenanceCommand::class)
            ->everySixHours()
            ->withoutOverlapping()
            ->runInBackground();
    }

    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');
        require base_path('routes/console.php');
    }
}
