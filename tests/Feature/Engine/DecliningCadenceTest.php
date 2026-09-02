<?php

declare(strict_types=1);

use App\Engine\EngineTick;
use App\Engine\Rules\DecliningCadence;
use App\Enums\Cadence;
use App\Enums\EngineRuleId;
use App\Enums\ProjectStatus;
use App\Enums\TokenType;
use App\Models\AccessToken;
use App\Models\EngineEvent;
use App\Models\Narrator;
use App\Models\Project;
use App\Models\Story;
use App\Notifications\EngineNotification;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Notification;

/**
 * Un projet qui a enregistré `$previous` histoires sur la fenêtre d'avant et
 * `$recent` sur la fenêtre récente (quatre semaines chacune).
 */
function cadenceProject(int $previous, int $recent): Project
{
    $project = Project::factory()->create([
        'status' => ProjectStatus::Active,
        'cadence' => Cadence::Weekly,
    ]);

    Narrator::factory()->primary()->create([
        'project_id' => $project->id,
        'email' => 'odette@example.test',
    ]);

    $project->refresh();

    foreach (range(1, max($previous, 0)) as $index) {
        Story::factory()->forProject($project)->recorded()->create()
            ->forceFill(['recorded_at' => now()->subWeeks(5)->addDays($index)])->save();
    }

    foreach (range(1, max($recent, 0)) as $index) {
        Story::factory()->forProject($project)->recorded()->create()
            ->forceFill(['recorded_at' => now()->subWeeks(2)->addDays($index)])->save();
    }

    return $project->refresh();
}

/**
 * Le tick du lundi matin : la règle ne regarde le rythme qu'une fois par
 * semaine.
 */
function runCadenceRule(?CarbonImmutable $at = null): void
{
    $monday = ($at ?? CarbonImmutable::now())->next(CarbonImmutable::MONDAY)->setTime(7, 7);

    (new EngineTick([app(DecliningCadence::class)]))->run($monday);
}

beforeEach(function (): void {
    Notification::fake();
});

it('propose de ralentir quand le rythme est divisé par deux', function (): void {
    $project = cadenceProject(previous: 4, recent: 2);

    runCadenceRule();

    expect(EngineEvent::query()->sole()->rule_id)->toBe(EngineRuleId::DecliningCadence);
    Notification::assertSentTo($project->primaryNarrator, EngineNotification::class);
});

it('ne propose rien quand le rythme tient', function (): void {
    cadenceProject(previous: 4, recent: 3);

    runCadenceRule();

    expect(EngineEvent::query()->count())->toBe(0);
});

it('ne propose rien en dessous du minimum de deux', function (): void {
    // Une baisse de un à zéro n'est pas un ralentissement, c'est un silence,
    // et une autre règle s'en occupe avec d'autres mots.
    cadenceProject(previous: 1, recent: 0);

    runCadenceRule();

    expect(EngineEvent::query()->count())->toBe(0);
});

it('ne regarde le rythme que le lundi matin', function (): void {
    cadenceProject(previous: 4, recent: 2);

    $tuesday = CarbonImmutable::now()->next(CarbonImmutable::TUESDAY)->setTime(7, 7);
    (new EngineTick([app(DecliningCadence::class)]))->run($tuesday);

    expect(EngineEvent::query()->count())->toBe(0);
});

it('joint un lien pour passer à la quinzaine', function (): void {
    cadenceProject(previous: 4, recent: 2);

    runCadenceRule();

    $token = AccessToken::query()->where('type', TokenType::Action->value)->sole();

    expect($token->scope)->toBe(['action', 'switch_biweekly']);
});

it('ne propose qu’une fois toutes les huit semaines', function (): void {
    cadenceProject(previous: 4, recent: 2);

    runCadenceRule();
    expect(EngineEvent::query()->count())->toBe(1);

    // Deux fois de suite, ce serait insister sur un refus.
    $this->travel(4)->weeks();
    runCadenceRule();
    expect(EngineEvent::query()->count())->toBe(1);
});

it('ignore un projet déjà en quinzomadaire', function (): void {
    $project = cadenceProject(previous: 4, recent: 2);
    $project->forceFill(['cadence' => Cadence::Biweekly])->save();

    // Il n'y a plus rien à proposer : le narrateur a déjà ralenti.
    runCadenceRule();

    expect(EngineEvent::query()->count())->toBe(0);
});

it('se taît pendant une pause', function (): void {
    $project = cadenceProject(previous: 4, recent: 2);
    $project->forceFill(['paused_until' => now()->addWeeks(6)])->save();

    runCadenceRule();

    expect(EngineEvent::query()->count())->toBe(0);
});

it('mesure la rétention à huit semaines', function (): void {
    $project = cadenceProject(previous: 4, recent: 2);
    runCadenceRule();

    $event = EngineEvent::query()->sole();
    $rule = app(DecliningCadence::class);

    // Ce qu'on veut savoir, c'est si la famille est toujours là deux mois
    // plus tard — pas si elle a répondu dans la semaine.
    expect($rule->resumed($event, CarbonImmutable::now()->addWeeks(4)))->toBeNull();

    Story::factory()->forProject($project)->recorded()->create()
        ->forceFill(['recorded_at' => now()->addWeek()])->save();

    expect($rule->resumed($event, CarbonImmutable::now()->addWeeks(9)))->toBeTrue();
});
