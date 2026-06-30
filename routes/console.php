<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Jadwalkan perintah pengarsipan log secara otomatis
// Fitur quarterly() otomatis akan menjalankan perintah ini setiap:
// - 1 Januari
// - 1 April
// - 1 Juli
// - 1 Oktober
// Tepat pada pukul 00:00.
Schedule::command('logs:archive')->quarterly();