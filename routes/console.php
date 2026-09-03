<?php

declare(strict_types=1);

use App\Jobs\MeasureResumptions;
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

// Le résumé du matin, pour les projets en notification différée : un SMS à
// 23 h chez une personne de 85 ans n'est pas une bonne nouvelle (bloc 08).
Schedule::command('reactions:send-digests')
    ->dailyAt('09:00')
    ->withoutOverlapping();

// Le moteur de complétion : onze règles, toutes les heures à la minute sept.
// Décalé de l'heure ronde, où tout ce qui tourne sur la machine se réveille
// en même temps (bloc 09).
Schedule::command('engine:tick')
    ->cron((string) config('product.engine.tick_cron'))
    ->withoutOverlapping();

// Ce que les relances ont produit. Sans cette mesure, le moteur ne serait
// qu'un émetteur de messages.
Schedule::job(new MeasureResumptions)
    ->hourly()
    ->withoutOverlapping();

// Les coordonnées d'un narrateur qui n'a jamais dit oui partent au bout de
// trente jours : il les a reçues d'un proche, il n'a pas choisi de nous les
// confier (bloc 10 §6.5).
Schedule::command('narrators:delete-unaccepted-contacts')
    ->daily()
    ->withoutOverlapping();

/*
 * L'intégrité du journal d'audit, tous les jours.
 *
 * Une vérification qu'il faut penser à lancer n'est pas une vérification. Le
 * trigger empêche l'altération accidentelle ; cette commande détecte
 * l'altération délibérée, la seule qui compte, et elle ne sert que si elle
 * tourne sans qu'on y pense.
 */
Schedule::command('audit:verify')
    ->dailyAt('04:30')
    ->withoutOverlapping();

/*
 * La maturité des livres, et la sortie honorable.
 *
 * Tôt le matin, avant que les familles ne regardent leur jauge — et
 * quotidiennement, parce que les échéances M+12 et M+15 sont des promesses de
 * vente, pas des rappels qu'on pense à envoyer.
 */
Schedule::command('books:evaluate')
    ->dailyAt('05:00')
    ->withoutOverlapping();
