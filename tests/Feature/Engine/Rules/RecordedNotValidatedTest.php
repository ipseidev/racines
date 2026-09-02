<?php

declare(strict_types=1);

use App\Actions\ValidateStoryAction;
use App\Engine\EngineTick;
use App\Engine\Rules\RecordedNotValidated;
use App\Enums\EngineRuleId;
use App\Enums\ProjectStatus;
use App\Enums\ShareDecision;
use App\Enums\ValidatedVia;
use App\Models\EngineEvent;
use App\Models\Narrator;
use App\Models\Project;
use App\Models\Story;
use App\Notifications\EngineNotification;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Notification;

/**
 * Une histoire dont le texte attend une décision depuis `$daysAgo` jours.
 *
 * @return array{Story, Narrator}
 */
function awaitingDecision(int $daysAgo, string $variant = 'to_review'): array
{
    $project = Project::factory()->create(['status' => ProjectStatus::Active]);
    $narrator = Narrator::factory()->primary()->create([
        'project_id' => $project->id,
        'email' => 'odette@example.test',
    ]);

    $story = $variant === 'to_review'
        ? Story::factory()->toReview()->create(['project_id' => $project->id, 'narrator_id' => $narrator->id])
        : Story::factory()->transcribed()->create([
            'project_id' => $project->id,
            'narrator_id' => $narrator->id,
            'share_decision' => ShareDecision::DecideLater,
            'share_decided_at' => now()->subDays($daysAgo),
        ]);

    $story->forceFill(['transcribed_at' => now()->subDays($daysAgo)])->save();

    return [$story->refresh(), $narrator];
}

function runValidationRule(): void
{
    (new EngineTick([app(RecordedNotValidated::class)]))->run(CarbonImmutable::now());
}

beforeEach(function (): void {
    Notification::fake();
});

it('ne rappelle rien avant le quatrième jour', function (): void {
    awaitingDecision(3);

    runValidationRule();

    expect(EngineEvent::query()->count())->toBe(0);
});

it('rappelle au quatrième jour, dans les deux variantes', function (string $variant): void {
    [, $narrator] = awaitingDecision(4, $variant);

    runValidationRule();

    expect(EngineEvent::query()->sole()->rule_id)->toBe(EngineRuleId::RecordedNotValidated);
    Notification::assertSentTo($narrator, EngineNotification::class);
})->with(['to_review', 'decide_later']);

it('s’arrête après deux rappels et attend en silence', function (): void {
    [$story] = awaitingDecision(4);

    runValidationRule();
    $this->travel(4)->days();
    runValidationRule();

    expect(EngineEvent::query()->count())->toBe(2);

    // Le narrateur a le droit de ne pas trancher. Une troisième relance
    // transformerait une hésitation en dette.
    $this->travel(4)->days();
    runValidationRule();

    expect(EngineEvent::query()->count())->toBe(2)
        ->and(EngineEvent::query()->orderByDesc('id')->first()?->action_taken['awaiting_quietly'])
        ->toBeTrue();

    // Et l'état n'a pas bougé : rien n'est validé par lassitude.
    expect($story->refresh()->validated_at)->toBeNull();
});

it('n’envoie qu’un message même si le tick tourne deux fois', function (): void {
    awaitingDecision(4);

    runValidationRule();
    runValidationRule();

    // Le second passage est consigné comme supprimé — savoir que la règle
    // aurait parlé fait partie de la mesure — mais un seul message part, et
    // le rappel différé garde sa place pour plus tard.
    $sent = EngineEvent::query()->get()->reject(fn ($e) => $e->wasSuppressed());

    expect($sent)->toHaveCount(1);
    Notification::assertCount(1);
});

it('se taît dès que l’histoire est validée', function (): void {
    [$story] = awaitingDecision(4);
    app(ValidateStoryAction::class)->handle($story, ValidatedVia::PostTranscription);

    runValidationRule();

    expect(EngineEvent::query()->count())->toBe(0);
});

it('se taît pendant une pause et pour un projet gelé', function (string $case): void {
    [$story] = awaitingDecision(4);

    $case === 'pause'
        ? $story->project->forceFill(['paused_until' => now()->addWeek()])->save()
        : $story->project->forceFill(['status' => ProjectStatus::FrozenBereavement])->save();

    runValidationRule();

    expect(EngineEvent::query()->count())->toBe(0);
})->with(['pause', 'gel']);

it('mène vers la relecture, pas vers un nouvel enregistrement', function (): void {
    awaitingDecision(4);

    runValidationRule();

    // Ce qu'on demande, c'est une décision sur un texte déjà écrit.
    expect(EngineEvent::query()->sole()->action_taken['actions'])
        ->toBe([__('notifications.engine.validation_reminder.button')]);
});

it('mesure la reprise à la validation', function (): void {
    [$story] = awaitingDecision(4);
    runValidationRule();

    $event = EngineEvent::query()->sole();
    $rule = app(RecordedNotValidated::class);

    expect($rule->resumed($event, CarbonImmutable::now()))->toBeNull();

    app(ValidateStoryAction::class)->handle($story->refresh(), ValidatedVia::PostTranscription);

    expect($rule->resumed($event->refresh(), CarbonImmutable::now()))->toBeTrue();
});

it('compte « garder pour moi » comme une décision prise', function (): void {
    [$story] = awaitingDecision(4);
    runValidationRule();

    $event = EngineEvent::query()->sole();
    $story->forceFill(['share_decision' => ShareDecision::KeepPrivate])->save();

    // Ce qu'on mesurait, c'est l'absence de décision — pas l'absence de
    // partage.
    expect(app(RecordedNotValidated::class)->resumed($event->refresh(), CarbonImmutable::now()))
        ->toBeTrue();
});

it('conclut à l’absence d’effet après quatorze jours', function (): void {
    awaitingDecision(4);
    runValidationRule();

    $event = EngineEvent::query()->sole();

    expect(app(RecordedNotValidated::class)->resumed($event, CarbonImmutable::now()->addDays(15)))
        ->toBeFalse();
});
