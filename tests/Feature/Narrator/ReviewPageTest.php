<?php

declare(strict_types=1);

use App\Actions\IssueRecordToken;
use App\Enums\StoryVisibility;
use App\Enums\TokenIssuedReason;
use App\Enums\TranscriptKind;
use App\Enums\ValidatedVia;
use App\Models\FamilyMember;
use App\Models\Recording;
use App\Models\Story;
use App\Models\Transcript;
use App\States\Story\Shared;
use App\States\Story\ToReview;
use App\States\Story\Transcribed;
use App\States\Story\Validated;

/**
 * Une histoire à relire : verbatim, mise au propre, audio dérivé, et un lien
 * d'enregistrement vivant — la relecture se fait sur ce même jeton.
 *
 * @return array{string, Story}
 */
function reviewLink(): array
{
    $storage = fakeMediaStorage();

    $story = Story::factory()->toReview()->create(['title' => 'Les crêpes de Kerhostin']);
    $recording = Recording::factory()->confirmed()->create(['story_id' => $story->id]);
    $recording->forceFill(['derived_mp3_path' => 'derives/histoire.mp3'])->save();
    $storage->put('derives/histoire.mp3', 'mp3');

    Transcript::factory()->create([
        'story_id' => $story->id,
        'recording_id' => $recording->id,
        'text' => 'Alors euh je me souviens de la maison de Kerhostin.',
    ]);
    Transcript::factory()->fluide()->create([
        'story_id' => $story->id,
        'recording_id' => $recording->id,
    ]);

    $issued = app(IssueRecordToken::class)->handle($story, TokenIssuedReason::Rotation);

    return [$issued->plain, $story->refresh()];
}

it('rend le texte mis au propre, le mot à mot et l’audio', function (): void {
    [$token, $story] = reviewLink();

    $this->get("/r/{$token}/review")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('narrator/Review')
            ->where('question', $story->questionText())
            ->where('title', 'Les crêpes de Kerhostin')
            ->where('fluide', 'Je me souviens de la maison de Kerhostin.')
            ->where('verbatim', 'Alors euh je me souviens de la maison de Kerhostin.')
            // La mention est portée par la page, pas laissée au front : elle
            // dit d'où vient le texte, et c'est une obligation, pas un ornement.
            ->has('aiLabel')
            ->has('audioUrl')
            ->has('familyMembers', 0),
        );
});

it('n’expose aucune donnée personnelle du narrateur', function (): void {
    [$token, $story] = reviewLink();
    $narrator = $story->narrator;
    $narrator->forceFill(['email' => 'odette@example.test'])->save();

    $content = (string) $this->get("/r/{$token}/review")->getContent();

    // Le prénom est légitime — c'est son histoire ; la coordonnée, non : un
    // lien porteur ne doit pas devenir une fiche de renseignement.
    expect($content)->not->toContain((string) $narrator->phone_e164)
        ->and($content)->not->toContain('odette@example.test')
        ->and($content)->not->toContain($story->narrator_id);
});

it('liste les proches du projet pour le choix de visibilité', function (): void {
    [$token, $story] = reviewLink();
    $member = FamilyMember::factory()->create([
        'project_id' => $story->project_id,
        'display_name' => 'Camille',
    ]);
    FamilyMember::factory()->create(['display_name' => 'Étranger']);

    $this->get("/r/{$token}/review")
        ->assertInertia(fn ($page) => $page
            ->has('familyMembers', 1)
            ->where('familyMembers.0.id', $member->id)
            ->where('familyMembers.0.name', 'Camille'),
        );
});

it('corrige le texte sans écraser la mise au propre', function (): void {
    [$token, $story] = reviewLink();

    $this->post("/r/{$token}/review/edit", ['text' => 'Je me souviens très bien de Kerhostin.'])
        ->assertRedirect();

    $story->refresh();

    expect(Transcript::readableFor($story)?->text)->toBe('Je me souviens très bien de Kerhostin.')
        ->and($story->transcripts()->ofKind(TranscriptKind::Edited)->count())->toBe(1)
        // Ni le verbatim ni le fluide n'ont bougé : une correction ajoute.
        ->and($story->transcripts()->ofKind(TranscriptKind::Fluide)->current()->sole()->text)
        ->toBe('Je me souviens de la maison de Kerhostin.');
});

