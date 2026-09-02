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
| Tâches planifiées
|--------------------------------------------------------------------------
|
| Le service `scheduler` de `compose.yaml` les exécute en local ; Forge s'en
| charge en production (bloc 16).
|
*/

// Toutes les cinq minutes : un créneau de 9 h ne doit pas devenir 9 h 55
// parce qu'un projet a mis du temps (décision T-28).
Schedule::command('prompts:dispatch-due')
    ->everyFiveMinutes()
    ->withoutOverlapping();
