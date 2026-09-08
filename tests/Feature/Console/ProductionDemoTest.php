<?php

declare(strict_types=1);

use App\Actions\AcceptInvitation;
use App\Enums\Channel;
use App\Enums\OrderStatus;
use App\Enums\ProjectStatus;
use App\Enums\TokenType;
use App\Enums\UserRole;
use App\Models\AccessToken;
use App\Models\Cohort;
use App\Models\ConsentText;
use App\Models\FamilyMember;
use App\Models\Narrator;
use App\Models\Order;
use App\Models\Project;
use App\Models\Story;
use App\Models\User;
use App\Settings\PilotSettings;
use App\States\Story\Proposed;
use Database\Seeders\QuestionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

/**
 * Le décor de production, celui qui **tourne** en production.
 *
 * Toute la famille `demo:*` refuse cet environnement, et ces tests vérifient
 * l'inverse : que ce décor-ci passe par le vrai chemin d'achat, qu'il ne fait
 * bouger aucun argent, qu'il sort des lectures par cohorte, et qu'il ne
 * s'installe pas sur un compte du personnel.
 */
beforeEach(function (): void {
    Notification::fake();
});

it('fabrique un achat complet par le chemin du webhook', function (): void {
    $this->artisan('prod:demo --force --email=moi@exemple.fr --proches=2')
        ->assertSuccessful();

    $order = Order::query()->sole();
    $project = $order->project;

    expect($order->status)->toBe(OrderStatus::Paid)
        ->and($order->paid_at)->not->toBeNull()
        // Le délai de rétractation est stocké, comme pour un vrai achat.
        ->and($order->withdrawal_deadline_at)->not->toBeNull()
        ->and($order->items)->toHaveCount(1)
        ->and($project)->toBeInstanceOf(Project::class)
        // En attente d'acceptation, jamais actif : rien ne part avant que le
        // narrateur ait dit oui (invariant du bloc 10).
        ->and($project?->status)->toBe(ProjectStatus::AwaitingAcceptance)
        ->and($project?->gift_message)->not->toBeNull();
});

it('ne fait bouger aucun argent et reste reconnaissable', function (): void {
    $this->artisan('prod:demo --force --email=moi@exemple.fr')->assertSuccessful();

    $order = Order::query()->sole();

    // Le préfixe est ce qui retrouve le décor, et un identifiant Stripe
    // commence par `cs_` : aucune commande réelle ne peut y répondre.
    expect($order->stripe_checkout_session_id)->toStartWith('demo_')
        // Nul et pas inventé : un remboursement sur un paiement fantôme
        // échouerait sur un « No such payment_intent » (T-171).
        ->and($order->stripe_payment_intent_id)->toBeNull()
        ->and($order->refunded_cents)->toBe(0)
        ->and($order->total_cents)->toBe(app(PilotSettings::class)->pilot_price_cents);
});

it('sort le projet de toutes les lectures par cohorte', function (): void {
    $cohorte = Cohort::factory()->create();

    $settings = app(PilotSettings::class);
    $settings->cohort_id = $cohorte->getKey();
    $settings->save();

    $this->artisan('prod:demo --force --email=moi@exemple.fr')->assertSuccessful();

    // `FulfillOrder` range un achat dans la cohorte en cours ; y laisser le
    // décor décalerait H0 et H1 de cette cohorte — les chiffres du Gate.
    expect(Order::query()->sole()->project?->cohort_id)->toBeNull();
});

it('installe le narrateur sur le numéro donné, joignable des deux façons', function (): void {
    $this->artisan('prod:demo --force --email=moi@exemple.fr --telephone=0612345678 --prenom=Suzanne')
        ->assertSuccessful();

    $narrator = Narrator::query()->sole();

    // Tapé comme on le tape, rangé au format international.
    expect($narrator->phone_e164)->toBe('+33612345678')
        ->and($narrator->first_name)->toBe('Suzanne')
        ->and($narrator->preferred_channel)->toBe(Channel::Sms)
        // Le courriel est là **en secours** : si le SMS ne part pas,
        // basculer le canal reprend le parcours sans refabriquer le décor.
        ->and($narrator->email)->toBe('moi+narratrice@exemple.fr')
        ->and($narrator->is_primary)->toBeTrue();
});

it('invite les proches sur des alias de la même boîte', function (): void {
    $this->artisan('prod:demo --force --email=Moi+autre@Exemple.fr --proches=2')
        ->assertSuccessful();

    $adresses = FamilyMember::query()->pluck('email')->all();

    // Un alias sur un alias empilerait les `+` : on repart du local.
    // L'acheteuse a sa fiche d'écoute, posée par `FulfillOrder`.
    expect($adresses)->toContain('moi+demo@exemple.fr')
        ->and($adresses)->toContain('moi+camille@exemple.fr')
        ->and($adresses)->toContain('moi+julien@exemple.fr');

    // Un jeton d'écoute par proche, jamais un lien famille commun.
    expect(AccessToken::query()->where('type', TokenType::ListenProject->value)->count())->toBe(2);
});

it('refuse de s’installer sur un compte du personnel', function (): void {
    User::factory()->create([
        'email' => 'support+demo@exemple.fr',
        'role' => UserRole::Support,
    ]);

    $this->artisan('prod:demo --force --email=support@exemple.fr')->assertFailed();

    expect(Order::query()->count())->toBe(0);
});

it('refuse un numéro qui n’est pas un numéro', function (): void {
    $this->artisan('prod:demo --force --email=moi@exemple.fr --telephone=bonjour')->assertFailed();

    expect(Project::query()->count())->toBe(0);
});

