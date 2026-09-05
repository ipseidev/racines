<?php

declare(strict_types=1);

use App\Features\PreventePrice;
use App\Settings\PilotSettings;
use Inertia\Testing\AssertableInertia;

/**
 * La page d'accueil.
 *
 * Deux choses s'y jouent, et une seule est visible. La visible : dire ce que
 * fait le produit dans l'ordre du dossier 01 §4. L'invisible, et la plus
 * importante : n'y promettre que ce qu'on peut tenir. Les engagements R-10
 * sont en formulation **canonique** — les mêmes mots ici, dans les CGV et
 * dans les courriels — et le vocabulaire interdit R-11 n'y a pas sa place.
 */
function landingStrings(): array
{
    $public = require base_path('lang/fr/public.php');

    return $public['landing'];
}

it('annonce la promesse et les quatre étapes', function (): void {
    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('public/Landing')
            ->where('mode', 'pilot')
            ->where('price', app(PilotSettings::class)->pilot_price_cents)
            // Le catalogue voyage avec la page : c'est de là que viennent
            // tous les textes, et son absence rendrait la page muette.
            ->where('i18n.public.landing.promise', landingStrings()['promise'])
            ->has('i18n.public.landing.how.one.title')
            ->has('i18n.public.landing.how.two.title')
            ->has('i18n.public.landing.how.three.title')
            ->has('i18n.public.landing.how.four.title'),
        );
});

it('décrit les quatre étapes dans l’ordre du parcours réel', function (): void {
    $how = landingStrings()['how'];

    // L'ordre est celui du vécu, dans la structure du leader (T-134) : la
    // famille choisit les questions, le lien arrive et la personne parle, le
    // texte est relu et validé **par le narrateur**, la famille écoute.
    // Inverser les deux dernières décrirait un produit où la famille lit
    // avant l'accord.
    //
    // Titre et corps sont lus ensemble : la répartition du sens entre les
    // deux est une question de rédaction, pas de contrat.
    $step = fn (string $key): string => $how[$key]['title'].' '.$how[$key]['body'];

    expect($step('one'))->toContain('questions')
        ->and($step('two'))->toContain('lien')
        ->and($step('two'))->toContain('parle')
        ->and($step('three'))->toContain('relit')
        ->and($step('four'))->toContain('écoute');
});

it('nomme le QR code sous un titre qui ne fait que l’évoquer', function (): void {
    $how = landingStrings()['how'];

    // Le titre promet un scan sans dire de quoi. Sans le chapeau qui nomme le
    // QR code et ce qu'il rejoue, la section s'ouvre sur une devinette.
    expect($how['headline'])->toContain('scan')
        ->and($how['lede'])->toContain('QR code')
        ->and($how['lede'])->toContain('voix');
});

it('ne met dans la bande de confiance que ce que la page tient ailleurs', function (): void {
    $strings = landingStrings();
    $trust = $strings['trust'];

    // Trois lignes sans démonstration : chacune doit être reprise, en détail,
    // là où le visiteur ira vérifier. Une bande de confiance qui affirme seule
    // est une promesse en l'air.
    expect($trust['no_app'])->toContain('application')
        ->and($strings['hero']['checks']['no_app'])->toContain('Aucune application')
        ->and($trust['one_payment'])->toContain('abonnement')
        ->and($strings['faq']['subscription']['a'])->toContain('Un seul paiement')
        ->and($trust['refund'])->toContain('rembours')
        ->and($strings['faq']['refund']['a'])->toContain('rembours');
});

