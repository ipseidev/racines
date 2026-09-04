<?php

declare(strict_types=1);

use App\Filament\Pages\ManagePilot;
use App\Http\Controllers\Public\WelcomeOfferController;
use App\Models\Lead;
use App\Models\OutboundMessage;
use App\Models\User;
use App\Notifications\WelcomeOfferNotification;
use App\Settings\PilotSettings;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia;
use Livewire\Livewire;

/**
 * La fenêtre de bienvenue (T-141) : une adresse contre un code de réduction.
 *
 * Trois choses comptent plus que le reste. Une adresse, un code : redemander
 * ne fabrique pas un second code. Le code part par courriel et jamais dans la
 * réponse : c'est ce qui fait qu'une adresse laissée existe. Et la demande de
 * nouvelles est séparée, décochée, jamais requise, comme la case marketing du
 * tunnel.
 */
function claim(array $overrides = []): TestResponse
{
    return test()->from('/')->post('/offre-de-bienvenue', array_merge([
        'email' => 'camille@exemple.test',
    ], $overrides));
}

it('propose l’offre sur la page d’accueil, avec son montant', function (): void {
    $this->get('/')->assertInertia(fn (AssertableInertia $page) => $page
        ->where('welcomeOffer.enabled', true)
        ->where('welcomeOffer.discountPercent', 10),
    );
});

it('ne la propose plus quand le réglage la coupe, ni quand le pourcentage est nul', function (): void {
    app(PilotSettings::class)->fill(['welcome_offer_enabled' => false])->save();

    $this->get('/')->assertInertia(fn (AssertableInertia $page) => $page
        ->where('welcomeOffer.enabled', false),
    );

    app(PilotSettings::class)->fill(['welcome_offer_enabled' => true, 'welcome_offer_discount_percent' => 0])->save();

    $this->get('/')->assertInertia(fn (AssertableInertia $page) => $page
        ->where('welcomeOffer.enabled', false),
    );
});

it('ne la propose pas à qui a déjà son code', function (): void {
    // Proposer deux fois la même réduction à la même personne ressemble à
    // une relance.
    $this->withCookie(WelcomeOfferController::COOKIE, 'ABCD-EFGH')
        ->get('/')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('welcomeOffer.enabled', false),
        );
});

it('crée un contact, lui envoie son code, et le pose en cookie', function (): void {
    Notification::fake();

    $response = claim();

    $response->assertRedirect('/')->assertSessionHasNoErrors();

    $lead = Lead::query()->firstOrFail();

    expect($lead->email)->toBe('camille@exemple.test')
        ->and($lead->email_hash)->toBe(hash('sha256', 'camille@exemple.test'))
        ->and($lead->discount_code)->toMatch('/^[A-Z2-9]{4}-[A-Z2-9]{4}$/')
        // Sans 0, O, 1 ni I : un code se recopie à la main.
        ->and($lead->discount_code)->not->toMatch('/[01IO]/')
        ->and($lead->discount_percent)->toBe(10)
        ->and($lead->codeUsable())->toBeTrue()
        ->and($lead->code_expires_at->toDateString())->toBe(now()->addDays(365)->toDateString())
        ->and($lead->news_opted_in_at)->toBeNull();

    Notification::assertSentTo($lead, WelcomeOfferNotification::class);

    // Le code voyage en cookie, pour s'appliquer tout seul au tunnel.
    $response->assertCookie(WelcomeOfferController::COOKIE, $lead->discount_code);
});

it('écrit un courriel qui donne le code, sa valeur et sa fin', function (): void {
    $lead = Lead::factory()->create(['discount_percent' => 10]);

    $mail = (new WelcomeOfferNotification($lead))->toMail($lead);
    $text = implode(' ', array_map('strval', $mail->introLines)).' '.implode(' ', array_map('strval', $mail->outroLines));

    expect($mail->subject)->toContain("10\u{202F}%")
        ->and($text)->toContain($lead->discount_code)
        ->and($text)->toContain("10\u{202F}%")
        ->and($text)->toContain($lead->code_expires_at->translatedFormat('j F Y'))
        ->and($mail->actionUrl)->toBe(route('checkout.show'))
        // Pas de nouvelles promises à qui ne les a pas demandées.
        ->and($text)->not->toContain('nos nouvelles');
});

