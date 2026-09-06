<?php

declare(strict_types=1);

use App\Books\IssueQrToken;
use App\Enums\TokenIssuedReason;
use App\Enums\TokenType;
use App\Models\AccessToken;
use App\Models\Book;
use App\Models\BookChapter;
use App\Models\Narrator;
use App\Models\Story;
use App\Services\Tokens\TokenService;
use App\Support\SensitiveGrant;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Éteindre le QR d'une histoire déjà imprimée.
 *
 * Le geste que le papier rend nécessaire : un narrateur peut changer d'avis
 * après l'impression, et le livre ne se rappelle pas. Ce qu'on peut faire,
 * c'est cesser de servir la voix — et un **seul** geste doit suffire pour
 * tous les exemplaires en circulation, ce qui est toute la raison d'être du
 * code dérivé.
 *
 * @return array{Story, string}
 */
function histoireAvecQr(): array
{
    $story = Story::factory()->shared()->create();
    $book = Book::factory()->create(['project_id' => $story->project_id]);

    $chapter = new BookChapter(['position' => 10, 'included' => true]);
    $chapter->story()->associate($story);
    $chapter->book()->associate($book);
    $chapter->save();

    app(IssueQrToken::class)->handle($chapter);

    return [$story, IssueQrToken::plainFor($chapter)];
}

it('éteint le QR depuis l’espace du narrateur', function (): void {
    [$story, $code] = histoireAvecQr();
    $narrator = $story->project->primaryNarrator;

    $espace = app(TokenService::class)->issue(
        TokenType::NarratorSpace,
        $narrator,
        ['manage'],
        reason: TokenIssuedReason::Initial,
    );
    $grant = app(TokenService::class)->issue(TokenType::SensitiveGrant, $narrator);

    // Éteindre un QR déjà imprimé est un retrait comme un autre : le jeton de
    // l'espace ouvre toutes les histoires et a pu être ouvert il y a un moment.
    $this->withCookie(SensitiveGrant::COOKIE, $grant->plain)
        ->delete("/n/{$espace->plain}/stories/{$story->getKey()}/qr")
        ->assertRedirect();

    expect(AccessToken::query()->where('type', TokenType::Qr->value)->firstOrFail()->revoked_at)
        ->not->toBeNull();

    /*
     * 410 et non 404 : le code a existé, il ne vaut plus. Et surtout, la page
     * est celle du livre — « le texte imprimé reste le vôtre » — et non le
     * « ce lien n'est plus valable » des autres espaces, qui laisserait
     * croire à quelqu'un tenant un livre qu'il s'y prend mal.
     */
    $this->get("/q/{$code}")
        ->assertStatus(410)
        ->assertInertia(fn ($page) => $page->component('qr/LinkUnavailable'));
});

it('rallume le même code, pour que le livre reparte', function (): void {
    [$story, $code] = histoireAvecQr();
    $narrator = $story->project->primaryNarrator;

    $espace = app(TokenService::class)->issue(TokenType::NarratorSpace, $narrator, ['manage']);
    $grant = app(TokenService::class)->issue(TokenType::SensitiveGrant, $narrator);

    // Une autorisation par acte : le jeton d'acte sensible est à usage
    // unique, et c'est le point.
    $this->withCookie(SensitiveGrant::COOKIE, $grant->plain)
        ->delete("/n/{$espace->plain}/stories/{$story->getKey()}/qr");

    $second = app(TokenService::class)->issue(TokenType::SensitiveGrant, $narrator);
    $this->withCookie(SensitiveGrant::COOKIE, $second->plain)
        ->post("/n/{$espace->plain}/stories/{$story->getKey()}/qr");

    // Le **même** code : un nouveau condamnerait les exemplaires déjà chez la
    // famille.
    $this->get("/q/{$code}")->assertOk();
});

it('refuse la révocation depuis l’espace d’un autre narrateur', function (): void {
    [$story] = histoireAvecQr();
    $ailleurs = Narrator::factory()->create(['is_primary' => true]);

    $espace = app(TokenService::class)->issue(TokenType::NarratorSpace, $ailleurs, ['manage']);
    $grant = app(TokenService::class)->issue(TokenType::SensitiveGrant, $ailleurs);

    $this->withCookie(SensitiveGrant::COOKIE, $grant->plain)
        ->delete("/n/{$espace->plain}/stories/{$story->getKey()}/qr")
        ->assertNotFound();

    expect(AccessToken::query()->where('type', TokenType::Qr->value)->firstOrFail()->revoked_at)
        ->toBeNull();
});