it('refuse un canal qui n’est pas un chemin d’envoi', function (): void {
    // `both` enverrait deux fois, `phone_operator` désigne un humain.
    $this->artisan('prod:demo --force --email=moi@exemple.fr --canal=both')->assertFailed();

    expect(Project::query()->count())->toBe(0);
});

it('efface le décor précédent avant d’en fabriquer un neuf', function (): void {
    $this->artisan('prod:demo --force --email=moi@exemple.fr')->assertSuccessful();
    $premier = Order::query()->sole()->project_id;

    $this->artisan('prod:demo --force --email=moi@exemple.fr')->assertSuccessful();

    // L'opt-in est définitif : un tunnel se rejoue sur un projet neuf, et le
    // précédent ne doit pas rester à peser sur les mesures.
    expect(Project::query()->whereKey($premier)->value('erased_at'))->not->toBeNull()
        ->and(Order::query()->count())->toBe(1)
        ->and(Order::query()->sole()->project_id)->not->toBe($premier);
});

it('la purge efface les projets et supprime les commandes jamais payées', function (): void {
    $this->artisan('prod:demo --force --email=moi@exemple.fr')->assertSuccessful();
    $project = Order::query()->sole()->project_id;

    $this->artisan('prod:demo --purge --force')->assertSuccessful();

    // `EraseProject` garde les commandes pour la comptabilité ; une commande
    // jamais payée n'a rien à y faire.
    expect(Order::query()->count())->toBe(0)
        ->and(Project::query()->whereKey($project)->value('erased_at'))->not->toBeNull()
        ->and(Narrator::query()->sole()->phone_e164)->toBeNull();
});

it('la purge ne touche pas une commande réelle', function (): void {
    $order = Order::factory()->create(['stripe_checkout_session_id' => 'cs_test_reelle']);

    $this->artisan('prod:demo --purge --force')->assertSuccessful();

    expect(Order::query()->whereKey($order->getKey())->exists())->toBeTrue();
});

it('la question refuse tant que l’annonce n’a pas été acceptée', function (): void {
    $this->artisan('prod:demo --force --email=moi@exemple.fr')->assertSuccessful();

    $this->artisan('prod:demo --question')->assertFailed();

    expect(Story::query()->count())->toBe(0);
});

it('la question n’attend pas le lendemain, et ne s’empile pas', function (): void {
    $this->seed(QuestionSeeder::class);

    $this->artisan('prod:demo --force --email=moi@exemple.fr')->assertSuccessful();

    $project = Project::query()->sole();
    app(AcceptInvitation::class)->handle($project, ['preferred_channel' => Channel::Sms->value]);

    // L'acceptation pose une nuit à dessein ; la commande l'avance.
    expect($project->refresh()->next_prompt_at?->isFuture())->toBeTrue();

    $this->artisan('prod:demo --question')->assertSuccessful();
    $this->artisan('prod:demo --question')->assertSuccessful();

    $story = Story::query()->sole();

    expect($story->state->getValue())->toBe(Proposed::$name)
        ->and($story->project_id)->toBe($project->getKey());
});

/*
 * Les deux pièges heurtés en production le 8 septembre.
 *
 * Le premier : la commande a levé sur un texte de consentement manquant
 * **après** avoir créé le compte et tiré son mot de passe — un compte
 * inaccessible, et rien pour le dire. Ce qui est tiré une fois s'imprime
 * avant tout ce qui peut lever.
 *
 * Le second : `--motdepasse` modifiait la fabrication, donc la seule façon de
 * retrouver l'accès était de relancer `prod:demo`, qui efface le décor
 * précédent. Récupérer le moyen de regarder le parcours l'emportait.
 */
it('imprime les identifiants avant tout ce qui peut lever', function (): void {
    // Le décor sans texte de consentement : exactement l'état de la production
    // ce matin-là, où `FulfillOrder` lève dans sa transaction.
    ConsentText::query()->delete();

    $this->artisan('prod:demo --force --email=moi@exemple.fr')
        ->expectsOutputToContain('moi+demo@exemple.fr')
        // Le message brut nomme la valeur en cause ; la trace Symfony ne
        // disait ni ce qui est cassé pour un client, ni quoi taper.
        ->expectsOutputToContain('early_service_start')
        ->expectsOutputToContain('prod:check')
        ->assertFailed();

    // Le compte existe, et son mot de passe a été montré : la commande a
    // échoué, mais elle n'a pas laissé un compte muet derrière elle.
    expect(User::query()->where('email', 'moi+demo@exemple.fr')->exists())->toBeTrue()
        // Transactionnel : pas de projet à moitié construit derrière.
        ->and(Project::query()->count())->toBe(0)
        ->and(Order::query()->count())->toBe(0);
});

it('réémet un mot de passe sans toucher au décor', function (): void {
    $this->artisan('prod:demo --force --email=moi@exemple.fr')->assertSuccessful();

    $project = Project::query()->sole()->getKey();
    $before = User::query()->where('email', 'moi+demo@exemple.fr')->sole()->password;

    $this->artisan('prod:demo --motdepasse')
        ->expectsOutputToContain('moi+demo@exemple.fr')
        ->expectsOutputToContain('Le décor n’a pas bougé')
        ->assertSuccessful();

    expect(User::query()->where('email', 'moi+demo@exemple.fr')->sole()->password)->not->toBe($before)
        // Le décor est intact : même projet, une seule commande, rien d'effacé.
        ->and(Project::query()->sole()->getKey())->toBe($project)
        ->and(Project::query()->sole()->erased_at)->toBeNull()
        ->and(Order::query()->count())->toBe(1);
});

it('refuse de réémettre quand il n’y a pas de décor', function (): void {
    $this->artisan('prod:demo --motdepasse')->assertFailed();
});
