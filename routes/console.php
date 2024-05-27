<?php

use Illuminate\Foundation\Inspiring;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

// Artisan::command('inspire', function () {
//     $this->comment(Inspiring::quote());
// })->describe('Display an inspiring quote');

Schedule::command('database-backup:cron')->everyFiveMinutes();

// $schedule->command('database-backup:cron')
//     ->cron('0 0 * * 1,3,5'); // Runs at midnight (00:00) on Mondays, Wednesdays, and Fridays
