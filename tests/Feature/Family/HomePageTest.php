<?php

declare(strict_types=1);

use App\Enums\AnalyticsEvent;
use App\Enums\TokenType;
use App\Models\FamilyMember;
use App\Models\ListenEvent;
use App\Models\Reaction;
use App\Models\Story;
use App\Models\User;
use App\Services\Tokens\TokenService;

/**
 * @return array{string, FamilyMember}
 */
function familyHome(?FamilyMember $member = null): array
{
    $member ??= FamilyMember::factory()->create();
    $issued = app(TokenService::class)->issue(TokenType::ListenProject, $member, ['listen', 'react']);

    return [$issued->plain, $member];
}

it('liste les histoires visibles, les plus récemment partagées d’abord', function (): void {
    $member = FamilyMember::factory()->create();
    $older = Story::factory()->shared()->create(['project_id' => $member->project_id, 'title' => 'Ancienne']);
    $older->forceFill(['shared_at' => now()->subDays(5)])->save();
    $newer = Story::factory()->shared()->create(['project_id' => $member->project_id, 'title' => 'Récente']);
    $newer->forceFill(['shared_at' => now()])->save();

    [$token] = familyHome($member);

    $this->get("/l/{$token}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('family/Home')
            ->has('stories', 2)
            ->where('stories.0.title', 'Récente')
            ->where('stories.1.title', 'Ancienne'),
        );
});

it('marque « Nouvelle » ce que ce proche n’a pas encore écouté', function (): void {
    $member = FamilyMember::factory()->create();
    $listened = Story::factory()->shared()->create(['project_id' => $member->project_id, 'title' => 'Écoutée']);
    $fresh = Story::factory()->shared()->create(['project_id' => $member->project_id, 'title' => 'Pas écoutée']);

    ListenEvent::factory()->listened()->create([
        'story_id' => $listened->id,
        'family_member_id' => $member->id,
    ]);

    // Le badge dit « pas encore écoutée par **vous** » : une écoute par un
    // autre proche ne l'enlève pas.
    ListenEvent::factory()->listened()->create(['story_id' => $fresh->id]);

    [$token] = familyHome($member);

    $props = $this->get("/l/{$token}")->viewData('page')['props']['stories'];
    $byTitle = collect($props)->keyBy('title');

    expect($byTitle['Écoutée']['isNew'])->toBeFalse()
        ->and($byTitle['Pas écoutée']['isNew'])->toBeTrue();
});

it('n’expose aucune donnée d’une histoire non visible', function (): void {
    $member = FamilyMember::factory()->create();
    Story::factory()->shared()->create(['project_id' => $member->project_id, 'title' => 'Partagée']);
    $hidden = Story::factory()->toReview()->create([
        'project_id' => $member->project_id,
        'title' => 'Confidentielle',
    ]);

    [$token] = familyHome($member);

    $response = $this->get("/l/{$token}");
    $json = json_encode($response->viewData('page')['props'], JSON_UNESCAPED_UNICODE);

    // L'assertion porte sur le JSON des props, pas sur le rendu : c'est là
    // qu'une donnée de trop passerait inaperçue.
    expect($json)->not->toContain('Confidentielle')
        ->and($json)->not->toContain($hidden->id)
        ->and($json)->toContain('Partagée');
});

it('rappelle qui a invité, et de ne pas faire circuler le lien', function (): void {
    $inviter = User::factory()->create(['name' => 'Claire']);
    $member = FamilyMember::factory()->create(['invited_by_user_id' => $inviter->id]);
    Story::factory()->shared()->create(['project_id' => $member->project_id]);

    [$token] = familyHome($member);

    $this->get("/l/{$token}")
        ->assertInertia(fn ($page) => $page->where('inviterName', 'Claire'));

    // Le texte du pied de page est porté par les fichiers de langue, et
    // nommer l'invitant est ce qui répond à « pourquoi ai-je ce lien ? ».
    expect(__('family.home.footer', ['inviter' => 'Claire']))
        ->toContain('Claire')
        ->toContain('Ne le transmettez qu’à des proches');
});

it('mesure l’ouverture du lien', function (): void {
    $analytics = fakeAnalytics();
    $member = FamilyMember::factory()->create();
    [$token] = familyHome($member);

    $this->get("/l/{$token}")->assertOk();

    $captured = $analytics->captured(AnalyticsEvent::FamilyLinkOpened);

    expect($captured)->toHaveCount(1)
        ->and($captured[0]['distinct_id'])->toBe($member->id)
        // Aucune donnée personnelle dans une mesure : des identifiants.
        ->and(json_encode($captured[0]['properties']))->not->toContain($member->display_name);
});

it('ne met jamais l’espace famille en cache', function (): void {
    [$token] = familyHome();

    $response = $this->get("/l/{$token}");

    expect($response->headers->get('cache-control'))->toContain('no-store')
        ->and($response->headers->get('x-robots-tag'))->toBe('noindex, nofollow');
});

it('montre les réactions déjà envoyées par ce proche', function (): void {
    $member = FamilyMember::factory()->create();
    $story = Story::factory()->shared()->create(['project_id' => $member->project_id]);
    Reaction::factory()->create(['story_id' => $story->id, 'family_member_id' => $member->id]);

    [$token] = familyHome($member);

    $this->get("/l/{$token}")
        ->assertInertia(fn ($page) => $page->where('stories.0.yourReactions', ['heart']));
});
