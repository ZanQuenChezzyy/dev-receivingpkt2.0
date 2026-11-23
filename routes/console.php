<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Menjalankan antrean (queue) setiap menit tanpa bentrok
// Ini sangat cocok untuk shared hosting (Hostinger) yang tidak mendukung Supervisor
Schedule::command('queue:work --stop-when-empty')
    ->everyMinute()
    ->withoutOverlapping();

// Sinkronisasi harian data Aging Non-Stock & PD + notifikasi In-App internal (non-email)
Schedule::command('nonstock:check-aging-alerts')
    ->dailyAt('07:30')
    ->withoutOverlapping();
