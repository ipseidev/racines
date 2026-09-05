<?php

declare(strict_types=1);

use App\Support\Links;
use Database\Seeders\E2ELinksSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Sur quel hôte les routes à jeton répondent.
 *
 * En production, le domaine court est contraignant : c'est lui qu'on annonce
 * dès l'invitation, et un lien servi ailleurs affaiblirait la seule défense
 * anti-hameçonnage du produit (doc 04 §9).
 *
 * En développement, aucune contrainte — et ce n'est pas un relâchement, c'est
 * ce qui rend les vérifications jouables. Trois blocs se vérifient sur un
 * appareil qui n'est pas la machine de développement : le spike navigateur
 * (04), l'écoute famille sur un vrai téléphone (08), la photo HEIC (12). Sur
 * chacun, l'adresse vue par l'appareil — l'IP du réseau local, un tunnel — ne
 * peut pas être celle du domaine court, et un lien à jeton y recevait un
 * **404** pendant que la page d'accueil répondait 200 (T-156). Pire, aligner
 * l'environnement sur l'appareil cassait alors la suite bout en bout, qui
 * attaque `localhost`. Les deux doivent tenir en même temps.
 */
it('ne contraint pas le domaine hors production', function (): void {
    expect(Links::routeDomain())->toBeNull();
});

it('sert un lien d’écoute sur un hôte qui n’est pas le domaine court', function (): void {
    $this->seed(E2ELinksSeeder::class);

    $token = E2ELinksSeeder::token('listen');

    expect(config('brand.links_domain'))->not->toBe('10.0.0.7');

    $this->get("http://10.0.0.7/l/{$token}")->assertOk();
});

it('sert aussi ce lien sur le domaine court', function (): void {
    $this->seed(E2ELinksSeeder::class);

    $token = E2ELinksSeeder::token('listen');
    $domain = (string) config('brand.links_domain');

    $this->get("http://{$domain}/l/{$token}")->assertOk();
});
