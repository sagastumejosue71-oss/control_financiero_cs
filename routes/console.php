<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Respaldo diario de los datos financieros (cifrados) y la base de datos.
// IMPORTANTE: esto solo se dispara si algo llama a "php artisan schedule:run"
// cada minuto. En Linux se hace con una entrada de cron; en Windows, con el
// Programador de Tareas apuntando al mismo comando. Sin eso, el schedule
// definido aquí no corre solo.
Schedule::command('finanzas:backup')->dailyAt('03:00');
