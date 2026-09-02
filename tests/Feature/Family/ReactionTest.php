<?php

declare(strict_types=1);

use App\Actions\HideStoryAction;
use App\Enums\AnalyticsEvent;
use App\Enums\TokenType;
use App\Jobs\NotifyNarratorOfReactions;
use App\Models\FamilyMember;
use App\Models\Reaction;
use App\Models\Story;
use App\Services\Tokens\TokenService;
use Illuminate\Support\Facades\Queue;

/**
 * @return array{string, FamilyMember, Story}
 */
function reactingLink(): array
{
    $member = FamilyMember::factory()->create(['display_name' => 'Marie']);
    $story = Story::factory()->shared()->create(['project_id' => $member->project_id]);
    $issued = app(TokenService::class)->issue(TokenType::ListenProject, $member, ['listen', 'react']);

    return [$issued->plain, $member, $story];
}

beforeEach(function (): void {
    Queue::fake();
});

it('enregistre un cœur, et deux fois reste une fois', function (): void {
    [$token, $member, $story] = reactingLink();

    $this->post("/l/{$token}/stories/{$story->id}/reactions", ['type' => 'heart'])->assertRedirect();
    $this->post("/l/{$token}/stories/{$story->id}/reactions", ['type' => 'heart'])->assertRedirect();

    // Le narrateur n'a pas à distinguer un enthousiasme d'un double-clic.
    expect(Reaction::query()->where('family_member_id', $member->id)->count())->toBe(1);
});

it('accepte un cœur et un merci du même proche', function (): void {
    [$token, $member, $story] = reactingLink();

    $this->post("/l/{$token}/stories/{$story->id}/reactions", ['type' => 'heart'])->assertRedirect();
    $this->post("/l/{$token}/stories/{$story->id}/reactions", ['type' => 'thanks'])->assertRedirect();

    expect(Reaction::query()->where('family_member_id', $member->id)->count())->toBe(2);
});

it('remplace le mot précédent au lieu d’en laisser deux', function (): void {
    [$token, , $story] = reactingLink();

    $this->post("/l/{$token}/stories/{$story->id}/reactions", [
        'type' => 'thanks',
        'comment' => 'Premier jet',
    ])->assertRedirect();

    $this->post("/l/{$token}/stories/{$story->id}/reactions", [
        'type' => 'thanks',
        'comment' => 'Merci maman, c’était beau.',
    ])->assertRedirect();

    // Quelqu'un qui se relit et corrige son message ne doit pas en laisser
    // deux au narrateur.
    $reaction = Reaction::query()->sole();

    expect($reaction->comment)->toBe('Merci maman, c’était beau.');
});

it('refuse un mot de plus de 280 caractères', function (): void {
    [$token, , $story] = reactingLink();

    $this->post("/l/{$token}/stories/{$story->id}/reactions", [
        'type' => 'thanks',
        'comment' => str_repeat('a', 281),
    ])->assertSessionHasErrors('comment');

    expect(Reaction::query()->count())->toBe(0);
});

it('accepte un mot vide comme une absence de mot', function (): void {
    [$token, , $story] = reactingLink();

    $this->post("/l/{$token}/stories/{$story->id}/reactions", [
        'type' => 'heart',
        'comment' => '   ',
    ])->assertRedirect();

    expect(Reaction::query()->sole()->comment)->toBeNull();
});

it('refuse une réaction sur une histoire non visible', function (): void {
    $member = FamilyMember::factory()->create();
    $story = Story::factory()->toReview()->create(['project_id' => $member->project_id]);
    $token = app(TokenService::class)->issue(TokenType::ListenProject, $member, ['listen'])->plain;

    $this->post("/l/{$token}/stories/{$story->id}/reactions", ['type' => 'heart'])->assertNotFound();

    expect(Reaction::query()->count())->toBe(0);
});

it('refuse une réaction sur une histoire masquée entre-temps', function (): void {
    [$token, , $story] = reactingLink();

    $this->post("/l/{$token}/stories/{$story->id}/reactions", ['type' => 'heart'])->assertRedirect();

    app(HideStoryAction::class)->handle($story);

    // Entre le chargement de la page et le tap, le narrateur a pu masquer son
    // récit. Sans cette seconde vérification, il serait notifié à propos de
    // ce qu'il vient de cacher.
    $this->post("/l/{$token}/stories/{$story->id}/reactions", ['type' => 'thanks'])->assertNotFound();

    expect(Reaction::query()->count())->toBe(1);
});

it('refuse un type de réaction inventé', function (): void {
    [$token, , $story] = reactingLink();

    // Aucun pouce baissé : le produit ne propose pas de désapprouver le
    // souvenir de quelqu'un, et la validation le refuse aussi.
    $this->post("/l/{$token}/stories/{$story->id}/reactions", ['type' => 'thumbs_down'])
        ->assertSessionHasErrors('type');

    expect(Reaction::query()->count())->toBe(0);
});

it('mesure la réaction, sans citer le mot', function (): void {
    $analytics = fakeAnalytics();
    [$token, $member, $story] = reactingLink();

    $this->post("/l/{$token}/stories/{$story->id}/reactions", [
        'type' => 'thanks',
        'comment' => 'Un mot très personnel',
    ])->assertRedirect();

    $captured = $analytics->captured(AnalyticsEvent::ReactionSent);

    expect($captured)->toHaveCount(1)
        ->and($captured[0]['properties']['has_comment'])->toBeTrue()
        ->and($captured[0]['distinct_id'])->toBe($member->id)
        // Une mesure ne transporte jamais le contenu d'un message.
        ->and(json_encode($captured[0]['properties']))->not->toContain('personnel');
});

it('demande la notification du narrateur, différée', function (): void {
    [$token, , $story] = reactingLink();

    $this->post("/l/{$token}/stories/{$story->id}/reactions", ['type' => 'heart'])->assertRedirect();

    // Différée d'une minute : le temps d'agréger un cœur et un merci envoyés
    // d'affilée en une seule notification.
    Queue::assertPushed(NotifyNarratorOfReactions::class);
});

it('ramène le proche sur l’histoire, pas sur la liste', function (): void {
    [$token, , $story] = reactingLink();

    // Le proche est arrivé par une navigation Inertia : l'en-tête `Referer`
    // pointe encore la liste, donc `back()` lui ferait perdre sa place et la
    // confirmation de son geste.
    $this->post("/l/{$token}/stories/{$story->id}/reactions", ['type' => 'heart'])
        ->assertRedirect(route('family.stories.show', ['token' => $token, 'story' => $story->id]));
});

it('nomme le narrateur dans la confirmation', function (): void {
    [$token, , $story] = reactingLink();

    $this->post("/l/{$token}/stories/{$story->id}/reactions", ['type' => 'thanks'])
        ->assertSessionHas('status', __('family.reaction.sent', [
            'first_name' => $story->narrator->first_name,
        ]));

    // Le message dit à qui le mot va : c'est ce qui donne envie de l'écrire.
    expect(session('status'))->toContain($story->narrator->first_name);
});
