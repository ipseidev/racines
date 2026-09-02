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