it('refuse une correction vide', function (): void {
    [$token] = reviewLink();

    $this->post("/r/{$token}/review/edit", ['text' => '   '])->assertSessionHasErrors('text');
});

it('partage après relecture, en validant par ce chemin', function (): void {
    [$token, $story] = reviewLink();

    $this->post("/r/{$token}/review/decision", ['decision' => 'share'])->assertRedirect();

    $story->refresh();

    expect($story->state)->toBeInstanceOf(Shared::class)
        ->and($story->validated_via)->toBe(ValidatedVia::PostTranscription)
        ->and($story->validated_at)->not->toBeNull()
        ->and($story->visibility)->toBe(StoryVisibility::AllFamily);
});

it('partage en restreignant l’écoute aux proches désignés', function (): void {
    [$token, $story] = reviewLink();
    $allowed = FamilyMember::factory()->create(['project_id' => $story->project_id]);
    $excluded = FamilyMember::factory()->create(['project_id' => $story->project_id]);

    $this->post("/r/{$token}/review/decision", [
        'decision' => 'share',
        'visibility' => 'restricted',
        'family_member_ids' => [$allowed->id],
    ])->assertRedirect();

    $story->refresh();

    expect($story->state)->toBeInstanceOf(Shared::class)
        ->and($story->isVisibleTo($allowed))->toBeTrue()
        ->and($story->isVisibleTo($excluded))->toBeFalse();
});

it('garde pour le livre : validée, jamais écoutable en ligne', function (): void {
    [$token, $story] = reviewLink();

    $this->post("/r/{$token}/review/decision", [
        'decision' => 'keep_private',
        'keep_for_book' => true,
    ])->assertRedirect();

    $story->refresh();

    expect($story->state)->toBeInstanceOf(Validated::class)
        ->and($story->visibility)->toBe(StoryVisibility::BookOnly)
        ->and($story->validated_via)->toBe(ValidatedVia::PostTranscription)
        ->and($story->isVisibleToFamily())->toBeFalse();
});

it('garde pour soi sans le livre : l’histoire redevient simplement transcrite', function (): void {
    [$token, $story] = reviewLink();

    $this->post("/r/{$token}/review/decision", ['decision' => 'keep_private'])->assertRedirect();

    $story->refresh();

    // Ni validée, ni partagée, ni imprimable — et réversible depuis l'espace
    // narrateur : c'est la règle §9, en cas de doute l'histoire reste privée
    // et hors livre.
    expect($story->state)->toBeInstanceOf(Transcribed::class)
        ->and($story->validated_at)->toBeNull()
        ->and($story->visibility)->not->toBe(StoryVisibility::BookOnly);
});

it('remet le choix à plus tard sans rien changer', function (): void {
    [$token, $story] = reviewLink();

    $this->post("/r/{$token}/review/decision", ['decision' => 'decide_later'])->assertRedirect();

    $story->refresh();

    expect($story->state)->toBeInstanceOf(ToReview::class)
        ->and($story->validated_at)->toBeNull();
});

it('ferme le lien de relecture après le partage', function (): void {
    [$token] = reviewLink();

    $this->post("/r/{$token}/review/decision", ['decision' => 'share'])->assertRedirect();

    // Le même jeton ne doit plus rien porter : ni relecture, ni nouvel
    // enregistrement par-dessus une histoire validée. Un lien révoqué rend
    // 410 et sa page amicale (bloc 03), pas un 404 sec.
    $this->get("/r/{$token}/review")->assertStatus(410);
});

it('refuse la relecture d’une histoire pas encore transcrite', function (): void {
    $story = Story::factory()->recorded()->create();
    $issued = app(IssueRecordToken::class)->handle($story);

    $this->get("/r/{$issued->plain}/review")->assertNotFound();
});

it('refuse une décision inventée', function (): void {
    [$token, $story] = reviewLink();

    $this->post("/r/{$token}/review/decision", ['decision' => 'brûler'])
        ->assertSessionHasErrors('decision');

    expect($story->refresh()->state)->toBeInstanceOf(ToReview::class);
});
