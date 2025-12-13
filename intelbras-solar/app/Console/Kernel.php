<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('intelbras:verificar-geracao')->everyFiveMinutes()->between('6:00', '20:00');

        // Schedule dinâmico para envio de resumo
        $interval = env('SCHEDULE_INTERVAL', 1);

        $scheduleCommand = $schedule->command('telegram:enviar-resumo')->between('6:00', '20:00');

        switch ($interval) {
            case 1:
                $scheduleCommand->hourly();
                break;
            case 2:
                $scheduleCommand->everyTwoHours();
                break;
            case 3:
                $scheduleCommand->everyThreeHours();
                break;
            case 4:
                $scheduleCommand->everyFourHours();
                break;
            case 5:
                $scheduleCommand->cron('0 */5 * * *');
                break;
            case 6:
                $scheduleCommand->everySixHours();
                break;
            case 12:
                $scheduleCommand->cron('0 */12 * * *');
                break;
            case 24:
                $scheduleCommand->daily()->at('12:00');
                break;
            default:
                $scheduleCommand->hourly();
        }
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
