<?php

declare(strict_types=1);

use App\Actions\ScheduleNextPrompt;
use App\Enums\Cadence;
use App\Enums\ProjectStatus;
use App\Enums\PromptSlot;
use App\Models\Project;
use Carbon\CarbonImmutable;

function schedule(Project $project, ?CarbonImmutable $from = null): ?CarbonImmutable
{
    return app(ScheduleNextPrompt::class)->handle($project, $from);
}

it('envoie la première question dès le lendemain, au créneau choisi', function (): void {
    // Mardi 8 septembre 2026, 15 h à Paris.
    $this->travelTo(CarbonImmutable::parse('2026-09-08 15:00', 'Europe/Paris'));

    $project = Project::factory()->create([
        'prompt_day' => 1,
        'prompt_slot' => PromptSlot::Morning,
        'next_prompt_at' => null,
    ]);

    $next = schedule($project);

    expect($next)->not->toBeNull();

    $paris = $next->setTimezone('Europe/Paris');

    // Le lendemain à 9 h, et non lundi prochain : le dossier veut le premier
    // enregistrement sous 72 heures.
    expect($paris->toDateString())->toBe('2026-09-09')
        ->and($paris->hour)->toBe(9)
        ->and($next->diffInHours(now()))->toBeLessThan(72);
});

it('envoie ensuite chaque semaine, le jour et le créneau choisis, dans le fuseau du projet', function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-09-09 09:05', 'Europe/Paris'));

    $project = Project::factory()->create([
        'prompt_day' => 3,           // mercredi
        'prompt_slot' => PromptSlot::Evening,
        'cadence' => Cadence::Weekly,
        'next_prompt_at' => now(),
    ]);

    $paris = schedule($project)?->setTimezone('Europe/Paris');

    expect($paris?->dayOfWeekIso)->toBe(3)
        ->and($paris?->hour)->toBe(18)
        ->and($paris?->toDateString())->toBe('2026-09-16');
});

it('ne décale pas l’heure locale au changement d’heure d’octobre', function (): void {
    // Vendredi 23 octobre 2026 : l'heure d'hiver arrive le dimanche 25.
    $this->travelTo(CarbonImmutable::parse('2026-10-23 09:05', 'Europe/Paris'));

    $project = Project::factory()->create([
        'prompt_day' => 5,           // vendredi
        'prompt_slot' => PromptSlot::Morning,
        'next_prompt_at' => now(),
    ]);

    $next = schedule($project);
    $paris = $next?->setTimezone('Europe/Paris');

    expect($paris?->toDateString())->toBe('2026-10-30')
        // Toujours 9 h **pour le narrateur**, même si l'écart avec UTC a changé.
        ->and($paris?->hour)->toBe(9)
        ->and($next?->hour)->toBe(8);
});

it('espace de quinze jours quand la cadence le demande', function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-09-09 09:05', 'Europe/Paris'));

    $project = Project::factory()->create([
        'prompt_day' => 3,
        'prompt_slot' => PromptSlot::Morning,
        'cadence' => Cadence::Biweekly,
        'next_prompt_at' => now(),
    ]);

    expect(schedule($project)?->setTimezone('Europe/Paris')->toDateString())->toBe('2026-09-23');
});

/*
|--------------------------------------------------------------------------
| Les rythmes intra-semaine
|--------------------------------------------------------------------------
|
| Le jour choisi n'est plus le seul jour d'envoi mais le premier : deux
| questions par semaine ajoutent un jour trois jours plus loin, trois en
| ajoutent deux, écartés de deux jours. Ce qui compte et que ces tests
| gardent, c'est qu'aucune paire ne se retrouve à vingt-quatre heures
| d'écart — deux questions collées se lisent comme une relance.
|
*/

