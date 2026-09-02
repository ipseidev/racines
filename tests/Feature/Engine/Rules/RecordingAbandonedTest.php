<?php

declare(strict_types=1);

use App\Engine\EngineTick;
use App\Engine\Rules\RecordingAbandoned;
use App\Enums\ClientEventName;
use App\Enums\EngineRuleId;
use App\Enums\ProjectStatus;
use App\Models\AccessToken;
use App\Models\ClientEvent;
use App\Models\EngineEvent;
use App\Models\Narrator;
use App\Models\Project;
use App\Models\Recording;
use App\Models\Story;
use App\Notifications\EngineNotification;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Notification;

/**
 * Une histoire commencée il y a `$daysAgo` jours et jamais envoyée.
 *
 * @return array{Story, Narrator}
 */
function abandonedDraft(int $daysAgo): array
{
    $project = Project::factory()->create(['status' => ProjectStatus::Active]);
    $narrator = Narrator::factory()->primary()->create([
        'project_id' => $project->id,
        'email' => 'odette@example.test',
    ]);
    $story = Story::factory()->proposed()->create([
        'project_id' => $project->id,
        'narrator_id' => $narrator->id,
    ]);

    $event = new ClientEvent(['event' => ClientEventName::RecordingStarted, 'payload' => []]);
    $event->story()->associate($story);
    $event->save();
    $event->forceFill(['created_at' => now()->subDays($daysAgo)])->save();

    return [$story->refresh(), $narrator];
}

function runAbandonedRule(): void
{
    (new EngineTick([app(RecordingAbandoned::class)]))->run(CarbonImmutable::now());
}

beforeEach(function (): void {
    Notification::fake();
});

it('ne rappelle rien avant le deuxième jour', function (): void {
    abandonedDraft(1);

    runAbandonedRule();

    expect(EngineEvent::query()->count())->toBe(0);
    Notification::assertNothingSent();
});

it('rappelle au deuxième jour, sans rien reprocher', function (): void {
    [, $narrator] = abandonedDraft(2);

    runAbandonedRule();

    expect(EngineEvent::query()->sole()->rule_id)->toBe(EngineRuleId::RecordingAbandoned);

    Notification::assertSentTo($narrator, EngineNotification::class);

    // « Votre histoire vous attend », pas « vous n'avez pas terminé » : la
    // personne a peut-être été interrompue, ou s'est arrêtée parce que le
    // souvenir était difficile.
    expect(__('notifications.engine.draft_waiting.line'))
        ->toContain('toujours là')
        ->and(__('notifications.engine.draft_waiting.subject'))
        ->toBe('Votre histoire vous attend');
});

it('ne rappelle qu’une fois', function (): void {
    abandonedDraft(2);

    runAbandonedRule();
    $this->travel(3)->days();
    runAbandonedRule();

    // Si le brouillon reste, c'est un choix, et il se respecte.
    expect(EngineEvent::query()->count())->toBe(1);
    Notification::assertCount(1);
});

it('se taît quand l’enregistrement a été envoyé', function (): void {
    [$story] = abandonedDraft(2);
    Recording::factory()->confirmed()->create(['story_id' => $story->id]);

    runAbandonedRule();

    expect(EngineEvent::query()->count())->toBe(0);
});

it('se taît pendant une pause', function (): void {
    [$story] = abandonedDraft(2);
    $story->project->forceFill(['paused_until' => now()->addWeek()])->save();

    runAbandonedRule();

    expect(EngineEvent::query()->count())->toBe(0);
});

it('se taît pour un projet résilié', function (): void {
    [$story] = abandonedDraft(2);
    $story->project->forceFill(['status' => ProjectStatus::Cancelled])->save();

    runAbandonedRule();

    expect(EngineEvent::query()->count())->toBe(0);
});

it('envoie un lien neuf vers la même histoire', function (): void {
    [$story] = abandonedDraft(2);
    $before = AccessToken::query()->where('subject_id', $story->id)->count();

    runAbandonedRule();

    // Le brouillon est rangé par histoire, pas par jeton : un lien neuf
    // retrouve le même enregistrement en cours.
    expect(AccessToken::query()->where('subject_id', $story->id)->count())
        ->toBe($before + 1);
});

it('mesure la reprise quand l’enregistrement finit par arriver', function (): void {
    [$story] = abandonedDraft(2);
    runAbandonedRule();

    $event = EngineEvent::query()->sole();
    $rule = app(RecordingAbandoned::class);

    expect($rule->resumed($event, CarbonImmutable::now()))->toBeNull();

    Recording::factory()->confirmed()->create(['story_id' => $story->id]);

    expect($rule->resumed($event, CarbonImmutable::now()))->toBeTrue();
});

it('conclut à l’absence d’effet après sept jours', function (): void {
    abandonedDraft(2);
    runAbandonedRule();

    $event = EngineEvent::query()->sole();

    expect(app(RecordingAbandoned::class)->resumed($event, CarbonImmutable::now()->addDays(8)))
        ->toBeFalse();
});
