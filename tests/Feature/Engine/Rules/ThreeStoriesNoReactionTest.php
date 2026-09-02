<?php

declare(strict_types=1);

use App\Engine\EngineTick;
use App\Engine\Rules\ThreeStoriesNoReaction;
use App\Enums\EngineRuleId;
use App\Enums\ProjectStatus;
use App\Enums\ReactionType;
use App\Enums\TokenType;
use App\Models\AccessToken;
use App\Models\EngineEvent;
use App\Models\FamilyMember;
use App\Models\Project;
use App\Models\Reaction;
use App\Models\Story;
use App\Notifications\EngineNotification;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Notification;

/**
 * Un projet avec `$count` histoires partagées, sans aucune réaction.
 */
function sharedWithoutReaction(int $count = 3): Project
{
    $project = Project::factory()->create(['status' => ProjectStatus::Active]);

    foreach (range(1, $count) as $index) {
        $story = Story::factory()->forProject($project)->shared()->create();
        $story->forceFill(['shared_at' => now()->subDays($count - $index)])->save();
    }

    return $project->refresh();
}

function runReactionRule(): void
{
    (new EngineTick([app(ThreeStoriesNoReaction::class)]))->run(CarbonImmutable::now());
}

beforeEach(function (): void {
    Notification::fake();
});

it('ne suggère rien avant trois histoires partagées', function (): void {
    sharedWithoutReaction(2);

    runReactionRule();

    expect(EngineEvent::query()->count())->toBe(0);
});

it('suggère un cœur à l’Initiateur·rice à la troisième', function (): void {
    $project = sharedWithoutReaction(3);

    runReactionRule();

    expect(EngineEvent::query()->sole()->rule_id)->toBe(EngineRuleId::ThreeStoriesNoReaction);
    Notification::assertSentTo($project->owner, EngineNotification::class);
});

it('joint un lien d’action en un tap', function (): void {
    sharedWithoutReaction(3);

    runReactionRule();

    $token = AccessToken::query()->where('type', TokenType::Action->value)->sole();

    expect($token->scope)->toBe(['action', 'react_heart'])
        ->and($token->single_use)->toBeTrue();
});

it('se taît dès qu’une seule réaction existe', function (): void {
    $project = sharedWithoutReaction(3);
    $member = FamilyMember::factory()->create(['project_id' => $project->id]);
    $story = $project->stories()->first();

    Reaction::factory()->create([
        'story_id' => $story?->id,
        'family_member_id' => $member->id,
        'type' => ReactionType::Heart,
    ]);

    // Une seule réaction suffit à rompre le silence : ce qu'on détecte, c'est
    // l'absence totale.
    runReactionRule();

    expect(EngineEvent::query()->count())->toBe(0);
});

it('ne suggère qu’une fois par mois', function (): void {
    sharedWithoutReaction(3);

    runReactionRule();
    $this->travel(20)->days();
    runReactionRule();

    expect(EngineEvent::query()->count())->toBe(1);

    $this->travel(11)->days();
    runReactionRule();

    expect(EngineEvent::query()->count())->toBe(2);
});

it('respecte le plafond de sollicitations de l’Initiateur·rice', function (): void {
    $project = sharedWithoutReaction(3);

    foreach (range(1, 4) as $index) {
        EngineEvent::factory()->create([
            'project_id' => $project->id,
            'rule_id' => EngineRuleId::NarratorSilence21d,
            'occurrence_key' => "saturation-{$index}",
            'dedupe_key' => "saturation:{$index}",
            'action_taken' => ['told' => 'initiator'],
        ]);
    }

    runReactionRule();

    // Une Initiateur·rice épuisée ne relance plus personne.
    expect(EngineEvent::query()->where('rule_id', EngineRuleId::ThreeStoriesNoReaction->value)->count())
        ->toBe(0);
});

it('se taît pour un projet en pause ou gelé', function (string $case): void {
    $project = sharedWithoutReaction(3);

    $case === 'pause'
        ? $project->forceFill(['paused_until' => now()->addWeek()])->save()
        : $project->forceFill(['status' => ProjectStatus::FrozenBereavement])->save();

    runReactionRule();

    expect(EngineEvent::query()->count())->toBe(0);
})->with(['pause', 'gel']);

it('n’occupe pas le quota quotidien du narrateur', function (): void {
    sharedWithoutReaction(3);

    runReactionRule();

    expect(EngineEvent::query()->sole()->action_taken['told'])->toBe('initiator');
});

it('mesure la reprise à la première réaction', function (): void {
    $project = sharedWithoutReaction(3);
    runReactionRule();

    $event = EngineEvent::query()->sole();
    $rule = app(ThreeStoriesNoReaction::class);

    expect($rule->resumed($event, CarbonImmutable::now()))->toBeNull();

    $member = FamilyMember::factory()->create(['project_id' => $project->id]);
    Reaction::factory()->create([
        'story_id' => $project->stories()->first()?->id,
        'family_member_id' => $member->id,
    ]);

    expect($rule->resumed($event, CarbonImmutable::now()))->toBeTrue();
});

it('conclut à l’absence d’effet après quatorze jours', function (): void {
    sharedWithoutReaction(3);
    runReactionRule();

    expect(app(ThreeStoriesNoReaction::class)
        ->resumed(EngineEvent::query()->sole(), CarbonImmutable::now()->addDays(15)))
        ->toBeFalse();
});
