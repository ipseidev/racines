<?php

declare(strict_types=1);

use App\Support\Translations;

/**
 * Ce qui s'affiche dans **tous** les espaces doit être traduit dans tous.
 *
 * Le front ne reçoit que `common` plus le fichier de l'espace courant : les
 * pages narrateur et famille s'ouvrent en 4G sur de vieux téléphones, et leur
 * envoyer le catalogue entier serait payé par la personne qui a le moins de
 * réseau (PRD US-01).
 *
 * La conséquence se paie ailleurs, et elle s'est payée : le bandeau de
 * consentement était rangé dans `public` alors qu'il est monté dans cinq
 * mises en page. Connecté à son espace, on lisait « public.consent.title »
 * et « public.consent.accept » en clair — la clé brute, à la place du texte.
 *
 * Ce test dit la règle : un composant partagé range ses textes dans `common`.
 */
it('sert les textes des composants partagés dans chaque espace', function (string $espace): void {
    $catalogue = Translations::forSpace($espace, 'fr');

    // Le bandeau de consentement : accueil, tunnel, quiz, page témoignage,
    // espace de l'Initiateur·rice.
    expect($catalogue)->toHaveKey('common')
        ->and($catalogue['common'])->toHaveKey('consent')
        ->and($catalogue['common']['consent'])->toHaveKeys([
            'title', 'body', 'accept', 'refuse', 'more', 'manage',
        ]);

    // Le sélecteur de langue, qui vit dans les quatre espaces.
    expect($catalogue['common'])->toHaveKey('locale');
})->with(['public', 'initiator', 'narrator', 'family', 'auth']);

it('n’envoie que le commun et l’espace demandé', function (): void {
    /*
     * La légèreté est la raison d'être du découpage : si ce test se met à
     * échouer parce qu'un troisième fichier est apparu, c'est que quelqu'un a
     * élargi l'envoi — et c'est la page du narrateur qui le paiera.
     */
    expect(array_keys(Translations::forSpace('narrator', 'fr')))
        ->toBe(['common', 'narrator']);
});