it('part vraiment par courriel, avec sa trace de livraison', function (): void {
    claim()->assertSessionHasNoErrors();

    $message = OutboundMessage::query()->where('template', 'welcome_offer')->firstOrFail();

    expect($message->to_masked)->toBe('c••••••@exemple.test')
        ->and($message->project_id)->toBeNull();
});

it('enregistre la demande de nouvelles, datée et versionnée, seulement si la case est cochée', function (): void {
    Notification::fake();

    claim(['news' => true]);

    $lead = Lead::query()->firstOrFail();

    expect($lead->news_opted_in_at)->not->toBeNull()
        ->and($lead->consent_text_version)->not->toBeNull()
        ->and($lead->ip_hash)->not->toBeNull()
        // Jamais l'adresse IP en clair.
        ->and($lead->ip_hash)->not->toBe('127.0.0.1');

    $mail = (new WelcomeOfferNotification($lead))->toMail($lead);
    $text = implode(' ', array_map('strval', $mail->outroLines));

    expect($text)->toContain('nos nouvelles');
});

it('ne fabrique pas un second code pour la même adresse, et la renvoie le sien', function (): void {
    Notification::fake();

    claim();
    claim(['email' => 'Camille@Exemple.test ']);

    expect(Lead::query()->count())->toBe(1);

    Notification::assertSentToTimes(Lead::query()->firstOrFail(), WelcomeOfferNotification::class, 2);
});

it('remplace un code expiré, mais jamais un code qui a servi', function (): void {
    Notification::fake();

    $expired = Lead::factory()->expired()->create(['email' => 'camille@exemple.test']);
    $oldCode = $expired->discount_code;

    claim();

    $expired->refresh();

    expect($expired->discount_code)->not->toBe($oldCode)
        ->and($expired->codeUsable())->toBeTrue();

    Notification::assertSentTo($expired, WelcomeOfferNotification::class);

    $used = Lead::factory()->used()->create(['email' => 'odette@exemple.test']);
    $usedCode = $used->discount_code;

    claim(['email' => 'odette@exemple.test'])->assertSessionHasNoErrors();

    // La réduction de bienvenue vaut une fois par personne : le code reste
    // celui qui a servi, et aucun courriel ne part.
    expect($used->refresh()->discount_code)->toBe($usedCode);
    Notification::assertNotSentTo($used, WelcomeOfferNotification::class);
});

it('refuse une adresse qui n’en est pas une', function (): void {
    claim(['email' => 'pas-une-adresse'])->assertSessionHasErrors('email');

    expect(Lead::query()->count())->toBe(0);
});

it('remercie un robot sans rien garder', function (): void {
    Notification::fake();

    // Le champ que personne ne voit : rempli, c'est un robot. On répond
    // « merci » : un robot qui reçoit une erreur apprend.
    claim([WelcomeOfferController::HONEYPOT => 'https://spam.test'])
        ->assertRedirect('/')
        ->assertSessionHasNoErrors();

    expect(Lead::query()->count())->toBe(0);
    Notification::assertNothingSent();
});

it('refuse poliment quand l’offre est coupée', function (): void {
    app(PilotSettings::class)->fill(['welcome_offer_enabled' => false])->save();

    claim()->assertSessionHasErrors('email');

    expect(Lead::query()->count())->toBe(0);
});

it('dit que l’envoi a échoué, et garde le contact pour la prochaine fois', function (): void {
    Mail::shouldReceive('mailer')->andThrow(new RuntimeException('Resend est tombé.'));

    claim()->assertSessionHasErrors('email');

    // Le code existe : la prochaine tentative renverra le même.
    expect(Lead::query()->count())->toBe(1);
});

it('est borné par adresse et par IP', function (): void {
    $route = Route::getRoutes()->getByName('welcome_offer.claim');

    expect($route?->middleware())->toContain('throttle:welcome-offer');
});

it('se règle depuis l’administration', function (): void {
    $this->actingAs(User::factory()->admin()->withAppAuthentication()->create());

    Livewire::test(ManagePilot::class)
        ->fillForm([
            'welcome_offer_enabled' => false,
            'welcome_offer_discount_percent' => 15,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $settings = app(PilotSettings::class);

    expect($settings->welcome_offer_enabled)->toBeFalse()
        ->and($settings->welcome_offer_discount_percent)->toBe(15);
});
