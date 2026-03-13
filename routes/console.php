<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Agendar atualização de status online dos usuários a cada 5 minutos
Schedule::command('users:update-online-status')->everyFiveMinutes();

// Limpar sessões expiradas e marcar usuários como offline a cada 10 minutos
Schedule::command('sessions:clean-expired')->everyTenMinutes();
