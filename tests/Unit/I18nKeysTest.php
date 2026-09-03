<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Lang;
use Symfony\Component\Finder\Finder;

/*
 * Les clés de traduction : elles existent, et elles sont uniques.
 *
 * Deux fautes silencieuses, l'une et l'autre coûteuses.
 *
 * La première : une clé appelée qui n'existe pas. Laravel rend alors la clé
 * elle-même, et « initiator.dashboard.title » s'affiche à l'écran d'une
 * famille. Rien n'échoue, personne n'est prévenu.
 *
 * La seconde, pire : **deux fois la même clé** dans un fichier de langue. PHP
 * garde la dernière et jette la première, sans un mot. Un fichier qui grossit
 * par ajouts successifs finit par redéfinir `'settings' => [...]`, et quatre
 * messages disparaissent — écart T-106, découvert exactement comme ça.
 */

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

/**
 * Les clés dupliquées d'un fichier de langue, par portée.
 *
 * On lit les **jetons** et non le tableau : `require` rend déjà le tableau
 * dédoublonné, et ne peut donc rien révéler. Une clé est une chaîne suivie
 * d'un `=>` ; la portée est la profondeur de crochets.
 *
 * @return list<string>
 */
function duplicateTranslationKeys(string $source): array
{
    $tokens = token_get_all($source);
    $scopes = [0 => []];
    $depth = 0;
    $duplicates = [];
    $pendingKey = null;

    foreach ($tokens as $token) {
        if (is_string($token)) {
            if ($token === '[') {
                $depth++;
                $scopes[$depth] = [];
            } elseif ($token === ']') {
                unset($scopes[$depth]);
                $depth = max(0, $depth - 1);
            }

            $pendingKey = null;

            continue;
        }

        [$id, $text] = $token;

        if ($id === T_WHITESPACE || $id === T_COMMENT || $id === T_DOC_COMMENT) {
            continue;
        }

        if ($id === T_CONSTANT_ENCAPSED_STRING) {
            $pendingKey = trim($text, "'\"");

            continue;
        }

        if ($id === T_DOUBLE_ARROW && $pendingKey !== null) {
            if (isset($scopes[$depth][$pendingKey])) {
                $duplicates[] = $pendingKey;
            }

            $scopes[$depth][$pendingKey] = true;
        }

        $pendingKey = null;
    }

    return $duplicates;
}

it('repère une clé définie deux fois', function (): void {
    // La preuve que le détecteur détecte : sans elle, le test suivant
    // passerait aussi bien sur un détecteur qui ne trouve jamais rien.
    $duplicated = <<<'PHP'
        <?php return [
            'settings' => ['saved' => 'a'],
            'other' => ['saved' => 'b'],
            'settings' => ['title' => 'c'],
        ];
        PHP;

    expect(duplicateTranslationKeys($duplicated))->toBe(['settings'])
        ->and(duplicateTranslationKeys('<?php return [\'a\' => 1, \'b\' => 2];'))->toBe([]);
});

it('ne définit aucune clé deux fois dans un fichier de langue', function (): void {
    $offenders = [];

    foreach (Finder::create()->files()->in(base_path('lang'))->name('*.php') as $file) {
        foreach (duplicateTranslationKeys($file->getContents()) as $key) {
            $offenders[] = $file->getRelativePathname().' : '.$key;
        }
    }

    expect($offenders)->toBe([], 'Clés redéfinies (PHP garde la dernière) : '.implode(', ', $offenders));
});

it('appelle des clés qui existent', function (): void {
    $missing = [];

    $files = Finder::create()
        ->files()
        ->in([base_path('app'), base_path('database'), base_path('routes')])
        ->name('*.php');

    foreach ($files as $file) {
        // `__()` et `trans_choice()` : la seconde forme existe dès qu'un
        // texte doit s'accorder, et une clé qui échappe au scanner n'est plus
        // vérifiée par personne.
        preg_match_all(
            "/(?:__|trans_choice)\('([a-z][a-z0-9_]*\.[A-Za-z0-9_.]+)'\s*[,)]/",
            $file->getContents(),
            $matches,
        );

        foreach ($matches[1] as $key) {
            // Les clés construites par concaténation (`'…status.'.$value`)
            // échappent à cette expression, et c'est voulu : on ne devine pas
            // ce qu'une variable vaudra. Elles sont couvertes par les tests
            // fonctionnels des pages qui les emploient.
            if (! is_string(trans($key)) || trans($key) === $key) {
                $missing[] = $file->getRelativePathname().' : '.$key;
            }
        }
    }

    expect($missing)->toBe([], 'Clés appelées mais absentes : '.implode(', ', $missing));
});
