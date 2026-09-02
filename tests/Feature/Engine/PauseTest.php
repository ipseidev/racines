<?php

declare(strict_types=1);

use App\Actions\RequestPause;
use App\Actions\ResumeFromPause;
use App\Engine\EngineTick;
use App\Engine\Rules\NarratorSilence10d;
use App\Engine\Rules\PauseRequested;
use App\Enums\EngineRuleId;
use App\Enums\ProjectStatus;
use App\Models\EngineEvent;
use App\Models\Narrator;
use App\Models\Project;
use App\Notifications\EngineNotification;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Notification;

function pausableProject(): Project
{
    $project = Project::factory()->create(['status' => ProjectStatus::Active]);
    $project->forceFill(['next_prompt_at' => now()->addDay()])->save();

    Narrator::factory()->primary()->create([
        'project_id' => $project->id,
        'first_name' => 'Odette',
        'email' => 'odette@example.test',
    ]);

    return $project->refresh();
}

function runPauseRule(): void
{
    (new EngineTick([app(PauseRequested::class)]))->run(CarbonImmutable::now());
}

beforeEach(function (): void {
    Notification::fake();
});

it('pose la date de reprise et annule l’envoi programmé', function (): void {
    $project = pausableProject();

    app(RequestPause::class)->handle($project, now()->addWeeks(4));

    $project->refresh();

    // Aucun envoi ne doit rester en attente : une pause qui laisse partir la
    // question de mardi n'est pas une pause.
    expect($project->paused_until)->not->toBeNull()
        ->and($project->next_prompt_at)->toBeNull()
        ->and($project->status)->toBe(ProjectStatus::Paused);
});

it('confirme la pause au narrateur, avec sa date de fin', function (): void {
    $project = pausableProject();
    app(RequestPause::class)->handle($project, now()->addWeeks(4));

    runPauseRule();

    $event = EngineEvent::query()->sole();

    expect($event->rule_id)->toBe(EngineRuleId::PauseRequested)
        ->and($event->action_taken)->toHaveKey('paused_until');

    // Une pause sans terme annoncé inquiète autant qu'un silence : la
    // personne se demande si le projet est mort.
    Notification::assertSentTo(
        $project->refresh()->primaryNarrator,
        EngineNotification::class,
        fn (EngineNotification $n): bool => str_contains(
            (string) $n->toMail($project->primaryNarrator)->subject,
            $project->paused_until?->locale('fr')->isoFormat('D MMMM YYYY') ?? '',
        ),
    );
});

it('ne confirme qu’une fois la même pause', function (): void {
    $project = pausableProject();
    app(RequestPause::class)->handle($project, now()->addWeeks(4));

    runPauseRule();
    runPauseRule();

    expect(EngineEvent::query()->count())->toBe(1);
    Notification::assertCount(1);
});

it('confirme de nouveau une pause prolongée', function (): void {
    $project = pausableProject();
    app(RequestPause::class)->handle($project, now()->addWeeks(4));
    runPauseRule();

    // Prolonger est une nouvelle décision, et mérite sa confirmation.
    app(RequestPause::class)->handle($project->refresh(), now()->addWeeks(8));
    runPauseRule();

    expect(EngineEvent::query()->count())->toBe(2);
});

it('reste silencieux pendant toute la pause', function (): void {
    $project = pausableProject();
    app(RequestPause::class)->handle($project, now()->addWeeks(4));
    runPauseRule();

    $this->travel(2)->weeks();

    // Seule la confirmation passe : c'est la seule règle qui parle de la
    // pause elle-même.
    (new EngineTick([app(NarratorSilence10d::class)]))->run(CarbonImmutable::now());

    expect(EngineEvent::query()->where('rule_id', EngineRuleId::NarratorSilence10d->value)->count())
        ->toBe(0);
});

it('reprend à l’échéance, et replanifie', function (): void {
    $project = pausableProject();
    app(RequestPause::class)->handle($project, now()->addWeeks(4));

    $this->travel(4)->weeks();
    $this->travel(1)->hour();

    $resumed = app(ResumeFromPause::class)->handle();

    $project->refresh();

    expect($resumed)->toBe(1)
        ->and($project->paused_until)->toBeNull()
        ->and($project->status)->toBe(ProjectStatus::Active)
        ->and($project->next_prompt_at)->not->toBeNull();

    Notification::assertSentTo($project->primaryNarrator, EngineNotification::class);
});

it('ne reprend pas avant l’échéance', function (): void {
    $project = pausableProject();
    app(RequestPause::class)->handle($project, now()->addWeeks(4));

    $this->travel(3)->weeks();

    expect(app(ResumeFromPause::class)->handle())->toBe(0)
        ->and($project->refresh()->paused_until)->not->toBeNull();
});

it('ne réveille pas un projet résilié', function (): void {
    $project = pausableProject();
    app(RequestPause::class)->handle($project, now()->addWeeks(4));
    $project->forceFill(['status' => ProjectStatus::Cancelled])->save();

    $this->travel(5)->weeks();

    expect(app(ResumeFromPause::class)->handle())->toBe(0);
});

it('mesure la reprise à la fin de la pause', function (): void {
    $project = pausableProject();
    app(RequestPause::class)->handle($project, now()->addWeeks(4));
    runPauseRule();

    $event = EngineEvent::query()->sole();
    $rule = app(PauseRequested::class);

    expect($rule->resumed($event, CarbonImmutable::now()))->toBeNull();

    $this->travel(4)->weeks();
    $this->travel(1)->hour();
    app(ResumeFromPause::class)->handle();

    expect($rule->resumed($event->refresh(), CarbonImmutable::now()))->toBeTrue();
});
