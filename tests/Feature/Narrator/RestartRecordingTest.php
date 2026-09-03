<?php

declare(strict_types=1);

use App\Enums\ShareDecision;
use App\Enums\TokenType;
use App\Models\AccessToken;
use App\Models\Recording;
use App\Models\Story;
use App\Services\Tokens\TokenService;
use App\States\Story\Proposed;
use App\States\Story\Recorded;
use App\States\Story\Shared;
use App\States\Story\ToReview;
use App\States\Story\Transcribed;
use Illuminate\Support\Facades\DB;

/**
 * « Recommencer », depuis le lien d'une histoire déjà racontée.
 *
 * Le bloc 04 le promet — « ce n'est pas un cul-de-sac » — et la page
 * `AlreadyRecorded` porte le bouton depuis le début. Il n'a jamais rien fait :
 * il appelait une prop `onRestart` qu'une page rendue par le serveur ne peut
 * pas recevoir, une fonction ne traversant pas Inertia. Et même rebranché, la
 * chaîne aurait échoué à la confirmation : la seule transition vers
 * « enregistrée » partait de « proposée ».
 *
 * L'histoire revient donc à « proposée », et l'ancien enregistrement reste en
 * place, `is_current` à faux : on n'efface pas ce qu'elle a dit, on ajoute.
 */
function recordTokenFor(Story $story): string
{
    $plain = str_repeat('r', 43);

    $token = new AccessToken([
        'type' => TokenType::Record,
        'scope' => ['record'],
        'expires_at' => now()->addDays(7),
    ]);

    $token->token_hash = TokenService::hash($plain);
    $token->subject()->associate($story);
    $token->save();

    return $plain;
}

it('ramène une histoire enregistrée à « proposée » et garde l’ancien enregistrement', function (): void {
    $story = Story::factory()->create();
    $story->state->transitionTo(Recorded::class);
    $previous = Recording::factory()->confirmed()->create(['story_id' => $story->id]);

    $plain = recordTokenFor($story);

    $this->post("/r/{$plain}/restart")->assertRedirect("/r/{$plain}");

    expect($story->fresh()->state)->toBeInstanceOf(Proposed::class)
        ->and($story->fresh()->recorded_at)->toBeNull()
        ->and($previous->fresh()->is_current)->toBeTrue();
});

it('accepte aussi une histoire transcrite ou en relecture', function (string $state): void {
    $story = Story::factory()->create();
    $story->state->transitionTo(Recorded::class);
    $story->state->transitionTo(Transcribed::class);

    if ($state === 'to_review') {
        $story->state->transitionTo(ToReview::class);
    }

    $plain = recordTokenFor($story);

    $this->post("/r/{$plain}/restart")->assertRedirect("/r/{$plain}");

    expect($story->fresh()->state)->toBeInstanceOf(Proposed::class);
})->with(['transcribed', 'to_review']);

it('oublie la décision de partage : elle n’a pas encore répondu de nouveau', function (): void {
    $story = Story::factory()->create();
    $story->state->transitionTo(Recorded::class);
    $story->state->transitionTo(Transcribed::class);
    $story->state->transitionTo(ToReview::class);
    $story->forceFill([
        'share_decision' => ShareDecision::KeepPrivate,
        'share_decided_at' => now(),
    ])->save();

    $plain = recordTokenFor($story);
    $this->post("/r/{$plain}/restart");

    expect($story->fresh()->share_decision)->toBeNull()
        ->and($story->fresh()->share_decided_at)->toBeNull();
});

it('refuse de recommencer une histoire déjà partagée à la famille', function (): void {
    $story = Story::factory()->shared()->create();
    $plain = recordTokenFor($story);

    $this->post("/r/{$plain}/restart")->assertForbidden();

    expect($story->fresh()->state)->toBeInstanceOf(Shared::class);
});

it('rend de nouveau la page d’enregistrement après avoir recommencé', function (): void {
    $story = Story::factory()->create();
    $story->state->transitionTo(Recorded::class);
    Recording::factory()->confirmed()->create(['story_id' => $story->id]);

    $plain = recordTokenFor($story);

    $this->get("/r/{$plain}")
        ->assertInertia(fn ($page) => $page->component('narrator/AlreadyRecorded'));

    $this->post("/r/{$plain}/restart");

    $this->get("/r/{$plain}")
        ->assertInertia(fn ($page) => $page->component('narrator/Record'));
});

it('journalise l’acte, parce qu’un enregistrement remplacé doit s’expliquer', function (): void {
    $story = Story::factory()->create();
    $story->state->transitionTo(Recorded::class);
    Recording::factory()->confirmed()->create(['story_id' => $story->id]);

    $plain = recordTokenFor($story);
    $this->post("/r/{$plain}/restart");

    $this->assertDatabaseHas('audit_logs', [
        'action' => 'restarted Story',
        'subject_id' => $story->id,
    ]);
});

it('tolère un second appel : recommencer deux fois n’est pas une faute', function (): void {
    // Le défaut signalé pendant le checkpoint. Le premier clic ramène
    // l'histoire à « proposée » ; le second — bouton retour, double tap,
    // réseau lent — tombait sur une garde qui refusait cet état, alors que
    // l'action elle-même est écrite pour ne rien faire. « Il n'y a rien à
    // faire » et « c'est interdit » sont deux réponses différentes, et un
    // refus sec au visage de quelqu'un de 82 ans n'est pas la bonne.
    $story = Story::factory()->create();
    $story->state->transitionTo(Recorded::class);
    Recording::factory()->confirmed()->create(['story_id' => $story->id]);

    $plain = recordTokenFor($story);

    $this->post("/r/{$plain}/restart")->assertRedirect("/r/{$plain}");
    $this->post("/r/{$plain}/restart")->assertRedirect("/r/{$plain}");

    expect($story->fresh()->state)->toBeInstanceOf(Proposed::class);
});

it('ne journalise pas deux fois un acte qui n’a eu lieu qu’une fois', function (): void {
    $story = Story::factory()->create();
    $story->state->transitionTo(Recorded::class);
    Recording::factory()->confirmed()->create(['story_id' => $story->id]);

    $plain = recordTokenFor($story);

    $this->post("/r/{$plain}/restart");
    $this->post("/r/{$plain}/restart");

    expect(DB::table('audit_logs')
        ->where('action', 'restarted Story')
        ->where('subject_id', $story->id)
        ->count())->toBe(1);
});
