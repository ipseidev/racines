<?php

declare(strict_types=1);

use App\Actions\SetStoryVisibility;
use App\Engine\EngineTick;
use App\Engine\Rules\ValidatedNotListened;
use App\Enums\ProjectStatus;
use App\Enums\StoryVisibility;
use App\Enums\TokenType;
use App\Models\AccessToken;
use App\Models\EngineEvent;
use App\Models\FamilyMember;
use App\Models\ListenEvent;
use App\Models\Project;
use App\Models\Story;
use App\Notifications\EngineNotification;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Notification;

/**
 * Une histoire partagée il y a `$daysAgo` jours, avec `$members` proches.
 *
 * @return array{Story, list<FamilyMember>}
 */
function unheardStory(int $daysAgo, int $members = 2): array
{
    $project = Project::factory()->create(['status' => ProjectStatus::Active]);
    $story = Story::factory()->forProject($project)->shared()->create(['title' => 'Les crêpes']);
    $story->forceFill(['shared_at' => now()->subDays($daysAgo)])->save();

    $family = FamilyMember::factory()->count($members)->create(['project_id' => $project->id])->all();

    return [$story->refresh(), $family];
}

function runListenRule(): void
{
    (new EngineTick([app(ValidatedNotListened::class)]))->run(CarbonImmutable::now());
}

beforeEach(function (): void {
    Notification::fake();
});

it('ne pousse rien avant le cinquième jour', function (): void {
    unheardStory(4);

    runListenRule();

    expect(EngineEvent::query()->count())->toBe(0);
    Notification::assertNothingSent();
});

it('pousse chaque proche au cinquième jour', function (): void {
    [, $family] = unheardStory(5);

    runListenRule();

    expect(EngineEvent::query()->sole()->action_taken['nudged'])->toBe(2);

    foreach ($family as $member) {
        Notification::assertSentTo($member, EngineNotification::class);
    }
});

it('donne à chaque proche son propre lien vers l’histoire', function (): void {
    [$story] = unheardStory(5);

    runListenRule();

    $tokens = AccessToken::query()->where('type', TokenType::ListenStory->value)->get();

    // Un lien par personne, vers l'histoire et non vers la liste : on demande
    // deux minutes d'écoute, pas une visite.
    expect($tokens)->toHaveCount(2)
        ->and($tokens->pluck('issued_to_id')->unique())->toHaveCount(2)
        ->and($tokens->pluck('subject_id')->unique()->all())->toBe([$story->id]);
});

it('ne pousse qu’une fois par histoire', function (): void {
    unheardStory(5);

    runListenRule();
    $this->travel(5)->days();
    runListenRule();

    // Insister ne changerait rien, et ce n'est pas au narrateur de payer le
    // silence de sa famille par des rappels supplémentaires.
    expect(EngineEvent::query()->count())->toBe(1);
    Notification::assertCount(2);
});

it('se taît dès qu’un proche a écouté trente secondes', function (): void {
    [$story, $family] = unheardStory(5);
    ListenEvent::factory()->listened()->create([
        'story_id' => $story->id,
        'family_member_id' => $family[0]->id,
    ]);

    runListenRule();

    expect(EngineEvent::query()->count())->toBe(0);
});

it('ne compte pas une page ouverte comme une écoute', function (): void {
    [$story, $family] = unheardStory(5);
    ListenEvent::factory()->create([
        'story_id' => $story->id,
        'family_member_id' => $family[0]->id,
        'seconds_listened' => 8,
        'reached_30s' => false,
    ]);

    runListenRule();

    // Le seuil du dossier est trente secondes d'écoute, pas un clic.
    expect(EngineEvent::query()->count())->toBe(1);
});

it('n’écrit qu’aux proches autorisés à écouter', function (): void {
    [$story, $family] = unheardStory(5);
    app(SetStoryVisibility::class)
        ->handle($story, StoryVisibility::Restricted, [$family[0]->id]);

    runListenRule();

    expect(EngineEvent::query()->sole()->action_taken['nudged'])->toBe(1);

    Notification::assertSentTo($family[0], EngineNotification::class);
    Notification::assertNotSentTo($family[1], EngineNotification::class);
});

it('se taît pendant une pause du projet', function (): void {
    [$story] = unheardStory(5);
    $story->project->forceFill(['paused_until' => now()->addWeek()])->save();

    runListenRule();

    expect(EngineEvent::query()->count())->toBe(0);
});

it('n’occupe pas le quota quotidien du narrateur', function (): void {
    unheardStory(5);

    runListenRule();

    // Ce sont les proches qu'on sollicite : cela ne doit pas empêcher une
    // vraie relance du narrateur le même jour.
    expect(EngineEvent::query()->sole()->action_taken['told'])->toBe('family');
});

it('mesure la reprise à la première écoute', function (): void {
    [$story, $family] = unheardStory(5);
    runListenRule();

    $event = EngineEvent::query()->sole();
    $rule = app(ValidatedNotListened::class);

    expect($rule->resumed($event, CarbonImmutable::now()))->toBeNull();

    ListenEvent::factory()->listened()->create([
        'story_id' => $story->id,
        'family_member_id' => $family[1]->id,
    ]);

    expect($rule->resumed($event, CarbonImmutable::now()))->toBeTrue();
});

it('conclut à l’absence d’effet après sept jours', function (): void {
    unheardStory(5);
    runListenRule();

    expect(app(ValidatedNotListened::class)
        ->resumed(EngineEvent::query()->sole(), CarbonImmutable::now()->addDays(8)))
        ->toBeFalse();
});
