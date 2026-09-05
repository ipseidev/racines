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

/*
 * L'essai refait (T-151).
 *
 * Il ne faisait que réécouter. Il donne maintenant l'écran du narrateur —
 * question, grand bouton, vu-mètre — puis montre ce que devient une voix. Rien
 * ne part toujours : les deux tests plus haut le gardent.
 */
it('pose la question que la page d’accueil met en avant', function (): void {
    $public = require base_path('lang/fr/public.php');

    // Le site raconte une seule histoire : la carte du héros pose cette
    // question, l'extrait audio y répond, la relecture montre ces mots-là.
    // L'essai pose la même — sinon l'exemple montré au bout ne répond plus à
    // la question qu'on vient de poser au visiteur.
    expect($public['demo']['question'])
        ->toBe($public['landing']['hero']['card']['question']);
});

it('montre au bout de l’essai ce que devient une voix', function (): void {
    $public = require base_path('lang/fr/public.php');

    // L'exemple d'Odette, et la page dit pourquoi ce n'est pas la voix du
    // visiteur : nous ne l'avons pas entendue. La promesse « rien ne part »
    // impose l'exemple, et l'explication rend l'exemple honnête.
    expect($public['demo']['result_body'])->toContain('Odette')
        ->and($public['demo']['result_body'])->toContain('n’a pas quitté')
        ->and($public['landing']['proof']['sample_verbatim'])->not->toBe('')
        ->and($public['landing']['proof']['sample_fluide'])->not->toBe('');

    // Le catalogue public voyage en entier avec la page : l'essai lit les
    // textes d'exemple de la page d'accueil sans qu'on les recopie.
    $this->get('/essai')->assertInertia(fn (AssertableInertia $page) => $page
        ->has('i18n.public.landing.proof.sample_verbatim')
        ->has('i18n.public.demo.result_body'),
    );
});

it('ne fait jamais tomber un compte à rebours', function (): void {
    $demo = (require base_path('lang/fr/public.php'))['demo'];

    // PRD US-06 : du temps écoulé, jamais des secondes qui fondent. L'essai
    // affichait « Il reste :seconds secondes » — voir le compte tomber coupe
    // la parole de qui cherche ses mots, et c'est exactement ce qu'on demande
    // à quelqu'un ici.
    expect($demo['elapsed'])->toContain(':time');

    foreach ($demo as $key => $string) {
        expect($string)->not->toContain('Il reste', "« {$key} » fait un compte à rebours.");
    }
});
