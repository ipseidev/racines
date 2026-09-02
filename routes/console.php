<?php

declare(strict_types=1);

use App\Jobs\PollTranscription;
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

// Filet du rappel de transcription : un webhook perdu laisserait une histoire
// enregistrée sans texte, et personne ne le saurait.
Schedule::job(new PollTranscription)
    ->everyMinute()
    ->withoutOverlapping();

// La corbeille tient sa promesse : trente jours, puis la suppression a lieu
// sans nouvelle demande — le narrateur a déjà décidé (bloc 07 §6.5).
Schedule::command('stories:purge-trashed')
    ->daily()
    ->withoutOverlapping();
