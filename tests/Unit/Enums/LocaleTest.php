<?php

declare(strict_types=1);

use App\Enums\Currency;
use App\Enums\Locale;
use App\Enums\Market;

/*
 * La locale : une langue **et** un marché.
 *
 * La distinction n'est pas cosmétique. `it-CH` lit les mêmes traductions que
 * `it` — il n'y a qu'un `lang/it` — mais formate ses montants et ses dates à
 * la suisse. Confondre les deux, c'est soit dupliquer 861 chaînes pour rien,
 * soit écrire « 45,50 » à quelqu'un qui lit « 45.50 ».
 */

it('sépare la langue des traductions du marché de formatage', function (): void {
    expect(Locale::SwissItalian->language())->toBe('it')
        ->and(Locale::SwissItalian->market())->toBe(Market::Switzerland)
        ->and(Locale::SwissItalian->tag())->toBe('it-CH')
        ->and(Locale::Italian->tag())->toBe('it-IT');
});

it('donne au marché suisse le franc, et l’euro aux autres', function (): void {
    expect(Locale::SwissFrench->currency())->toBe(Currency::SwissFranc)
        ->and(Locale::French->currency())->toBe(Currency::Euro)
        ->and(Locale::Spanish->currency())->toBe(Currency::Euro);
});

it('laisse le français à la racine et préfixe les autres', function (): void {
    // Le français est la langue par défaut : lui donner un préfixe
    // déplacerait toutes les adresses existantes, et chaque lien déjà posé
    // dehors mènerait à une redirection.
    expect(Locale::French->urlPrefix())->toBeNull()
        ->and(Locale::French->routePrefix())->toBe('')
        ->and(Locale::SwissFrench->urlPrefix())->toBe('fr-ch')
        ->and(Locale::SwissFrench->routePrefix())->toBe('fr-ch.');
});

it('nomme chaque langue dans sa propre langue', function (): void {
    // Personne ne cherche son idiome sous un nom étranger.
    expect(Locale::Italian->nativeName())->toBe('Italiano')
        ->and(Locale::Spanish->nativeName())->toBe('Español');
});

it('lit une étiquette écrite de plusieurs façons', function (): void {
    expect(Locale::tryFromTag('fr-CH'))->toBe(Locale::SwissFrench)
        ->and(Locale::tryFromTag('fr_ch'))->toBe(Locale::SwissFrench)
        ->and(Locale::tryFromTag('IT'))->toBe(Locale::Italian)
        ->and(Locale::tryFromTag('de'))->toBeNull()
        ->and(Locale::tryFromTag(null))->toBeNull()
        ->and(Locale::tryFromTag(''))->toBeNull();
});

it('choisit la meilleure langue d’un en-tête de navigateur', function (): void {
    expect(Locale::fromAcceptLanguage('it-CH,it;q=0.9,en;q=0.8'))->toBe(Locale::SwissItalian)
        ->and(Locale::fromAcceptLanguage('it-IT,it;q=0.9'))->toBe(Locale::Italian)
        // La qualité prime sur l'ordre d'écriture.
        ->and(Locale::fromAcceptLanguage('en;q=0.9,es;q=1.0'))->toBe(Locale::Spanish)
        // Une langue seule tombe sur son marché principal.
        ->and(Locale::fromAcceptLanguage('es'))->toBe(Locale::Spanish);
});

it('sert le français de Suisse à un navigateur suisse alémanique', function (): void {
    // L'allemand n'est pas servi. Une famille de Zurich reçoit le français de
    // Suisse — ses montants et ses dates, au moins, lui seront familiers —
    // plutôt que le français de France.
    expect(Locale::fromAcceptLanguage('de-CH,de;q=0.9'))->toBe(Locale::SwissFrench);
});

it('ne devine rien quand l’en-tête ne dit rien qu’on serve', function (): void {
    expect(Locale::fromAcceptLanguage('ja,ko;q=0.8'))->toBeNull()
        ->and(Locale::fromAcceptLanguage(''))->toBeNull()
        ->and(Locale::fromAcceptLanguage(null))->toBeNull();
});
