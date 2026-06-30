<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use Illuminate\Support\Facades\Schedule;

// Menjadwalkan pembersihan akun guest setiap hari (pukul 00:00)
Schedule::command('app:cleanup-guests')->daily();