it('affiche les sept engagements, et pas seulement au catalogue', function (): void {
    $commitments = landingStrings()['commitments'];

    // Ils ont pris le 5 septembre 2026 la place des deux tuiles d'options,
    // parties dans le tunnel. Jusque-là, aucune page ne les affichait : le
    // dossier demande qu'ils soient publiés, pas seulement écrits.
    $seven = ['validation', 'withdrawal', 'source_audio', 'ai_arranges',
        'no_cloning', 'no_training', 'eu_hosting'];

    foreach ($seven as $key) {
        expect($commitments[$key] ?? '')->not->toBe('');
    }

    $this->get('/')->assertInertia(fn (AssertableInertia $page) => $page
        ->has('i18n.public.landing.commitments.eu_hosting')
        ->has('i18n.public.landing.commitments.no_training')
        // Et les options ne sont plus sur la page d'accueil : elles se
        // choisissent dans le tunnel, quand l'achat est décidé.
        ->missing('i18n.public.landing.tiles')
        ->missing('phoneOptionPrice')
        ->missing('extraCopyPrice'),
    );
});

it('emploie la formulation canonique des engagements', function (): void {
    $commitments = landingStrings()['commitments'];

    expect($commitments['validation'])->toContain('explicite, jamais tacite')
        ->and($commitments['no_cloning'])->toContain('Pas de clonage vocal')
        ->and($commitments['ai_arranges'])->toContain('L’IA range, elle n’invente pas')
        ->and($commitments['source_audio'])->toContain('jamais remplacé')
        ->and($commitments['no_training'])->toContain('entraîner un modèle')
        ->and($commitments['eu_hosting'])->toContain('Union européenne');
});

it('n’emploie aucun mot interdit sur la page d’accueil', function (): void {
    // Critère de sortie du bloc. Le test général couvre tous les fichiers de
    // langue ; celui-ci nomme la page d'accueil, pour que l'échec dise où
    // regarder.
    // `array_walk_recursive` prend son tableau **par référence** : lui passer
    // le retour d'une fonction directement est une erreur d'exécution.
    $strings = landingStrings();
    $flat = [];

    array_walk_recursive($strings, function (mixed $value) use (&$flat): void {
        if (is_string($value)) {
            $flat[] = $value;
        }
    });

    foreach (['pour toujours', 'illimité', 'garanti à vie', 'validation tacite',
        'validation automatique', 'appartiennent à la famille'] as $forbidden) {
        foreach ($flat as $string) {
            expect(mb_stripos($string, $forbidden))->toBeFalse(
                "« {$forbidden} » apparaît sur la page d’accueil : « {$string} »",
            );
        }
    }
});

it('répond à la question de la fin de service', function (): void {
    $answer = landingStrings()['faq']['shutdown']['a'];

    // Doc 04 §7 : préavis, remise des archives, remboursement de ce qui n'a
    // pas été livré. Et surtout, ce qu'on ne promet pas.
    expect($answer)->toContain('trois mois')
        ->and($answer)->toContain('rembours')
        ->and($answer)->toContain('sans nous');
});

it('affiche le prix du mode pilote', function (): void {
    app(PilotSettings::class)->fill(['mode' => 'pilot', 'pilot_price_cents' => 4_900])->save();

    $this->get('/')->assertInertia(fn (AssertableInertia $page) => $page
        ->where('mode', 'pilot')
        ->where('price', 4_900),
    );
});

it('affiche l’une des deux variantes de prévente et pose le cookie', function (): void {
    $settings = app(PilotSettings::class);
    $settings->fill(['mode' => 'prevente', 'prevente_prices_cents' => [9_900, 12_900]])->save();

    $response = $this->get('/');

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->where('mode', 'prevente')
        ->where('price', fn (mixed $price) => in_array($price, [9_900, 12_900], true)),
    );

    // L'affectation doit précéder l'achat : un prix qui change entre la
    // découverte et le paiement fait fuir.
    $response->assertCookie(PreventePrice::COOKIE);
});

it('partage le mode et les prix avec toutes les pages', function (): void {
    // Le mode décide de ce que plusieurs pages annoncent. Les passer page par
    // page finirait par produire deux prix sur deux écrans du même parcours.
    $this->get('/')->assertInertia(fn (AssertableInertia $page) => $page
        ->has('pilot.mode')
        ->has('pilot.pilotPriceCents')
        ->has('pilot.phoneOptionPriceCents')
        ->where('pilot.legalValidated', false),
    );
});

