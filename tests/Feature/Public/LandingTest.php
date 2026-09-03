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

    // L'ordre est celui du vécu : le lien arrive, on parle, le texte est relu
    // et validé **par le narrateur**, la famille écoute. Inverser les deux
    // dernières décrirait un produit où la famille lit avant l'accord.
    //
    // Titre et corps sont lus ensemble : la répartition du sens entre les
    // deux est une question de rédaction, pas de contrat.
    $step = fn (string $key): string => $how[$key]['title'].' '.$how[$key]['body'];

    expect($step('one'))->toContain('lien')
        ->and($step('two'))->toContain('parle')
        ->and($step('three'))->toContain('relit')
        ->and($step('four'))->toContain('écoute');
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
