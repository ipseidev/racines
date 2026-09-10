<?php

declare(strict_types=1);

use App\Enums\Currency;
use App\Enums\Locale;
use App\Support\Locales;
use App\Support\Money;

/*
 * Un prix, écrit comme on l'écrit sur le marché de la page.
 *
 * Ce fichier a un jumeau : `resources/js/lib/intl.test.ts`. Les deux vérifient
 * **les mêmes chaînes**, parce que le serveur écrit le prix dans les
 * métadonnées et le client dans la page : deux caractères différents pour un
 * même prix, et l'hydratation de l'accueil échoue (T-238).
 */

/** Les espaces insécables ne se lisent pas dans un message d'échec. */
function readable(string $price): string
{
    return str_replace(["\u{202f}", "\u{00a0}"], ' ', $price);
}

it('écrit un prix rond sans décimales', function (): void {
    // « 49 € » et non « 49,00 € » : la précision inutile fait paraître le prix
    // plus lourd qu'il n'est.
    expect(readable(Money::format(4_900, Locale::French)))->toBe('49 €');
});

it('garde les centimes quand il y en a', function (): void {
    expect(readable(Money::format(4_550, Locale::French)))->toBe('45,50 €');
});

it('sépare les décimales par un point en Suisse', function (): void {
    expect(readable(Money::format(4_550, Locale::SwissFrench)))->toBe('45.50 €')
        ->and(readable(Money::format(4_900, Locale::SwissItalian)))->toBe('49 €');
});

it('groupe les milliers selon le marché', function (): void {
    expect(readable(Money::format(129_900, Locale::French)))->toBe('1 299 €')
        ->and(Money::format(129_900, Locale::Italian))->toContain('1.299')
        ->and(Money::format(129_900, Locale::SwissFrench))->toContain('1’299');
});

it('écrit le franc suisse devant le montant, avec son tiret de centimes', function (): void {
    expect(readable(Money::format(4_900, Locale::SwissFrench, Currency::SwissFranc)))->toBe('CHF 49.–')
        ->and(readable(Money::format(4_550, Locale::SwissFrench, Currency::SwissFranc)))->toBe('CHF 45.50');
});

it('marque un montant négatif d’un vrai signe moins', function (): void {
    expect(readable(Money::format(-1_000, Locale::French)))->toBe('−10 €');
});

it('suit la langue de la requête quand on ne lui en donne pas', function (): void {
    Locales::set(Locale::SwissFrench);

    expect(readable(Money::euros(4_550)))->toBe('45.50 €');

    Locales::set(Locale::French);

    expect(readable(Money::euros(4_550)))->toBe('45,50 €');
});

it('rend une donnée structurée avec un point décimal, quelle que soit la langue', function (): void {
    Locales::set(Locale::Italian);

    // Une donnée structurée n'est pas un texte : elle est lue par une machine,
    // qui attend le format de schema.org et non celui du pays.
    expect(Money::decimal(4_900))->toBe('49.00');
});
