<?php

declare(strict_types=1);

use App\Engine\EngineTick;
use App\Engine\Rules\MicDenied;
use App\Enums\AnswerType;
use App\Enums\ClientEventName;
use App\Enums\EngineRuleId;
use App\Enums\ProjectStatus;
use App\Enums\SupportTicketKind;
use App\Enums\SupportTicketStatus;
use App\Models\ClientEvent;
use App\Models\EngineEvent;
use App\Models\Project;
use App\Models\Story;
use App\Models\SupportTicket;
use App\States\Story\Recorded;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Notification;

function denyMic(Story $story, int $times = 1): void
{
    foreach (range(1, $times) as $index) {
        $event = new ClientEvent(['event' => ClientEventName::MicDenied, 'payload' => []]);
        $event->story()->associate($story);
        $event->save();
    }
}

function proposedStory(): Story
{
    $project = Project::factory()->create(['status' => ProjectStatus::Active]);

    return Story::factory()->proposed()->create(['project_id' => $project->id]);
}

function runMicRule(): void
{
    (new EngineTick([app(MicDenied::class)]))->run(CarbonImmutable::now());
}

beforeEach(function (): void {
    Notification::fake();
});

it('ne fait rien tant qu’aucun refus n’est rapporté', function (): void {
    proposedStory();

    runMicRule();

    expect(EngineEvent::query()->count())->toBe(0);
});

it('consigne le premier refus sans écrire à personne', function (): void {
    $story = proposedStory();
    denyMic($story);

    runMicRule();

    $event = EngineEvent::query()->sole();

    // La page d'aide est déjà affichée côté front. Redemander le micro tout
    // de suite ferait fuir la personne.
    expect($event->rule_id)->toBe(EngineRuleId::MicDenied)
        ->and($event->action_taken['help_shown'])->toBeTrue()
        ->and(SupportTicket::query()->count())->toBe(0);

    Notification::assertNothingSent();
});

it('ouvre un ticket au second refus', function (): void {
    $story = proposedStory();
    denyMic($story, 2);

    runMicRule();

    $ticket = SupportTicket::query()->sole();

    // Une personne de 82 ans qui n'y arrive pas n'écrit pas au support :
    // elle abandonne, et personne ne sait pourquoi.
    expect($ticket->kind)->toBe(SupportTicketKind::MicDeniedTwice)
        ->and($ticket->status)->toBe(SupportTicketStatus::Open)
        ->and($ticket->story_id)->toBe($story->id)
        ->and($ticket->payload['denials'])->toBe(2);
});

it('n’ouvre qu’un seul ticket, même après un troisième refus', function (): void {
    $story = proposedStory();
    denyMic($story, 2);
    runMicRule();

    denyMic($story);
    runMicRule();

    // Un support noyé sous les doublons ne traite plus rien.
    expect(SupportTicket::query()->count())->toBe(1);
});

it('ne consigne pas deux fois la même occurrence', function (): void {
    $story = proposedStory();
    denyMic($story);

    runMicRule();
    runMicRule();

    expect(EngineEvent::query()->count())->toBe(1);
});

it('ignore une histoire qui n’est plus à l’état proposé', function (): void {
    $story = proposedStory();
    denyMic($story, 2);
    $story->state->transitionTo(Recorded::class, AnswerType::Text);

    runMicRule();

    // Le narrateur a fini par répondre : le problème s'est réglé tout seul.
    expect(EngineEvent::query()->count())->toBe(0)
        ->and(SupportTicket::query()->count())->toBe(0);
});

it('se taît pour un projet gelé', function (): void {
    $story = proposedStory();
    $story->project->forceFill(['status' => ProjectStatus::FrozenBereavement])->save();
    denyMic($story, 2);

    runMicRule();

    expect(SupportTicket::query()->count())->toBe(0);
});

it('n’occupe pas le quota quotidien du narrateur', function (): void {
    $story = proposedStory();
    denyMic($story);

    runMicRule();

    // C'est le support qu'on alerte, pas le narrateur : ce message-là ne doit
    // pas empêcher une vraie relance le même jour.
    expect(EngineEvent::query()->sole()->action_taken['told'])->toBe('support');
});

it('mesure la reprise quand le micro finit par être autorisé', function (): void {
    $story = proposedStory();
    denyMic($story, 2);
    runMicRule();

    $event = EngineEvent::query()->sole();
    $rule = app(MicDenied::class);

    expect($rule->resumed($event, CarbonImmutable::now()))->toBeNull();

    $granted = new ClientEvent(['event' => ClientEventName::MicGranted, 'payload' => []]);
    $granted->story()->associate($story);
    $granted->save();

    expect($rule->resumed($event, CarbonImmutable::now()))->toBeTrue();
});

it('conclut à l’absence d’effet après sept jours', function (): void {
    $story = proposedStory();
    denyMic($story);
    runMicRule();

    $event = EngineEvent::query()->sole();

    expect(app(MicDenied::class)->resumed($event, CarbonImmutable::now()->addDays(8)))->toBeFalse();
});
