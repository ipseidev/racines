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
it('pose à l’acheteur une question sur ses parents, et non celle du héros', function (): void {
    $public = require base_path('lang/fr/public.php');

    // La carte du héros garde l'odeur d'enfance, et elle a ses raisons : c'est
    // une question de difficulté 1, celle qu'on envoie en premier à une
    // personne de quatre-vingts ans, et l'extrait audio y répond.
    //
    // L'essai ne s'adresse pas à elle. Celui qui l'ouvre a quarante-cinq ans,
    // il vient voir si sa mère saurait s'en servir, et il repart s'il n'a rien
    // senti. On lui pose donc la question qui parle de ses parents à lui.
    expect($public['demo']['question'])
        ->not->toBe($public['landing']['hero']['card']['question'])
        ->and($public['demo']['question'])->toContain('père')
        ->and($public['demo']['question'])->toContain('mère');
});

it('pose une vraie question du corpus, et non une phrase écrite pour la vitrine', function (): void {
    $public = require base_path('lang/fr/public.php');
    $corpus = (string) file_get_contents(base_path('docs/roadmap/annexes/A_corpus_questions_v1.md'));

    // « Répondez à une vraie question de la semaine », promet la page. Alors
    // c'en est une, mot pour mot : annexe A, `qualite-pere-mere`. Une question
    // écrite pour la vitrine ferait de l'essai une démonstration truquée.
    expect($corpus)->toContain($public['demo']['question']);
});

it('écrit sur l’exemple la question à laquelle il répond', function (): void {
    $public = require base_path('lang/fr/public.php');

    // Les deux questions cohabitent sur la page : celle qu'on vient de poser
    // au visiteur, et celle d'Odette au-dessus de sa réponse. Sans la seconde,
    // la page met dans sa bouche une réponse à une question qu'on ne lui a pas
    // posée.
    expect($public['demo']['result_question_label'])->not->toBe('');

    $this->get('/essai')->assertInertia(fn (AssertableInertia $page) => $page
        ->has('i18n.public.demo.result_question_label')
        ->has('i18n.public.landing.hero.card.question'),
    );
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
