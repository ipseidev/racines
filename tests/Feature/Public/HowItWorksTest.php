<?php

declare(strict_types=1);

use App\Settings\PilotSettings;
use Inertia\Testing\AssertableInertia;

/**
 * « Comment ça marche », la page (T-208).
 *
 * Sa structure est celle de la page du leader ; ses mots sont les nôtres, et
 * ils n'ont le droit de promettre que ce que le dossier tient. Trois choses
 * se vérifient ici : que la page se sert avec ce qu'il lui faut, que les six
 * étapes racontent le parcours dans l'ordre où il se vit, dans ses deux
 * voix, et que le texte respecte les règles de la maison (R-11, pas de tiret
 * long, questions du corpus mot pour mot).
 */
function howItWorksStrings(): array
{
    $public = require base_path('lang/fr/public.php');

    return $public['how_it_works'];
}

/**
 * Les six étapes, dans l'ordre du catalogue : les clés qui portent une voix
 * « gift » et une voix « self ».
 *
 * @return list<string>
 */
function howItWorksStepKeys(): array
{
    $steps = howItWorksStrings()['steps'];

    return array_values(array_filter(
        array_keys($steps),
        fn (string $key): bool => is_array($steps[$key]) && isset($steps[$key]['gift']),
    ));
}

/**
 * @return list<string>
 */
function howItWorksFlat(): array
{
    $strings = howItWorksStrings();
    $flat = [];

    // `array_walk_recursive` prend son tableau **par référence** : une
    // variable, jamais le retour d'une fonction.
    array_walk_recursive($strings, function (mixed $value) use (&$flat): void {
        if (is_string($value)) {
            $flat[] = $value;
        }
    });

    return $flat;
}

it('se sert avec le prix, l’offre de bienvenue et les six étapes', function (): void {
    $this->get('/comment-ca-marche')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('public/HowItWorks')
            ->where('mode', 'pilot')
            ->where('price', app(PilotSettings::class)->pilot_price_cents)
            ->has('welcomeOffer.enabled')
            ->has('welcomeOffer.discountPercent')
            ->has('i18n.public.how_it_works.steps.questions.gift.title')
            ->has('i18n.public.how_it_works.steps.record.gift.title')
            ->has('i18n.public.how_it_works.steps.text.gift.title')
            ->has('i18n.public.how_it_works.steps.decide.gift.title')
            ->has('i18n.public.how_it_works.steps.family.gift.title')
            ->has('i18n.public.how_it_works.steps.book.gift.title')
            // Les textes d'exemple, les réponses et les engagements viennent du
            // catalogue de l'accueil : la page les lit là, elle ne les recopie pas.
            ->has('i18n.public.landing.proof.sample_verbatim')
            ->has('i18n.public.landing.faq.refuses.a')
            ->has('i18n.public.landing.story.p3')
            ->has('i18n.public.welcome_offer.teaser'),
        );
});

it('s’ouvre sans compte', function (): void {
    $this->get('/comment-ca-marche')->assertOk();
});

it('affiche le prix que l’accueil affiche', function (): void {
    // Deux pages qui calculeraient le prix chacune de leur côté finiraient par
    // en afficher deux : la page lit les mêmes props que l'accueil.
    app(PilotSettings::class)->fill(['mode' => 'pilot', 'pilot_price_cents' => 4_900])->save();

    $this->get('/comment-ca-marche')->assertInertia(fn (AssertableInertia $page) => $page
        ->where('mode', 'pilot')
        ->where('price', 4_900),
    );
});

it('déroule les six étapes dans l’ordre où elles se vivent', function (): void {
    $steps = howItWorksStrings()['steps'];

    // Doc 03 §5.1 : la famille choisit les questions, le lien arrive et elle
    // parle, le texte apparaît, **elle** relit et décide, la famille écoute,
    // le livre arrive. Mettre la famille avant sa décision décrirait un
    // produit où l'on lit avant l'accord.
    expect(howItWorksStepKeys())->toBe(['questions', 'record', 'text', 'decide', 'family', 'book']);

    $step = fn (string $key): string => $steps[$key]['gift']['title'].' '.$steps[$key]['gift']['body'];

    expect($step('questions'))->toContain('questions')
        ->and($step('record'))->toContain('lien')
        ->and($step('record'))->toContain('parle')
        ->and($step('text'))->toContain('mot à mot')
        ->and($step('decide'))->toContain('relit')
        ->and($step('decide'))->toContain('accord')
        ->and($step('family'))->toContain('écoute')
        ->and($step('book'))->toContain('code à scanner');
});

it('raconte chaque étape en deux voix, « elle » quand on offre, « vous » quand on raconte', function (): void {
    $steps = howItWorksStrings()['steps'];

    // Les onglets du leader remplacent tout le contenu, pas seulement
    // l'accroche : chaque étape a donc son titre, son texte et son lien dans
    // les deux voix, et les deux voix ne disent pas la même phrase.
    foreach (howItWorksStepKeys() as $key) {
        foreach (['title', 'body', 'link'] as $part) {
            expect($steps[$key]['gift'][$part] ?? '')->not->toBe('')
                ->and($steps[$key]['self'][$part] ?? '')->not->toBe('');
        }

        expect($steps[$key]['gift']['title'])->not->toBe($steps[$key]['self']['title']);
        expect($steps[$key]['alt'] ?? '')->not->toBe('');
    }

    // Et la voix « pour moi » ne parle jamais d'« elle » à la troisième personne.
    foreach (howItWorksStepKeys() as $key) {
        $self = $steps[$key]['self']['title'].' '.$steps[$key]['self']['body'];
        // « L'IA range, elle n'invente pas » est la formulation canonique d'un
        // engagement, et son « elle » est l'IA : on la retire avant de lire.
        $self = str_replace('L’IA range, elle n’invente pas.', '', $self);

        expect(preg_match('/\b[Ee]lle\b/u', $self))->toBe(0, "« elle » dans la voix « pour moi », étape {$key} : {$self}");
    }
});