it('pose la seconde question de la semaine trois jours après la première', function (): void {
    // Mardi 8 septembre 2026, 9 h 05 : la question du mardi vient de partir.
    $this->travelTo(CarbonImmutable::parse('2026-09-08 09:05', 'Europe/Paris'));

    $project = Project::factory()->create([
        'prompt_day' => 2,           // mardi
        'prompt_slot' => PromptSlot::Morning,
        'cadence' => Cadence::TwiceWeekly,
        'next_prompt_at' => now(),
    ]);

    $paris = schedule($project)?->setTimezone('Europe/Paris');

    // Vendredi, et non mardi prochain.
    expect($paris?->toDateString())->toBe('2026-09-11')
        ->and($paris?->hour)->toBe(9);
});

it('revient au jour choisi après la seconde question de la semaine', function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-09-11 09:05', 'Europe/Paris'));

    $project = Project::factory()->create([
        'prompt_day' => 2,
        'prompt_slot' => PromptSlot::Morning,
        'cadence' => Cadence::TwiceWeekly,
        'next_prompt_at' => now(),
    ]);

    expect(schedule($project)?->setTimezone('Europe/Paris')->toDateString())
        ->toBe('2026-09-15');
});

it('étale trois questions par semaine sans jamais en coller deux', function (
    string $depuis,
    string $attendu,
): void {
    $this->travelTo(CarbonImmutable::parse($depuis.' 09:05', 'Europe/Paris'));

    $project = Project::factory()->create([
        'prompt_day' => 2,           // mardi, jeudi, samedi
        'prompt_slot' => PromptSlot::Morning,
        'cadence' => Cadence::ThriceWeekly,
        'next_prompt_at' => now(),
    ]);

    expect(schedule($project)?->setTimezone('Europe/Paris')->toDateString())
        ->toBe($attendu);
})->with([
    'mardi → jeudi' => ['2026-09-08', '2026-09-10'],
    'jeudi → samedi' => ['2026-09-10', '2026-09-12'],
    'samedi → mardi suivant' => ['2026-09-12', '2026-09-15'],
]);

it('n’envoie jamais deux questions coup sur coup quand le jour change', function (): void {
    // La question du mercredi vient de partir, et le jour passe au jeudi.
    $this->travelTo(CarbonImmutable::parse('2026-09-09 09:05', 'Europe/Paris'));

    $project = Project::factory()->create([
        'prompt_day' => 4,           // jeudi, à partir de maintenant
        'prompt_slot' => PromptSlot::Morning,
        'cadence' => Cadence::Weekly,
        'next_prompt_at' => now(),
    ]);

    $next = schedule($project);

    // Jeudi **prochain**, pas demain : l'écart minimal d'une cadence
    // hebdomadaire est de sept jours, et il prime sur le jour choisi.
    expect($next?->setTimezone('Europe/Paris')->toDateString())->toBe('2026-09-17')
        ->and($next?->greaterThanOrEqualTo(now()->addDays(7)))->toBeTrue();
});

it('ne saute pas un cycle pour les quelques minutes qui séparent l’envoi du créneau', function (): void {
    /*
     * Le cœur du calcul en jours. La question est partie à 9 h 05 — le
     * planificateur tourne après l'envoi — et le créneau du mercredi suivant
     * est 9 h 00. Comparé à l'heure près, il est « en avance » de cinq
     * minutes, et l'échéance partait une semaine plus loin.
     */
    $this->travelTo(CarbonImmutable::parse('2026-09-09 09:05', 'Europe/Paris'));

    $project = Project::factory()->create([
        'prompt_day' => 3,
        'prompt_slot' => PromptSlot::Morning,
        'cadence' => Cadence::Weekly,
        'next_prompt_at' => now(),
    ]);

    expect(schedule($project)?->setTimezone('Europe/Paris')->toDateString())
        ->toBe('2026-09-16');
});

it('respecte une pause et ne planifie rien avant sa fin', function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-09-09 09:05', 'Europe/Paris'));

    $project = Project::factory()->create([
        'prompt_day' => 3,
        'prompt_slot' => PromptSlot::Morning,
        'next_prompt_at' => now(),
        'paused_until' => now()->addMonth(),
    ]);

    $next = schedule($project);

    expect($next?->greaterThan($project->paused_until))->toBeTrue();
});

