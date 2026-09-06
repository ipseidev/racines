<?php

declare(strict_types=1);

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

/**
 * L'espace, pour quelqu'un qui n'a pas de projet.
 *
 * Le tableau de bord savait le dire ; les cinq autres pages répondaient par
 * la page d'erreur brute de Laravel — une trace technique en anglais, servie
 * à quelqu'un qui vient de cliquer sur un onglet de **son** espace (T-199).
 *
 * Le cas n'est pas rare : un compte créé à la quatrième étape du tunnel puis
 * abandonné, un webhook Stripe en retard, ou simplement un onglet cliqué
 * pendant que la commande se règle. Aucun de ces gens n'a rien fait de mal.
 */
function sansProjet(): User
{
    $user = User::factory()->create();
    $user->markEmailAsVerified();

    return $user;
}

it('rend la même page d’accueil sur tous les onglets de l’espace', function (string $url): void {
    // 200, et non 404 : la route existe, la personne y a droit, il n'y a
    // simplement rien encore. Un 404 dirait le contraire des trois.
    $this->actingAs(sansProjet())
        ->get($url)
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('initiator/NoProject'));
})->with([
    '/espace',
    '/espace/questions',
    '/espace/proches',
    '/espace/reglages',
    '/espace/livre',
]);

it('ne fuit rien du projet de quelqu’un d’autre', function (): void {
    $autre = User::factory()->create();
    $autre->markEmailAsVerified();
    Project::factory()->create(['owner_user_id' => $autre->id]);

    $this->actingAs(sansProjet())
        ->get('/espace/questions')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('initiator/NoProject')
            ->missing('queue'));
});
