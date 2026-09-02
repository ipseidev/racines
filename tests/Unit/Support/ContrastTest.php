<?php

declare(strict_types=1);

use App\Support\Contrast;

it('calcule les rapports de référence WCAG', function (): void {
    expect(Contrast::ratio('#000000', '#FFFFFF'))->toBe(21.0)
        ->and(Contrast::ratio('#FFFFFF', '#FFFFFF'))->toBe(1.0);
});

it('est symétrique et insensible à la casse et au dièse', function (): void {
    expect(Contrast::ratio('#1F3D2B', '#FFFFFF'))
        ->toBe(Contrast::ratio('ffffff', '1f3d2b'));
});

it('accepte la notation à trois caractères', function (): void {
    expect(Contrast::ratio('#000', '#fff'))->toBe(21.0);
});

it('juge la lisibilité au seuil AA de 4,5', function (): void {
    expect(Contrast::isReadable('#1B1B1B', '#F7F5EF'))->toBeTrue()
        ->and(Contrast::isReadable('#CCCCCC', '#FFFFFF'))->toBeFalse();
});

it('refuse une couleur mal formée', function (): void {
    expect(fn () => Contrast::ratio('rouge', '#FFFFFF'))
        ->toThrow(InvalidArgumentException::class);
});
