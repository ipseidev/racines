<?php

declare(strict_types=1);

use App\Filament\Pages\ManageBrand;
use App\Filament\Pages\ManagePilot;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Resources\Resource;

/**
 * Les ressources du back-office : elles s'ouvrent, et elles s'arrêtent aux
 * bonnes portes.
 *
 * Un test par ressource serait un test par formulaire. Ce qui compte ici est
 * plus simple et plus solide : **chaque** liste répond, et **aucune** ne
 * s'ouvre à qui n'a pas la permission. Le jour où quelqu'un ajoute une
 * ressource en oubliant `canAccess()`, ce test le dit — et c'est la faute qui
 * coûterait le plus cher.
 *
 * @return list<class-string<resource>>
 */
function panelResources(): array
{
    return array_values(Filament::getPanel('admin')->getResources());
}

it('déclare les quinze ressources attendues', function (): void {
    expect(panelResources())->toHaveCount(15);
});

it('ouvre chaque liste à qui a la permission', function (): void {
    $admin = User::factory()->admin()->withAppAuthentication()->create();
    $this->actingAs($admin);

    foreach (panelResources() as $resource) {
        $this->get($resource::getUrl('index'))
            ->assertOk();
    }
})->skip(fn (): bool => panelResources() === []);

it('refuse chaque liste à une Initiateur·rice', function (): void {
    // Un client n'entre pas dans le back-office, ressource par ressource.
    $client = User::factory()->withAppAuthentication()->create();
    $this->actingAs($client);

    foreach (panelResources() as $resource) {
        expect($resource::canAccess())->toBeFalse($resource.' s’ouvre à un client');
    }
});

it('n’ouvre les rôles et les prix qu’à l’administration', function (): void {
    // Le support n'a ni les comptes, ni les réglages du pilote : distribuer
    // des rôles et fixer des prix ne sont pas des gestes de support.
    $support = User::factory()->support()->withAppAuthentication()->create();
    $this->actingAs($support);

    expect(UserResource::canAccess())->toBeFalse()
        ->and(ManagePilot::canAccess())->toBeFalse()
        ->and(ManageBrand::canAccess())->toBeFalse();
});

it('n’autorise la création d’aucune ressource', function (): void {
    /*
     * Rien ne se crée à la main dans ce back-office, et c'est un choix. Un
     * projet naît d'une commande payée, une histoire d'une question envoyée,
     * un jeton d'une action du domaine. Créer l'un des trois de côté
     * produirait une ligne qui n'a pas suivi le chemin du produit — donc une
     * ligne dont aucun invariant n'est garanti.
     */
    foreach (panelResources() as $resource) {
        expect($resource::canCreate())->toBeFalse($resource.' permet la création');
    }
});

it('donne un libellé traduit à chaque ressource', function (): void {
    foreach (panelResources() as $resource) {
        $label = $resource::getNavigationLabel();

        expect($label)->not->toStartWith('admin.', $resource.' : libellé non traduit')
            ->and($label)->not->toBe('');
    }
});
