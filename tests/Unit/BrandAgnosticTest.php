<?php

declare(strict_types=1);

use Symfony\Component\Finder\Finder;

/**
 * Le nom de marque n'est pas arrêté. Tant qu'il ne l'est pas, et même après,
 * il ne doit exister qu'à un seul endroit : les réglages, avec config/brand.php
 * pour repli. Ce test échoue si quelqu'un le recopie dans le code.
 */
it('n’écrit le nom de marque nulle part dans le code', function (): void {
    $name = (string) config('brand.product_name');

    expect(strlen($name))->toBeGreaterThanOrEqual(4, 'Le nom de marque est trop court pour être cherché de façon fiable.');

    $files = Finder::create()
        ->files()
        ->in([base_path('app'), base_path('resources/js'), base_path('lang'), base_path('database')])
        ->name(['*.php', '*.ts', '*.tsx', '*.blade.php'])
        ->notPath('vendor');

    $offenders = [];

    foreach ($files as $file) {
        if (stripos($file->getContents(), $name) !== false) {
            $offenders[] = $file->getRelativePathname();
        }
    }

    expect($offenders)->toBe([], 'Le nom de marque doit venir de BrandSettings : '.implode(', ', $offenders));
});

it('ne code pas le domaine des liens en dur', function (): void {
    $domain = (string) config('brand.links_domain');

    if (in_array($domain, ['localhost', '127.0.0.1', ''], true)) {
        expect(true)->toBeTrue(); // domaine local : rien à vérifier

        return;
    }

    $files = Finder::create()
        ->files()
        ->in([base_path('app'), base_path('resources/js')])
        ->name(['*.php', '*.ts', '*.tsx']);

    $offenders = [];

    foreach ($files as $file) {
        if (str_contains($file->getContents(), $domain)) {
            $offenders[] = $file->getRelativePathname();
        }
    }

    expect($offenders)->toBe([]);
});