/*
 * L'extrait écoutable de la carte du héros (T-149).
 *
 * La page promet « sa voix se réécoute à chaque page » dans son premier
 * paragraphe. Tant que la carte n'était qu'une frise animée, elle l'affirmait
 * sans le prouver ; l'extrait le prouve. Ce qui se teste ici n'est pas le son
 * — c'est ce qui l'entoure : sa présence, sa mention, et son texte.
 */
it('rend l’extrait du héros écoutable', function (): void {
    // Le fichier fait partie du dépôt : sans lui, la carte se tait, et le
    // test suivant explique ce qu'elle fait alors.
    expect(public_path((string) config('product.landing.hero_sample')))->toBeFile();

    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('heroSample.src', '/audio/landing/hero.mp3')
            ->where('heroSample.disclosed', false)
            ->has('i18n.public.landing.hero.card.transcript'),
        );
});

it('se tait plutôt que de proposer un extrait absent', function (): void {
    config(['product.landing.hero_sample' => 'audio/landing/pas-encore-la.mp3']);

    // Un bouton « Écouter » au-dessus d'un fichier manquant est pire que pas
    // de bouton : la carte reprend sa frise décorative.
    $this->get('/')->assertInertia(fn (AssertableInertia $page) => $page
        ->where('heroSample', null),
    );
});

it('fait entendre en haut de page les mots qu’elle montre plus bas', function (): void {
    $card = landingStrings()['hero']['card'];
    $proof = landingStrings()['proof'];

    // La cohérence de la page tient à ça : on entend Odette dans le héros, et
    // on retrouve ses mots exacts dans « La relecture », en mot à mot puis mis
    // au propre. Le mot à mot est donc un extrait de la transcription — à la
    // casse près, puisqu'il commence en cours de phrase.
    expect(mb_strtolower($card['transcript']))
        ->toContain(mb_strtolower($proof['sample_verbatim']));
});

/*
 * Le bandeau vert sous le héros, changé le 5 septembre 2026.
 *
 * Il portait trois engagements. À cet endroit — juste après la promesse, avant
 * même « qu'est-ce que c'est » — ils se lisaient comme une liste de choses à
 * surveiller, et faisaient peur : ils laissaient croire qu'on demandait
 * beaucoup à une personne âgée. Le bandeau dit maintenant pourquoi on offre.
 */
it('donne trois raisons d’offrir, en nos propres mots', function (): void {
    $promises = landingStrings()['promises'];

    // Trois phrases courtes : le bandeau les compose en grande capitale de
    // titrage, et un paragraphe y casserait la mise en page.
    foreach (['ask', 'voice', 'weekly'] as $key) {
        expect(mb_strlen($promises[$key]))->toBeLessThanOrEqual(60);
    }

    // Et jamais de citation : nous n'avons ni presse ni avis de familles, et
    // une phrase entre guillemets sur un fond vert avec des étoiles se lit
    // comme un témoignage. Le jour où de vrais avis existeront, ils viendront
    // avec un nom.
    foreach (['ask', 'voice', 'weekly'] as $key) {
        expect($promises[$key])->not->toContain('«')
            ->and($promises[$key])->not->toContain('”');
    }
});

it('dit toujours qui décide, sans le bandeau pour le rappeler', function (): void {
    $strings = landingStrings();

    // Ce que les engagements disaient n'a pas quitté la page avec eux : les
    // repères du héros et les questions fréquentes le portent, là où on
    // cherche une réponse plutôt qu'une raison d'offrir.
    expect($strings['hero']['checks']['she_decides'])->toContain('décide')
        ->and($strings['hero']['checks']['kept_words'])->toContain('jamais réécrits')
        ->and($strings['faq']['privacy']['a'])->toContain('autorisés');

    // La formulation canonique des engagements reste au catalogue, pour les
    // pages qui les portent (elle est vérifiée plus haut).
    expect($strings['commitments']['validation'])->toContain('explicite, jamais tacite');
});
