<?php

declare(strict_types=1);

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Tareas programadas
|--------------------------------------------------------------------------
| points:expire diario: genera los vencimientos de los lotes caducados.
| En fases posteriores se agregan points:notify-expiring, redemptions:expire
| y backup:run / backup:clean.
*/

Schedule::command('points:expire')->dailyAt('03:00')->withoutOverlapping();
