<?php

declare(strict_types=1);

use App\Actions\CreateProject;
use App\Enums\Locale;
use App\Enums\Offer;
use App\Enums\TokenType;
use App\Http\Middleware\SetLocale;
use App\Models\Story;
use App\Models\User;
use App\Services\Tokens\TokenService;
use Inertia\Testing\AssertableInertia;

/*
 * Qui décide de la langue d'une page.
 *
 * L'ordre est le cœur du sujet, et chaque cran a une raison :
 *
 *  1. **L'adresse** : une page indexée doit toujours servir la même langue,
 *     sinon Google en indexe une et le visiteur en reçoit une autre.
 *  2. **Le témoin** : le dernier choix explicite de la personne.
 *  3. **Le projet**, sur une page à jeton : la personne qui raconte n'a pas
 *     de compte, et l'en-tête de son téléphone ne dit pas la langue de sa
 *     famille.
 *  4. **Le compte**, sur un appareil neuf.
 *  5. **Le navigateur**, au tout premier passage.
 *  6. Le français.
 */

it('sert la langue que l’adresse annonce, quoi qu’en dise le navigateur', function (): void {
    $this->withHeader('Accept-Language', 'es-ES,es;q=0.9')
        ->get('/it/come-funziona')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->where('locale.current', 'it'));
});

it('sert le français à la racine, sans préfixe', function (): void {
    $this->get('/comment-ca-marche')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->where('locale.current', 'fr'));
});

it('distingue la langue du marché : l’italien de Suisse lit l’italien', function (): void {
    $this->get('/it-ch/come-funziona')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('locale.current', 'it-CH')
            ->where('locale.language', 'it')
            ->where('locale.tag', 'it-CH')
            ->where('locale.currency', 'CHF'));
});

it('retient dans un témoin la langue qu’une adresse a imposée', function (): void {
    // Sans quoi le tunnel, dont les écritures n'ont qu'une adresse, repasserait
    // en français à la première étape validée.
    $this->get('/es/como-funciona')
        ->assertOk()
        ->assertCookie(SetLocale::COOKIE, Locale::Spanish->value);
});

it('ne pose aucun témoin à qui n’a rien choisi', function (): void {
    // Le français par défaut n'est pas un choix : un témoin de plus pour ne
    // rien dire est un témoin de trop.
    $this->get('/comment-ca-marche')
        ->assertOk()
        ->assertCookieMissing(SetLocale::COOKIE);
});

it('suit le témoin sur une page qui n’a qu’une adresse', function (): void {
    $this->withCookie(SetLocale::COOKIE, Locale::Italian->value)
        ->get('/login')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->where('locale.current', 'it'));
});

it('suit la préférence du compte sur un appareil neuf', function (): void {
    $user = User::factory()->create(['locale' => Locale::Spanish]);

    $this->actingAs($user)
        ->get('/settings/profile')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->where('locale.current', 'es'));
});

it('lit le navigateur quand rien d’autre ne parle', function (): void {
    $this->withHeader('Accept-Language', 'it-CH,it;q=0.9,en;q=0.8')
        ->get('/login')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->where('locale.current', 'it-CH'));
});

it('retombe sur le français pour une langue qu’on ne sert pas', function (): void {
    $this->withHeader('Accept-Language', 'ja-JP,ja;q=0.9')
        ->get('/login')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->where('locale.current', 'fr'));
});

it('parle au narrateur la langue du projet, pas celle de son téléphone', function (): void {
    $story = Story::factory()->proposed()->create();
    $story->project->forceFill(['locale' => Locale::Italian])->save();

    $token = app(TokenService::class)->issue(TokenType::Record, $story)->plain;

    $this->withHeader('Accept-Language', 'en-US,en;q=0.9')
        ->get("/r/{$token}")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->where('locale.current', 'it'));
});

it('laisse le choix explicite du narrateur l’emporter sur le projet', function (): void {
    $story = Story::factory()->proposed()->create();
    $story->project->forceFill(['locale' => Locale::Italian])->save();

    $token = app(TokenService::class)->issue(TokenType::Record, $story)->plain;

    $this->withCookie(SetLocale::COOKIE, Locale::Spanish->value)
        ->get("/r/{$token}")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->where('locale.current', 'es'));
});

it('change de langue, retient le choix et le porte au compte', function (): void {
    $user = User::factory()->create(['locale' => Locale::French]);

    $this->actingAs($user)
        ->from('/settings/profile')
        ->post('/langue', ['locale' => Locale::Italian->value])
        ->assertRedirect('/settings/profile')
        ->assertCookie(SetLocale::COOKIE, Locale::Italian->value);

    expect($user->refresh()->locale)->toBe(Locale::Italian);
});

it('refuse une langue qu’on ne sert pas', function (): void {
    $this->from('/login')
        ->post('/langue', ['locale' => 'de'])
        ->assertSessionHasErrors('locale');
});

it('donne au projet la langue du tunnel où il a été offert', function (): void {
    $buyer = User::factory()->create(['locale' => Locale::Italian]);
    $project = app(CreateProject::class)->handle($buyer, Offer::Pilot);

    expect($project->locale)->toBe(Locale::Italian);
});

it('laisse changer la langue du projet depuis les réglages', function (): void {
    $owner = User::factory()->create();
    // `CreateProject` inscrit déjà l'Initiateur·rice comme membre.
    $project = app(CreateProject::class)->handle($owner, Offer::Pilot);

    $this->actingAs($owner)
        ->from('/espace/reglages')
        ->post('/espace/reglages/langue', ['locale' => Locale::Spanish->value])
        ->assertRedirect('/espace/reglages');

    expect($project->refresh()->locale)->toBe(Locale::Spanish);
});
