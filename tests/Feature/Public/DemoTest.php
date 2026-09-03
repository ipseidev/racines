<?php

declare(strict_types=1);

use Inertia\Testing\AssertableInertia;

/**
 * L'essai en soixante secondes.
 *
 * Ce que la page ne fait pas est son intérêt : rien ne part. Le test bout en
 * bout vérifie l'absence de requête vers `/recordings` ; ici on vérifie que le
 * serveur ne lui donne aucun moyen d'en faire une — ni jeton, ni URL d'envoi.
 */
it('rend la démonstration avec ses limites', function (): void {
    $this->get('/essai')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('public/Demo')
            ->where('limits.demoSeconds', 60)
            ->has('limits.segmentMilliseconds')
            ->has('limits.acceptedMimes'),
        );
});

it('ne donne à la démonstration aucun moyen d’envoyer quoi que ce soit', function (): void {
    $this->get('/essai')->assertInertia(fn (AssertableInertia $page) => $page
        ->missing('token')
        ->missing('uploadUrl')
        ->missing('storyRef'),
    );
});

it('s’ouvre sans compte', function (): void {
    // Une démonstration derrière une connexion ne démontre rien.
    $this->get('/essai')->assertOk();
});
