<?php

declare(strict_types=1);

use Symfony\Component\Finder\Finder;

/**
 * Vocabulaire interdit du référentiel R-11, et tournures culpabilisantes.
 *
 * Ces expressions promettent ce que le service ne peut pas tenir, ou mettent
 * en cause quelqu'un qui n'a rien promis. Elles sont bannies des textes
 * **visibles**.
 *
 * Le mot compte : ce test lit les **valeurs traduites**, pas le contenu brut
 * des fichiers. Un commentaire qui explique la règle en citant la tournure
 * interdite n'est pas une faute — c'est même la meilleure façon de la
 * transmettre — et un test qui le refuserait pousserait à écrire des
 * commentaires évasifs (écart T-96).
 *
 * @return list<string>
 */
function translatedStrings(string $file): array
{
    $values = require $file;

    if (! is_array($values)) {
        return [];
    }

    $flat = [];

    array_walk_recursive($values, function (mixed $value) use (&$flat): void {
        if (is_string($value)) {
            $flat[] = $value;
        }
    });

    return $flat;
}

/**
 * Les fichiers de langue **du produit**.
 *
 * `validation.php` et consorts viennent du framework : « ce champ est
 * obligatoire » y est du français correct pour un formulaire, et n'a rien à
 * voir avec la façon dont on s'adresse à un narrateur.
 *
 * @return list<string>
 */
function productLangFiles(): array
{
    $ours = ['actions.php', 'admin.php', 'common.php', 'enums.php', 'family.php',
        'narrator.php', 'notifications.php', 'public.php'];

    return array_values(array_filter(
        array_map(fn (string $name): string => base_path('lang/fr/'.$name), $ours),
        'is_file',
    ));
}

it('n’emploie aucune expression interdite dans les textes visibles', function (string $expression): void {
    $offenders = [];

    foreach (productLangFiles() as $file) {
        foreach (translatedStrings($file) as $string) {
            if (mb_stripos($string, $expression) !== false) {
                $offenders[] = basename($file).' : « '.mb_substr($string, 0, 60).' »';
            }
        }
    }

    // Les vues Blade et les playbooks n'ont pas de valeurs à parcourir : on
    // les lit tels quels, comme avant.
    $directories = array_filter([
        base_path('resources/views'),
        is_dir(base_path('resources/playbooks')) ? base_path('resources/playbooks') : null,
    ]);

    foreach (Finder::create()->files()->in($directories)->name(['*.md', '*.blade.php']) as $file) {
        if (mb_stripos($file->getContents(), $expression) !== false) {
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

    foreach (productLangFiles() as $file) {
        foreach (translatedStrings($file) as $string) {
            if (mb_stripos($string, $expression) !== false) {
                $offenders[] = basename($file).' : « '.mb_substr($string, 0, 60).' »';
            }
        }
    }

    expect($offenders)->toBe([], "« {$expression} » culpabilise : ".implode(', ', $offenders));
})->with([
    // Le narrateur n'a rien promis : il raconte quand il veut.
    'vous n’avez toujours pas',
    'vous n\'avez toujours pas',
    'vous n’avez pas encore répondu',
    'vous avez oublié',
    'nous attendons toujours',
    'sans réponse de votre part',
    'faute de réponse',
    // Rien n'expire, rien ne se ferme : la porte reste ouverte.
    'dernier rappel',
    'ultime rappel',
    'il ne vous reste que',
    'plus que quelques jours',
    'avant qu’il ne soit trop tard',
]);

it('parcourt bien les valeurs, et non le contenu brut des fichiers', function (): void {
    // Garde-fou du garde-fou : si `translatedStrings` rendait un tableau vide,
    // les deux tests ci-dessus passeraient sans rien vérifier.
    $strings = translatedStrings(base_path('lang/fr/notifications.php'));

    expect($strings)->not->toBeEmpty()
        ->and(count($strings))->toBeGreaterThan(50);

    foreach ($strings as $string) {
        expect($string)->not->toContain('*');
    }
});
