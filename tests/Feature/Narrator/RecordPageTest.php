<?php

declare(strict_types=1);

use App\Actions\AttachPhoto;
use App\Enums\AddressForm;
use App\Enums\TokenType;
use App\Models\Recording;
use App\Models\Story;
use App\Services\Tokens\TokenService;
use App\States\Story\Validated;
use App\Support\PhotoPresenter;
use Illuminate\Http\Testing\File;

function pageLink(Story $story): string
{
    return app(TokenService::class)->issue(TokenType::Record, $story)->plain;
}

it('rend la page d’enregistrement avec la question, l’état et les limites', function (): void {
    $story = Story::factory()->proposed()->create();
    $token = pageLink($story);

    $this->get("/r/{$token}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('narrator/Record')
            ->where('question', $story->questionText())
            ->where('firstName', $story->narrator->first_name)
            ->where('addressForm', 'vous')
            ->where('state', 'proposed')
            ->where('limits.softWarningSeconds', 600)
            ->where('limits.hardStopSeconds', 1200)
            ->where('limits.maxBytes', 209_715_200)
            ->where('limits.segmentMilliseconds', 5000));
});

it('ne fait descendre jusqu’au navigateur ni identifiant d’histoire ni coordonnée', function (): void {
    $story = Story::factory()->proposed()->create();
    $token = pageLink($story);

    $response = $this->get("/r/{$token}");
    $content = (string) $response->getContent();

    expect($content)->not->toContain($story->id)
        ->and($content)->not->toContain((string) $story->narrator->phone_e164)
        ->and($content)->toContain(hash('sha256', $story->id));
});

it('suit le tutoiement réglé sur le projet', function (): void {
    $story = Story::factory()->proposed()->create();
    $story->project->update(['address_form' => AddressForm::Tu]);

    $this->get('/r/'.pageLink($story))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('addressForm', 'tu'));
});

it('dit à un narrateur qu’il a déjà répondu, avec la date', function (): void {
    $story = Story::factory()->recorded()->create();
    $recording = Recording::factory()->confirmed()->create(['story_id' => $story->id]);

    $this->get('/r/'.pageLink($story))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('narrator/AlreadyRecorded')
            ->where('recordedAt', $recording->confirmed_at?->toIso8601String())
            ->where('answerType', 'audio'));
});

it('sert la page amicale quand l’histoire est validée : le lien est révoqué', function (): void {
    $story = Story::factory()->toReview()->create();
    $token = pageLink($story);

    $story->state->transitionTo(Validated::class);

    $this->get("/r/{$token}")
        ->assertStatus(410)
        ->assertInertia(fn ($page) => $page
            ->component('narrator/LinkUnavailable')
            ->where('reason', 'revoked'));
});

/**
 * Le tour de chauffe est proposé **une fois** (T-247).
 *
 * Il se décidait sur « cette personne n'a jamais enregistré d'histoire », ce
 * qui n'est pas la même question : quelqu'un qui joue les quinze secondes puis
 * referme sans répondre n'a rien enregistré, et le retrouvait à l'ouverture
 * suivante — à chaque fois, tant qu'un récit n'était pas allé au bout. C'est
 * exactement la répétition qui fait passer le produit pour une machine qui ne
 * reconnaît personne.
 */
it('propose le tour de chauffe au tout premier lien', function (): void {
    $story = Story::factory()->proposed()->create();

    $this->get('/r/'.pageLink($story))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('firstTime', true));
});

it('ne repropose pas le tour de chauffe une fois qu’il a été joué ou passé', function (string $event): void {
    $story = Story::factory()->proposed()->create();
    $token = pageLink($story);

    // Le tour est joué, ou passé : les deux valent réponse.
    $this->postJson("/r/{$token}/events", ['event' => $event])
        ->assertStatus(202);

    expect($story->narrator->refresh()->first_run_at)->not->toBeNull();

    // La personne n'a toujours rien enregistré, et ne le revoit pourtant plus.
    $this->get("/r/{$token}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('firstTime', false));
})->with(['first_run_done', 'first_run_skipped']);

it('ne retient pas la date du tour de chauffe sur un autre événement', function (): void {
    $story = Story::factory()->proposed()->create();
    $token = pageLink($story);

    $this->postJson("/r/{$token}/events", ['event' => 'mic_granted'])
        ->assertStatus(202);

    expect($story->narrator->refresh()->first_run_at)->toBeNull();
});

it('garde la première date du tour de chauffe, sans la réécrire', function (): void {
    $story = Story::factory()->proposed()->create();
    $token = pageLink($story);

    $this->postJson("/r/{$token}/events", ['event' => 'first_run_skipped'])
        ->assertStatus(202);

    $premiere = $story->narrator->refresh()->first_run_at;

    $this->travel(1)->hours();
    $this->postJson("/r/{$token}/events", ['event' => 'first_run_done'])
        ->assertStatus(202);

    expect($story->narrator->refresh()->first_run_at?->toIso8601String())
        ->toBe($premiere?->toIso8601String());
});

/**
 * Une question posée **avec** une image (T-251).
 *
 * Le dépôt existait depuis le bloc 12 — le tableau de bord de
 * l'Initiateur·rice porte un téléverseur sur chaque histoire, y compris celles
 * encore en PROPOSÉE — mais rien ne descendait l'image jusqu'à la narratrice :
 * elle attendait en base d'être vue après l'enregistrement, c'est-à-dire trop
 * tard pour servir à quoi que ce soit.
 *
 * Ce que ces tests protègent : la page ne montre **que** ce qui pose la
 * question, jamais ce que la narratrice a joint à sa réponse.
 */
it('descend les photos qui posent la question', function (): void {
    $story = Story::factory()->proposed()->create();

    app(AttachPhoto::class)->handle(
        $story,
        File::image('souvenir.jpg', 900, 600),
        $story->project->owner,
        'La maison de Saint-Léon',
    );

    $this->get('/r/'.pageLink($story->refresh()))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('questionPhotos', 1)
            ->where('questionPhotos.0.caption', 'La maison de Saint-Léon')
            ->has('questionPhotos.0.url')
            ->has('questionPhotos.0.alt')
        );
});

it('ne prend pas pour une question la photo jointe à la réponse', function (): void {
    $story = Story::factory()->recorded()->create();

    // Déposée alors que l'histoire est déjà racontée : elle illustre le récit.
    app(AttachPhoto::class)->handle(
        $story,
        File::image('apres.jpg', 900, 600),
        $story->project->owner,
        null,
    );

    expect(
        PhotoPresenter::promptsForStory($story->refresh()),
    )->toBe([]);
});
