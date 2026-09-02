<?php

declare(strict_types=1);

use Symfony\Component\Finder\Finder;

/**
 * Vocabulaire interdit du référentiel R-11.
 *
 * Ces expressions promettent ce que le service ne peut pas tenir. Elles sont
 * bannies des textes visibles : interface, courriels, SMS, playbooks, mentions
 * légales.
 */
it('n’emploie aucune expression interdite dans les textes visibles', function (string $expression): void {
    $directories = array_filter([
        base_path('lang/fr'),
        base_path('resources/views'),
        is_dir(base_path('resources/playbooks')) ? base_path('resources/playbooks') : null,
    ]);

    $offenders = [];

    $files = Finder::create()->files()->in($directories)->name(['*.php', '*.md', '*.blade.php']);

    foreach ($files as $file) {
        if (preg_match('/'.preg_quote($expression, '/').'/iu', $file->getContents()) === 1) {
            $offenders[] = $file->getRelativePathname();
        }
    }

    expect($offenders)->toBe([], "« {$expression} » est interdit (R-11) : ".implode(', ', $offenders));
})->with([
    'pour toujours',
    'illimité',
    'illimitée',
    'QR autonomes',
    'appartiennent à la famille',
    'validation tacite',
    'validation automatique',
    'garanti à vie',
]);

it('n’emploie aucune tournure culpabilisante dans les messages', function (string $expression): void {
    $offenders = [];

    foreach (Finder::create()->files()->in(base_path('lang/fr'))->name('*.php') as $file) {
        if (preg_match('/'.preg_quote($expression, '/').'/iu', $file->getContents()) === 1) {
            $offenders[] = $file->getRelativePathname();
        }
    }

    expect($offenders)->toBe([], "« {$expression} » culpabilise le narrateur : ".implode(', ', $offenders));
})->with([
    'vous n’avez toujours pas',
    'vous n\'avez toujours pas',
    'dernier rappel',
    'il ne vous reste que',
]);
