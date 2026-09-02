<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Lang;
use Symfony\Component\Finder\Finder;

/**
 * @return array<int, string>
 */
function translationKeysUsedInFront(): array
{
    $keys = [];

    $files = Finder::create()
        ->files()
        ->in(base_path('resources/js'))
        ->name(['*.ts', '*.tsx'])
        ->notName('*.test.ts')
        ->notName('*.test.tsx');

    foreach ($files as $file) {
        preg_match_all(
            '/\bt\(\s*[\'"]([a-z0-9_]+(?:\.[a-z0-9_]+)+)[\'"]/i',
            $file->getContents(),
            $matches,
        );

        foreach ($matches[1] as $key) {
            $keys[] = $key;
        }
    }

    return array_values(array_unique($keys));
}

it('a une traduction française pour chaque clé utilisée dans le front', function (): void {
    $missing = array_values(array_filter(
        translationKeysUsedInFront(),
        fn (string $key): bool => ! Lang::has($key, 'fr'),
    ));

    expect($missing)->toBe([], 'Clés absentes de lang/fr : '.implode(', ', $missing));
});

it('trouve bien des clés à vérifier', function (): void {
    expect(translationKeysUsedInFront())->not->toBeEmpty();
});

it('n’a aucun fichier de langue vide', function (): void {
    foreach (Finder::create()->files()->in(base_path('lang/fr'))->name('*.php') as $file) {
        $lines = require $file->getRealPath();

        expect($lines)->toBeArray()->not->toBeEmpty($file->getFilename().' est vide.');
    }
});
