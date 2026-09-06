<?php

declare(strict_types=1);

use App\Books\IssueQrToken;
use App\Enums\TokenType;
use App\Models\Book;
use App\Models\BookChapter;
use App\Models\ListenEvent;
use App\Models\Story;
use App\States\Story\Hidden;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

/**
 * La page qu'ouvre un QR imprimé.
 *
 * Trois promesses du dossier tiennent ici (doc 04 §7) : elle se lit **sans
 * compte** — un livre se prête, et exiger une identification pour entendre la
 * voix qu'on tient entre les mains serait absurde ; le code famille est
 * **facultatif** et posé par la famille ; et le retrait d'un récit **éteint le
 * QR** sans pour autant prétendre effacer le papier.
 */
function qrDe(Story $story): string
{
    $book = Book::factory()->create(['project_id' => $story->project_id]);
    $chapter = new BookChapter(['position' => 10, 'included' => true]);
    $chapter->story()->associate($story);
    $chapter->book()->associate($book);
    $chapter->save();

    app(IssueQrToken::class)->handle($chapter);

    return IssueQrToken::plainFor($chapter);
}

it('ouvre l’écoute sans compte ni code', function (): void {
    $story = Story::factory()->shared()->create(['title' => 'Le fournil']);

    $this->get('/q/'.qrDe($story))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('family/Story')
            ->where('mode', 'qr')
            ->where('title', 'Le fournil'));
});

it('ne montre ni réactions ni voisines ni dépôt de photo', function (): void {
    $story = Story::factory()->shared()->create();

    $this->get('/q/'.qrDe($story))
        ->assertInertia(fn ($page) => $page
            // Ces gestes ont un auteur, et on ne sait pas qui tient le livre.
            ->where('yourReactions', [])
            ->where('canContribute', false)
            ->where('siblings', []));
});

it('demande le code quand la famille en a posé un', function (): void {
    $story = Story::factory()->shared()->create();
    $story->project->forceFill(['family_code_hash' => Hash::make('KERHOSTIN')])->save();

    $this->get('/q/'.qrDe($story))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('qr/FamilyCode'));
});

it('retient le déverrouillage et ouvre l’écoute ensuite', function (): void {
    $story = Story::factory()->shared()->create();
    $story->project->forceFill(['family_code_hash' => Hash::make('KERHOSTIN')])->save();
    $token = qrDe($story);

    $reponse = $this->post("/q/{$token}/code", ['code' => 'KERHOSTIN']);
    $reponse->assertRedirect("/q/{$token}");

    // `withUnencryptedCookies` : la réponse porte la valeur en clair, c'est
    // le middleware qui la chiffre en sortant. La renvoyer telle quelle par
    // `withCookies` la ferait échouer au déchiffrement, donc disparaître.
    $this->withUnencryptedCookies(collect($reponse->headers->getCookies())
        ->mapWithKeys(fn ($cookie): array => [$cookie->getName() => $cookie->getValue()])
        ->all())
        ->get("/q/{$token}")
        ->assertInertia(fn ($page) => $page->component('family/Story'));
});

it('refuse un mauvais code sans dire combien d’essais restent', function (): void {
    $story = Story::factory()->shared()->create();
    $story->project->forceFill(['family_code_hash' => Hash::make('KERHOSTIN')])->save();
    $token = qrDe($story);

    $this->from("/q/{$token}")
        ->post("/q/{$token}/code", ['code' => 'AUTRE'])
        ->assertRedirect("/q/{$token}")
        ->assertSessionHasErrors('code');
});

it('bloque après cinq essais, par livre et non par adresse', function (): void {
    $story = Story::factory()->shared()->create();
    $story->project->forceFill(['family_code_hash' => Hash::make('KERHOSTIN')])->save();
    $token = qrDe($story);

    for ($i = 0; $i < 5; $i++) {
        $this->post("/q/{$token}/code", ['code' => 'FAUX']);
    }

    // Même le bon code ne passe plus : c'est le point d'un verrouillage.
    $this->from("/q/{$token}")
        ->post("/q/{$token}/code", ['code' => 'KERHOSTIN'])
        ->assertSessionHasErrors('code');
});

it('éteint le QR d’une histoire retirée, sans prétendre effacer le papier', function (): void {
    $story = Story::factory()->shared()->create();
    $token = qrDe($story);

    $story->state->transitionTo(Hidden::class);

    $this->get("/q/{$token}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('qr/Unavailable')
            // Rien de l'histoire ne doit fuir dans la réponse.
            ->missing('title')
            ->missing('audioUrl'));
});

it('compte l’écoute avec le type qr et sans proche', function (): void {
    $story = Story::factory()->shared()->create();
    $token = qrDe($story);

    $this->postJson("/q/{$token}/listen", ['seconds' => 35])
        ->assertOk()
        ->assertJson(['reached_30s' => true]);

    $event = ListenEvent::query()->firstOrFail();

    expect($event->token_type)->toBe(TokenType::Qr)
        ->and($event->family_member_id)->toBeNull()
        ->and($event->story_id)->toBe($story->id);
});

it('ne compte pas l’écoute d’un livre resté verrouillé', function (): void {
    $story = Story::factory()->shared()->create();
    $story->project->forceFill(['family_code_hash' => Hash::make('KERHOSTIN')])->save();

    $this->postJson('/q/'.qrDe($story).'/listen', ['seconds' => 35])->assertForbidden();

    expect(ListenEvent::query()->count())->toBe(0);
});
