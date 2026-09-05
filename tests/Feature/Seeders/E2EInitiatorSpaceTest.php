<?php

declare(strict_types=1);

use App\Actions\CreateProject;
use App\Enums\Offer;
use App\Enums\OrderStatus;
use App\Enums\Sku;
use App\Models\Order;
use App\Models\Project;
use App\Models\Question;
use App\Models\User;
use App\Settings\PilotSettings;
use Database\Seeders\E2ELinksSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

/**
 * Le décor de l'espace Initiateur·rice doit permettre de jouer le point 5 du
 * checkpoint du bloc 10 **en entier** : « réordonner deux questions, inviter
 * un proche, copier le lien WhatsApp, demander la rétractation ». Sans
 * commande payée dont le délai court encore, la page « Ma commande » est vide
 * et la dernière étape n'existe pas — le checkpoint ne se joue qu'aux trois
 * quarts, et c'est le quart légal qui manque.
 */
function initiatorUser(): User
{
    return User::query()->where('email', E2ELinksSeeder::INITIATOR_EMAIL)->firstOrFail();
}

function initiatorProject(): Project
{
    return Project::query()->where('owner_user_id', initiatorUser()->id)->firstOrFail();
}

it('sème une commande payée, au prix du pilote, encore rétractable', function (): void {
    $this->seed(E2ELinksSeeder::class);

    $order = Order::query()->where('user_id', initiatorUser()->id)->first();
    $price = app(PilotSettings::class)->pilot_price_cents;

    expect($order)->not->toBeNull()
        ->and($order->project_id)->toBe(initiatorProject()->id)
        ->and($order->status)->toBe(OrderStatus::Paid)
        ->and($order->canBeWithdrawn())->toBeTrue()
        ->and($order->total_cents)->toBe($price)
        ->and($order->items->pluck('sku')->all())->toBe([Sku::Pilot])
        ->and($order->items->first()?->unit_cents)->toBe($price);
});

it('ne sème la commande qu’une fois', function (): void {
    $this->seed(E2ELinksSeeder::class);
    $this->seed(E2ELinksSeeder::class);

    expect(Order::query()->where('user_id', initiatorUser()->id)->count())->toBe(1);
});

it('sème l’espace même quand le décor du banc d’essai existe déjà', function (): void {
    // Sur une base déjà semée, `run()` s'arrêtait à la garde du banc d'essai
    // et l'espace n'était jamais atteint : un décor ajouté après coup ne
    // pouvait rejoindre une base existante que par `migrate:fresh`, qui
    // efface aussi les réglages de la machine.
    $owner = User::factory()->create(['email' => E2ELinksSeeder::OWNER_EMAIL]);
    app(CreateProject::class)->handle($owner, Offer::Pilot, []);

    $this->seed(E2ELinksSeeder::class);

    $project = initiatorProject();

    expect($project->stories()->count())->toBe(2)
        ->and(Order::query()->where('project_id', $project->id)->exists())->toBeTrue();
});

it('complète un décor déjà semé avec la commande qui lui manque', function (): void {
    // Le cas des bases semées avant que la commande n'existe : le projet de
    // Camille est là, sa commande non. Relancer le seeder la pose, sans
    // toucher au reste.
    $this->seed(E2ELinksSeeder::class);
    Order::query()->where('user_id', initiatorUser()->id)->delete();
    $stories = initiatorProject()->stories()->count();

    $this->seed(E2ELinksSeeder::class);

    expect(Order::query()->where('project_id', initiatorProject()->id)->count())->toBe(1)
        ->and(initiatorProject()->stories()->count())->toBe($stories);
});

it('garde les questions du décor hors du corpus proposé aux familles', function (): void {
    // Une question de scénario porte une histoire, rien de plus. Active, elle
    // s'affichait en tête de « Les questions » pour toutes les familles, et
    // `PickNextQuestion` aurait pu la poser pour de vrai.
    $this->seed(E2ELinksSeeder::class);

    expect(Question::query()->where('slug', 'like', 'e2e-%')->exists())->toBeTrue()
        ->and(Question::query()->active()->where('slug', 'like', 'e2e-%')->exists())->toBeFalse();

    $this->actingAs(initiatorUser())
        ->get('/espace/questions')
        ->assertInertia(fn (Assert $page) => $page
            ->component('initiator/Questions')
            ->where('questions', fn (Collection $questions): bool => $questions
                ->pluck('text')
                ->doesntContain('Quel était le métier de votre mère ?')));
});
