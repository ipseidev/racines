<?php

declare(strict_types=1);

use App\Enums\AnalyticsEvent;
use App\Enums\TokenType;
use App\Models\FamilyMember;
use App\Models\Reaction;
use App\Models\Recording;
use App\Models\Story;
use App\Models\Transcript;
use App\Services\Tokens\TokenService;

/**
 * Une histoire partagée, avec son audio dérivé et ses deux textes.
 *
 * @return array{string, FamilyMember, Story}
 */
function familyStory(): array
{
    $storage = fakeMediaStorage();

    $member = FamilyMember::factory()->create();
    $story = Story::factory()->shared()->create([
        'project_id' => $member->project_id,
        'title' => 'L’odeur du pain',
    ]);

    $recording = Recording::factory()->confirmed()->create(['story_id' => $story->id]);
    $recording->forceFill([
        'derived_mp3_path' => 'derives/pain.mp3',
        'duration_seconds' => '124.40',
    ])->save();
    $storage->put('derives/pain.mp3', 'mp3');

    Transcript::factory()->create(['story_id' => $story->id, 'recording_id' => $recording->id]);
    Transcript::factory()->fluide()->create(['story_id' => $story->id, 'recording_id' => $recording->id]);

    $issued = app(TokenService::class)->issue(TokenType::ListenProject, $member, ['listen', 'react']);

    return [$issued->plain, $member, $story->refresh()];
}

it('rend le titre, la question, l’audio et les deux textes', function (): void {
    [$token, , $story] = familyStory();

    $this->get("/l/{$token}/stories/{$story->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('family/Story')
            ->where('title', 'L’odeur du pain')
            ->where('question', $story->questionText())
            ->where('durationSeconds', 124)
            ->where('text', 'Je me souviens de la maison de Kerhostin.')
            ->where('verbatim', 'Alors euh je me souviens de la maison de Kerhostin.')
            ->has('audioUrl')
            ->has('aiLabel'),
        );
});

it('nomme la personne dans la mention d’IA', function (): void {
    [$token, , $story] = familyStory();

    // La mention nomme la personne, pas le modèle : c'est sa voix qui est la
    // référence, et le lecteur peut toujours l'écouter.
    $this->get("/l/{$token}/stories/{$story->id}")
        ->assertInertia(fn ($page) => $page
            ->where('aiLabel', __('family.story.ai_label', [
                'first_name' => $story->narrator->first_name,
            ])),
        );
});

it('donne une URL audio temporaire, sans donnée personnelle', function (): void {
    [$token, , $story] = familyStory();

    $url = $this->get("/l/{$token}/stories/{$story->id}")
        ->viewData('page')['props']['audioUrl'];

    expect($url)->toBeString()
        ->and($url)->not->toContain($story->narrator->first_name)
        ->and($url)->not->toContain((string) $story->narrator->phone_e164)
        ->and($url)->not->toContain($token);
});

it('montre les prénoms de ceux qui ont réagi, jamais leurs coordonnées', function (): void {
    [$token, , $story] = familyStory();
    $other = FamilyMember::factory()->create([
        'project_id' => $story->project_id,
        'display_name' => 'Marie',
        'email' => 'marie@example.test',
    ]);
    Reaction::factory()->thanks('Merci maman')->create([
        'story_id' => $story->id,
        'family_member_id' => $other->id,
    ]);

    $props = $this->get("/l/{$token}/stories/{$story->id}")->viewData('page')['props'];
    $json = json_encode($props, JSON_UNESCAPED_UNICODE);

    expect($props['reactions'][0]['name'])->toBe('Marie')
        ->and($props['reactions'][0]['comment'])->toBe('Merci maman')
        // Un lien d'écoute ne doit pas devenir un carnet d'adresses.
        ->and($json)->not->toContain('marie@example.test')
        ->and($json)->not->toContain($other->id);
});

it('propose l’histoire précédente et la suivante', function (): void {
    [$token, $member, $story] = familyStory();

    $previous = Story::factory()->shared()->create(['project_id' => $member->project_id]);
    $previous->forceFill(['shared_at' => now()->subDays(3)])->save();
    $next = Story::factory()->shared()->create(['project_id' => $member->project_id]);
    $next->forceFill(['shared_at' => now()->addDay()])->save();
    $story->forceFill(['shared_at' => now()])->save();

    $this->get("/l/{$token}/stories/{$story->id}")
        ->assertInertia(fn ($page) => $page
            ->where('siblings.previous', $next->id)
            ->where('siblings.next', $previous->id),
        );
});

it('mesure l’ouverture de la page', function (): void {
    $analytics = fakeAnalytics();
    [$token, $member, $story] = familyStory();

    $this->get("/l/{$token}/stories/{$story->id}")->assertOk();

    $captured = $analytics->captured(AnalyticsEvent::StoryPageOpened);

    expect($captured)->toHaveCount(1)
        ->and($captured[0]['properties']['story_id'])->toBe($story->id)
        ->and($captured[0]['distinct_id'])->toBe($member->id);
});
