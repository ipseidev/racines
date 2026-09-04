<?php

declare(strict_types=1);

use App\Support\Phone;

/**
 * Le numéro tel qu'on le tape devient un numéro international (T-136).
 */
it('ramène un numéro français au format international', function (string $raw, string $expected): void {
    expect(Phone::e164($raw))->toBe($expected);
})->with([
    ['06 12 34 56 78', '+33612345678'],
    ['06.12.34.56.78', '+33612345678'],
    ['06-12-34-56-78', '+33612345678'],
    ['0612345678', '+33612345678'],
    ['6 12 34 56 78', '+33612345678'],
    ['0033 6 12 34 56 78', '+33612345678'],
    ['+33 6 12 34 56 78', '+33612345678'],
    ['+41 79 123 45 67', '+41791234567'],
]);

it('connaît quelques voisins', function (): void {
    expect(Phone::e164('0470 12 34 56', 'BE'))->toBe('+32470123456');
});

it('laisse passer ce qui ne ressemble à rien, pour que la validation le dise', function (): void {
    // Ni international, ni dix chiffres, ni neuf : on ne devine pas.
    expect(Phone::e164('12'))->toBe('12')
        ->and(Phone::e164('abc'))->toBe('abc');
});

it('rend null pour rien', function (): void {
    expect(Phone::e164(null))->toBeNull()
        ->and(Phone::e164('   '))->toBeNull();
});
