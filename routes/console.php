<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Un cobro pasa de "Por vencer" a "Vencido" y "Moroso" solo por el paso del tiempo.
Schedule::command('charges:refresh-status')->dailyAt('01:00');
