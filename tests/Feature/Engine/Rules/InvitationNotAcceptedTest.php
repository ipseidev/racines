<?php

declare(strict_types=1);

use App\Engine\EngineTick;
use App\Engine\Rules\InvitationNotAccepted;
use App\Enums\EngineRuleId;
use App\Enums\ProjectStatus;
use App\Models\EngineEvent;
use App\Models\Narrator;
use App\Models\Project;
use App\Notifications\EngineNotification;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Notification;

/**
 * Un cadeau envoyé il y a `$daysAgo` jours, jamais accepté.
 */
function unopenedGift(int $daysAgo): Project
{
    $project = Project::factory()->create(['status' => ProjectStatus::AwaitingAcceptance]);
    $project->forceFill(['gift_sent_at' => now()->subDays($daysAgo)])->save();

    Narrator::factory()->primary()->create([
        'project_id' => $project->id,
        'first_name' => 'Odette',
        'email' => 'odette@example.test',
    ]);

    return $project->refresh();
}

function runInvitationRule(): void
{
    (new EngineTick([new InvitationNotAccepted]))->run(CarbonImmutable::now());
}

beforeEach(function (): void {
    Notification::fake();
});

it('ne dit rien avant le septième jour', function (): void {
    unopenedGift(6);

    runInvitationRule();

    expect(EngineEvent::query()->count())->toBe(0);
    Notification::assertNothingSent();
});

it('relance doucement le narrateur au septième jour', function (): void {
    $project = unopenedGift(7);

    runInvitationRule();

    $event = EngineEvent::query()->sole();

    expect($event->rule_id)->toBe(EngineRuleId::InvitationNotAccepted)
        ->and($event->attempt())->toBe(1)
        ->and($event->action_taken['told'])->toBe('narrator');

    Notification::assertSentTo(
        $project->primaryNarrator,
        EngineNotification::class,
        fn (EngineNotification $n): bool => str_contains($n->deliveryPayload()['rule_id'], 'invitation_not_accepted'),
    );
});

it('en parle à l’Initiateur·rice au quatorzième jour', function (): void {
    $project = unopenedGift(14);

    runInvitationRule();

    $event = EngineEvent::query()->sole();

    // Un message de l'Initiateur·rice vaut dix des nôtres : sa voix se
    // reconnaît, un numéro inconnu non.
    expect($event->attempt())->toBe(2)
        ->and($event->action_taken['told'])->toBe('initiator');

    Notification::assertSentTo($project->owner, EngineNotification::class);
    Notification::assertNotSentTo($project->primaryNarrator, EngineNotification::class);
});

it('ne relance pas deux fois la même occurrence', function (): void {
    unopenedGift(7);

    runInvitationRule();
    runInvitationRule();

    expect(EngineEvent::query()->count())->toBe(1);
    Notification::assertCount(1);
});

it('s’arrête après deux relances', function (): void {
    $project = unopenedGift(7);

    runInvitationRule();

    $this->travel(7)->days();
    runInvitationRule();

    expect(EngineEvent::query()->count())->toBe(2);

    // Au-delà, ce n'est plus une invitation, c'est une insistance.
    $this->travel(7)->days();
    $project->forceFill(['gift_sent_at' => now()->subDays(21)])->save();
    runInvitationRule();

    expect(EngineEvent::query()->count())->toBe(2);
});

it('marque le contact du narrateur pour suppression après la seconde relance', function (): void {
    $project = unopenedGift(14);

    runInvitationRule();

    // On ne garde pas indéfiniment le téléphone de quelqu'un qui n'a jamais
    // dit oui.
    $narrator = $project->primaryNarrator()->first();

    expect($narrator?->contact_deletion_due_at)->not->toBeNull()
        ->and($narrator?->contact_deletion_due_at?->toDateString())
        ->toBe(now()->addDays(30)->toDateString());
});

it('se taît pour un projet gelé ou en pause', function (string $status): void {
    $project = unopenedGift(14);
    $project->forceFill(['status' => ProjectStatus::from($status)])->save();

    runInvitationRule();

    expect(EngineEvent::query()->count())->toBe(0);
    Notification::assertNothingSent();
})->with(['paused', 'frozen_bereavement', 'cancelled']);

it('se taît dès que l’invitation est acceptée ou refusée', function (string $column): void {
    $project = unopenedGift(14);
    $project->forceFill([$column => now()])->save();

    runInvitationRule();

    expect(EngineEvent::query()->count())->toBe(0);
})->with(['accepted_at', 'refused_at']);

it('respecte le plafond de sollicitations de l’Initiateur·rice', function (): void {
    $project = unopenedGift(14);

    // Quatre sollicitations déjà envoyées ce mois-ci : la cinquième ne part
    // pas, même pour une invitation restée sans réponse (R-7).
    foreach (range(1, 4) as $index) {
        $event = new EngineEvent([
            'rule_id' => EngineRuleId::ThreeStoriesNoReaction,
            'occurrence_key' => "saturation-{$index}",
            'dedupe_key' => "saturation:{$index}",
            'fired_at' => now(),
            'action_taken' => ['told' => 'initiator'],
        ]);
        $event->project()->associate($project);
        $event->save();
    }

    runInvitationRule();

    expect(EngineEvent::query()->where('rule_id', EngineRuleId::InvitationNotAccepted->value)->count())->toBe(0);
    Notification::assertNothingSent();
});

it('mesure la reprise quand l’invitation est acceptée', function (): void {
    $project = unopenedGift(7);
    runInvitationRule();

    $event = EngineEvent::query()->sole();
    $rule = new InvitationNotAccepted;

    expect($rule->resumed($event, CarbonImmutable::now()))->toBeNull();

    $project->forceFill(['accepted_at' => now()])->save();

    expect($rule->resumed($event->refresh(), CarbonImmutable::now()))->toBeTrue();
});

it('conclut à l’absence d’effet après trente jours', function (): void {
    unopenedGift(7);
    runInvitationRule();

    $event = EngineEvent::query()->sole();

    expect((new InvitationNotAccepted)->resumed($event, CarbonImmutable::now()->addDays(31)))->toBeFalse();
});
