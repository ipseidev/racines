<?php

declare(strict_types=1);

use App\Jobs\MeasureResumptions;
use App\Jobs\PollTranscription;
use App\Providers\AppServiceProvider;
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

/*
 * Le filet des cadeaux programmés, toutes les minutes.
 *
 * La voie normale reste le report en file posé par `FulfillOrder`. Celui-ci
 * rattrape le cas où ce report n'a pas lieu : une file `sync`, `deferred` ou
 * `background` exécute tout de suite ce qu'on lui demande de différer, et le
 * cadeau part à la seconde du paiement au lieu de l'heure choisie (T-239).
 */
Schedule::command('gifts:dispatch-due')
    ->everyMinute()
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

/*
|--------------------------------------------------------------------------
| Sauvegardes et santé (bloc 16)
|--------------------------------------------------------------------------
|
| L'ordre des deux tâches de sauvegarde n'est pas indifférent : on **nettoie
| avant** de sauvegarder. L'inverse effacerait parfois l'archive de la nuit
| même, quand la rétention tombe pile au moment du passage.
|
*/

/*
 * La garde du mot de passe d'archive tourne **avant** la sauvegarde, pas au
 * démarrage de l'application : sans elle l'archive partirait en clair, mais
 * le site, lui, fonctionne très bien — le faire tomber pour une variable qui
 * ne concerne qu'un travail nocturne transformerait une précaution en panne
 * (T-206).
 */
Schedule::call(fn () => AppServiceProvider::guardBackupPassword())
    ->dailyAt('01:25')
    ->name('garde-mot-de-passe-archive');

Schedule::command('backup:clean')->dailyAt('01:00')->withoutOverlapping();
Schedule::command('backup:run')->dailyAt('01:30')->withoutOverlapping();

/*
 * `backup:monitor` répond à la question que `backup:run` ne pose pas : la
 * dernière archive est-elle **récente et de taille plausible** ? Une
 * sauvegarde qui réussit tous les soirs en écrivant trois kilo-octets est le
 * pire des cas, et c'est le seul que ce contrôle attrape.
 */
Schedule::command('backup:monitor')->dailyAt('07:00');

/*
 * Les contrôles de santé, toutes les minutes.
 *
 * C'est ce qui alimente `/health`, interrogé par Oh Dear. Sans exécution
 * planifiée, l'endpoint rendrait le dernier résultat connu — et une panne de
 * cinq heures s'afficherait en vert.
 */
Schedule::command('health:check')->everyMinute();

/*
 * Le battement que `ScheduleCheck` relit.
 *
 * Sans lui le contrôle est rouge à vie, et il l'était : le planificateur
 * tournait, `health:check` passait toutes les minutes, mais personne ne
 * posait la marque que ce contrôle va chercher — d'où « The schedule did not
 * run yet » par courriel chaque heure, en local comme en production.
 *
 * Un contrôle qui crie tout le temps est un contrôle qu'on finit par filtrer,
 * et le jour où le planificateur tombe pour de bon, le courriel arrive dans
 * un dossier que plus personne ne lit. C'est le seul dégât d'une sonde
 * bloquée au rouge, et il suffit à la réparer tout de suite.
 */
Schedule::command('health:schedule-check-heartbeat')->everyMinute();

// `audit:verify` tourne déjà plus haut, à 04:30. Le contrôle de santé, lui,
// ne regarde que la journée en cours : relire à chaque minute un journal qui
// grossit finirait par faire désactiver le contrôle, ce qui est la vraie panne.

/*
 * Les archives d'export, effacées à l'expiration du lien (bloc 14).
 *
 * Une archive contenant l'intégralité des récits d'une famille, oubliée sur
 * un stockage objet, est une fuite qui attend son heure. La ligne reste, sans
 * son objet : elle prouve qu'un export a été remis.
 */
Schedule::command('exports:expire')->dailyAt('02:30');

/*
 * La remise proactive (R-10.2) : soixante jours avant la fin d'hébergement.
 *
 * Une famille ne pense pas à télécharger ses données ; elle y pense le jour
 * où le service ferme, c'est-à-dire trop tard.
 */
Schedule::command('exports:proactive')->dailyAt('06:00');

/*
 * Les métriques du pilote, chaque nuit (bloc 15).
 *
 * À 03:00, après les sauvegardes et avant que quiconque regarde un tableau
 * de bord. La commande calcule **hier** : une journée en cours donne des
 * chiffres qui bougent à chaque exécution.
 */
Schedule::command('metrics:compute')->dailyAt('03:00')->withoutOverlapping();
