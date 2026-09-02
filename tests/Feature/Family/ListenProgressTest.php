<?php

declare(strict_types=1);

use App\Enums\AnalyticsEvent;
use App\Enums\TokenType;
use App\Models\FamilyMember;
use App\Models\ListenEvent;
use App\Models\Story;
use App\Services\Tokens\TokenService;

/**
 * @return array{string, FamilyMember, Story}
 */
function listeningLink(): array
{
    $member = FamilyMember::factory()->create();
    $story = Story::factory()->shared()->create(['project_id' => $member->project_id]);
    $issued = app(TokenService::class)->issue(TokenType::ListenProject, $member, ['listen']);

    return [$issued->plain, $member, $story];
}

it('additionne les secondes écoutées', function (): void {
    [$token, $member, $story] = listeningLink();

    foreach ([10, 10, 5] as $seconds) {
        $this->postJson("/l/{$token}/stories/{$story->id}/listen", ['seconds' => $seconds])
            ->assertOk();
    }

    $event = ListenEvent::query()
        ->where('story_id', $story->id)
        ->where('family_member_id', $member->id)
        ->sole();

    expect($event->seconds_listened)->toBe(25)
        ->and($event->reached_30s)->toBeFalse();
});

it('pose le seuil de trente secondes une seule fois', function (): void {
    $analytics = fakeAnalytics();
    [$token, $member, $story] = listeningLink();

    foreach ([20, 20, 20] as $seconds) {
        $this->postJson("/l/{$token}/stories/{$story->id}/listen", ['seconds' => $seconds])->assertOk();
    }

    // Le franchissement du seuil est un fait daté : le recompter à chaque
    // envoi gonflerait la mesure d'un facteur dix.
    expect($analytics->captured(AnalyticsEvent::StoryListened30s))->toHaveCount(1);

    $event = ListenEvent::query()->sole();

    expect($event->reached_30s)->toBeTrue()
        ->and($event->seconds_listened)->toBe(60);
});

it('compte séparément chaque proche', function (): void {
    $analytics = fakeAnalytics();
    [$firstToken, , $story] = listeningLink();
    $second = FamilyMember::factory()->create(['project_id' => $story->project_id]);
    $secondToken = app(TokenService::class)->issue(TokenType::ListenProject, $second, ['listen'])->plain;

    $this->postJson("/l/{$firstToken}/stories/{$story->id}/listen", ['seconds' => 35])->assertOk();
    $this->postJson("/l/{$secondToken}/stories/{$story->id}/listen", ['seconds' => 35])->assertOk();

    expect(ListenEvent::query()->count())->toBe(2)
        ->and($analytics->captured(AnalyticsEvent::StoryListened30s))->toHaveCount(2);
});

it('rend l’état de l’écoute au lecteur', function (): void {
    [$token, , $story] = listeningLink();

    $this->postJson("/l/{$token}/stories/{$story->id}/listen", ['seconds' => 31])
        ->assertOk()
        ->assertJson(['seconds_listened' => 31, 'reached_30s' => true]);
});

it('refuse un envoi sur une histoire non visible', function (): void {
    $member = FamilyMember::factory()->create();
    $story = Story::factory()->toReview()->create(['project_id' => $member->project_id]);
    $token = app(TokenService::class)->issue(TokenType::ListenProject, $member, ['listen'])->plain;

    $this->postJson("/l/{$token}/stories/{$story->id}/listen", ['seconds' => 10])
        ->assertNotFound();

    expect(ListenEvent::query()->count())->toBe(0);
});

it('borne un incrément invraisemblable', function (): void {
    [$token, , $story] = listeningLink();

    // Le client rapporte toutes les dix secondes : un envoi de deux heures
    // est un client cassé ou malveillant, pas une écoute.
    $this->postJson("/l/{$token}/stories/{$story->id}/listen", ['seconds' => 7200])
        ->assertStatus(422);

    expect(ListenEvent::query()->count())->toBe(0);
});

it('n’émet aucune mesure quand le seuil n’est pas franchi', function (): void {
    $analytics = fakeAnalytics();
    [$token, , $story] = listeningLink();

    $this->postJson("/l/{$token}/stories/{$story->id}/listen", ['seconds' => 12])->assertOk();

    expect($analytics->captured(AnalyticsEvent::StoryListened30s))->toBe([]);
});