it('ne fait entrer dans le livre que les histoires validées', function (): void {
    // Le narrateur est souverain jusqu'au livre (R-1, R-4) : la page le dit à
    // l'étape du livre, pas seulement à celle de la décision.
    expect(howItWorksStrings()['steps']['book']['gift']['body'])->toContain('validées')
        ->and(howItWorksStrings()['steps']['book']['self']['body'])->toContain('validées');
});

it('pose de vraies questions du corpus, mot pour mot', function (): void {
    $corpus = (string) file_get_contents(base_path('docs/roadmap/annexes/A_corpus_questions_v1.md'));
    $samples = howItWorksStrings()['steps']['questions']['samples'];

    // « Voir quelques questions », dit le lien. Alors ce sont celles de
    // l'annexe A, et pas des phrases écrites pour la vitrine.
    expect($samples)->toHaveCount(4);

    foreach ($samples as $key => $question) {
        expect(str_contains($corpus, $question))->toBeTrue("« {$key} » n'est pas une question du corpus.");
    }
});

it('parle du texte avec les mots des engagements, sans les réécrire', function (): void {
    $public = require base_path('lang/fr/public.php');
    $text = howItWorksStrings()['steps']['text']['gift']['body'];

    // « L'IA range, elle n'invente pas » est une formulation canonique (R-10,
    // doc 04 §1). Si l'étape la cite, elle la cite telle quelle.
    expect($text)->toContain('L’IA range, elle n’invente pas')
        ->and($public['landing']['commitments']['ai_arranges'])->toContain('L’IA range, elle n’invente pas');
});

it('ne promet ni impression en France ni reprise après un appel', function (): void {
    // Deux promesses que le dossier ne permet pas encore : l'imprimeur n'est
    // pas contractualisé (bloc 13), et la reprise après un appel ou une mise
    // en veille attend le spike navigateur (doc 03 US-01).
    foreach (howItWorksFlat() as $string) {
        expect(mb_stripos($string, 'imprimé en France'))->toBeFalse("Promesse d'impression : « {$string} »")
            ->and(mb_stripos($string, 'appel entrant'))->toBeFalse("Promesse de reprise : « {$string} »")
            ->and(mb_stripos($string, 'mise en veille'))->toBeFalse("Promesse de reprise : « {$string} »");
    }
});

it('n’emploie ni mot interdit ni tiret long', function (): void {
    $flat = howItWorksFlat();

    expect($flat)->not->toBeEmpty();

    foreach (['pour toujours', 'illimité', 'garanti à vie', 'validation tacite',
        'validation automatique', 'appartiennent à la famille', 'QR autonomes', 'à vie'] as $forbidden) {
        foreach ($flat as $string) {
            expect(mb_stripos($string, $forbidden))->toBeFalse(
                "« {$forbidden} » apparaît sur la page : « {$string} »",
            );
        }
    }

    // Pas de tiret long dans un texte visible (T-134) : une phrase, un point,
    // deux points.
    foreach ($flat as $string) {
        expect(str_contains($string, '—'))->toBeFalse("Tiret long : « {$string} »");
    }
});

it('ouvre sur la question du leader, et ses onglets suivent le vocabulaire du tunnel', function (): void {
    $public = require base_path('lang/fr/public.php');
    $hero = howItWorksStrings()['hero'];

    // Le bandeau de titre pose la question « cadeau ou pour moi », et le
    // tunnel demande ensuite « Un proche » ou « Vous-même » : on n'invente
    // pas un troisième vocabulaire entre les deux.
    expect($hero['title'])->toBe('Comment ça marche')
        ->and($hero['question'])->toContain('offrez')
        ->and($hero['gift'])->toContain('proche')
        ->and($public['checkout']['for']['relative'])->toContain('proche')
        ->and($hero['self'])->toContain('moi');

    // L'accroche et les deux appels existent dans les deux voix.
    foreach (['intro', 'cta', 'together'] as $section) {
        $block = howItWorksStrings()[$section];

        expect($block['gift']['headline'])->not->toBe($block['self']['headline']);
    }

    expect(howItWorksStrings()['cta']['self']['button'])->toContain('mon livre');
});

it('cite le fondateur, et personne d’autre', function (): void {
    // Là où le leader met un avis de client, nous n'avons aucun client : la
    // citation est celle du fondateur, lue dans « Notre histoire », et son
    // auteur est nommé. Une citation sans auteur en invente un.
    expect(howItWorksStrings()['voice']['author'])->toContain('fondateur');
});

it('relie l’accueil à la page, et la page à l’accueil', function (): void {
    $public = require base_path('lang/fr/public.php');

    // L'accueil garde ses quatre étapes et pointe vers les six ; la page
    // renvoie aux questions fréquentes, au livre et à notre histoire, qui
    // restent sur l'accueil.
    expect($public['landing']['how']['more'])->not->toBe('')
        ->and(howItWorksStrings()['questions']['all'])->not->toBe('')
        ->and(howItWorksStrings()['voice']['cta'])->not->toBe('');

    $this->get('/')->assertInertia(fn (AssertableInertia $page) => $page
        ->has('i18n.public.landing.how.more'),
    );
});
