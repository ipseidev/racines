<?php

declare(strict_types=1);

use App\Enums\AnalyticsEvent;
use App\Enums\EngineOutcome;
use App\Enums\EngineRuleId;
use App\Enums\ProjectStatus;
use App\Enums\TokenType;
use App\Jobs\MeasureResumptions;
use App\Models\AccessToken;
use App\Models\EngineEvent;
use App\Models\Project;
use App\Models\Story;
use App\Services\Tokens\TokenService;

/**
 * Un déclenchement de « lien non ouvert », dont la reprise se mesure à
 * l'ouverture du lien.
 *
 * @return array{EngineEvent, Story}
 */
function firedLinkEvent(int $daysAgo = 1): array
{
    $project = Project::factory()->create(['status' => ProjectStatus::Active]);
    $story = Story::factory()->forProject($project)->proposed()->create();
    app(TokenService::class)->issue(TokenType::Record, $story, ['record']);

    $event = EngineEvent::factory()->create([
        'project_id' => $project->id,
        'story_id' => $story->id,
        'rule_id' => EngineRuleId::LinkNotOpened,
        'occurrence_key' => $story->id.':-:1',
        'dedupe_key' => 'link_not_opened:'.$story->id.':-:1',
        'fired_at' => now()->subDays($daysAgo),
    ]);

    return [$event, $story];
}

function measure(): void
{
    app()->call([new MeasureResumptions, 'handle']);
}

it('laisse sans verdict tant que la reprise est possible', function (): void {
    [$event] = firedLinkEvent(1);

    measure();

    // « Pas encore » n'est pas « non » : le job repassera dans une heure.
    expect($event->refresh()->outcome)->toBeNull();
});

it('conclut à la reprise quand le lien est ouvert', function (): void {
    $analytics = fakeAnalytics();
    [$event, $story] = firedLinkEvent(1);

    AccessToken::query()->where('subject_id', $story->id)->update(['use_count' => 1]);

    measure();

    $event->refresh();

    expect($event->outcome)->toBe(EngineOutcome::Resumed)
        ->and($event->outcome_at)->not->toBeNull();

    $captured = $analytics->captured(AnalyticsEvent::EngineRuleResumed);

    expect($captured)->toHaveCount(1)
        ->and($captured[0]['properties']['rule_id'])->toBe('link_not_opened')
        ->and($captured[0]['properties']['delay_hours'])->toBeGreaterThan(0);
});

it('conclut à l’absence d’effet passé le délai de la règle', function (): void {
    [$event] = firedLinkEvent(8);

    measure();

    // Un résultat négatif est aussi précieux que l'autre : c'est lui qui dit
    // qu'une règle ne sert à rien.
    expect($event->refresh()->outcome)->toBe(EngineOutcome::NoEffect);
});

it('tranche au bout de trente jours, même sans verdict de la règle', function (): void {
    [$event] = firedLinkEvent(31);

    measure();

    // Un « peut-être » qui traîne un an ne mesure rien.
    expect($event->refresh()->outcome)->toBe(EngineOutcome::NoEffect);
});

it('ne mesure pas un événement supprimé', function (): void {
    $project = Project::factory()->create(['status' => ProjectStatus::Active]);
    $event = EngineEvent::factory()->suppressed()->create(['project_id' => $project->id]);

    measure();

    // Un message qui n'est pas parti n'a rien pu produire.
    expect($event->refresh()->outcome)->toBeNull();
});

it('ne repasse pas sur un verdict déjà rendu', function (): void {
    $analytics = fakeAnalytics();
    [$event, $story] = firedLinkEvent(1);
    AccessToken::query()->where('subject_id', $story->id)->update(['use_count' => 1]);

    measure();
    $first = $event->refresh()->outcome_at;

    measure();

    expect($event->refresh()->outcome_at?->toIso8601String())->toBe($first?->toIso8601String())
        ->and($analytics->captured(AnalyticsEvent::EngineRuleResumed))->toHaveCount(1);
});

it('rapporte les déclenchements et les reprises', function (): void {
    [$event, $story] = firedLinkEvent(1);
    AccessToken::query()->where('subject_id', $story->id)->update(['use_count' => 1]);
    measure();

    $this->artisan('engine:report')
        ->expectsOutputToContain('link_not_opened')
        ->assertSuccessful();
});

it('ne rapporte rien quand rien ne s’est déclenché', function (): void {
    $this->artisan('engine:report')
        ->expectsOutputToContain('Aucun déclenchement')
        ->assertSuccessful();
});