it('ne planifie rien après la fin de la collecte', function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-09-09 09:05', 'Europe/Paris'));

    $project = Project::factory()->create([
        'prompt_day' => 3,
        'prompt_slot' => PromptSlot::Morning,
        'next_prompt_at' => now(),
        'collection_ends_at' => now()->addDays(3),
    ]);

    expect(schedule($project))->toBeNull();
});

it('ne planifie rien pour un projet en pause définitive, gelé ou terminé', function (ProjectStatus $status): void {
    $project = Project::factory()->create(['status' => $status, 'next_prompt_at' => now()]);

    expect(schedule($project))->toBeNull();
})->with([
    'en pause' => ProjectStatus::Paused,
    'gelé par un deuil' => ProjectStatus::FrozenBereavement,
    'terminé' => ProjectStatus::Completed,
]);

it('enregistre l’échéance sur le projet quand on la lui applique', function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-09-09 09:05', 'Europe/Paris'));

    $project = Project::factory()->create(['next_prompt_at' => now()]);

    $next = app(ScheduleNextPrompt::class)->apply($project);

    expect($project->refresh()->next_prompt_at?->getTimestamp())->toBe($next?->getTimestamp());
});

/*
|--------------------------------------------------------------------------
| Le défaut de production T-242
|--------------------------------------------------------------------------
|
| Une narratrice à qui l'on avait promis « chaque lundi à 9 h » a reçu sa
| question un samedi, puis la suivante le lundi — deux jours plus tard.
|
| La cause tenait à l'ancienne formule, `addWeeks(1)->startOfWeek(MONDAY)` :
| ajouter une semaine puis **rembobiner au lundi de cette semaine-là** rend
| un écart de `7 − (jour de la référence − jour choisi)`. Référence samedi,
| jour choisi lundi : deux jours. L'écart minimal en jours, lui, ne se laisse
| pas rembobiner.
|
| Le samedi n'était pas un hasard : la première question part le lendemain de
| l'acceptation, quel que soit le jour, et c'est cette ancre décalée qui
| armait le défaut. Le second test tient la réparation de l'ancre.
|
*/

it('n’avance pas la question au lundi suivant quand la précédente est partie un samedi', function (): void {
    // Samedi 12 septembre 2026, 9 h : la question vient de partir.
    $this->travelTo(CarbonImmutable::parse('2026-09-12 09:00', 'Europe/Paris'));

    $project = Project::factory()->create([
        'prompt_day' => 1,           // lundi
        'prompt_slot' => PromptSlot::Morning,
        'cadence' => Cadence::Weekly,
        'next_prompt_at' => now(),
    ]);

    $next = schedule($project);

    // Le lundi 21, pas le lundi 14 : sept jours pleins, jamais deux.
    expect($next?->setTimezone('Europe/Paris')->toDateString())->toBe('2026-09-21')
        ->and($next?->greaterThanOrEqualTo(now()->addDays(7)))->toBeTrue();
});

it('ramène le rythme sur le jour choisi après une première question décalée', function (): void {
    // Jeudi 10 septembre, 10 h 40 : la narratrice accepte l'invitation.
    $this->travelTo(CarbonImmutable::parse('2026-09-10 10:40', 'Europe/Paris'));

    $project = Project::factory()->create([
        'prompt_day' => 1,           // lundi
        'prompt_slot' => PromptSlot::Morning,
        'cadence' => Cadence::Weekly,
        'next_prompt_at' => null,
    ]);

    $first = app(ScheduleNextPrompt::class)->apply($project);

    // Le lendemain : le dossier veut le premier enregistrement sous 72 h, et
    // cette promesse prime sur le jour choisi.
    expect($first?->setTimezone('Europe/Paris')->toDateString())->toBe('2026-09-11');

    // La question part, et le planificateur repasse derrière elle.
    $this->travelTo(CarbonImmutable::parse('2026-09-11 09:00', 'Europe/Paris'));
    $second = app(ScheduleNextPrompt::class)->apply($project);

    // Lundi, le jour promis : une ancre décalée se rattrape toute seule.
    expect($second?->setTimezone('Europe/Paris')->dayOfWeekIso)->toBe(1)
        ->and($second?->setTimezone('Europe/Paris')->toDateString())->toBe('2026-09-21');
});
