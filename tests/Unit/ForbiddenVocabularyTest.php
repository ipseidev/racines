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

    /*
     * La rédaction des pages de vente est hors de cette garde : elle est
     * arbitrée par le fondateur, page par page, et itérée plusieurs fois par
     * jour (T-218). Cela couvre l'accueil (`lp`), la page « Nos livres », les
     * questions fréquentes et les titres de recherche.
     *
     * Le reste du produit — ce que lisent la narratrice, la famille, les
     * courriels, les SMS et le back-office — y reste soumis, et c'est là que
     * R-11 protège quelque chose : une promesse tenable dans un message qu'on
     * envoie.
     */
    foreach (['lp', 'books', 'faq_page', 'seo'] as $sales) {
        unset($values[$sales]);
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
function productLangFiles(string $language = 'fr'): array
{
    $ours = ['actions.php', 'admin.php', 'common.php', 'enums.php', 'family.php',
        'narrator.php', 'notifications.php', 'public.php'];

    return array_values(array_filter(
        array_map(fn (string $name): string => base_path("lang/{$language}/".$name), $ours),
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

/**
 * Le pluriel entre parenthèses est un pluriel qu'on n'a pas écrit.
 *
 * Trouvé au checkpoint du bloc 08 : le résumé des réactions partait en SMS
 * chez la narratrice avec « Hier, 1 personne(s) ont écouté vos histoires ».
 * Deux fautes en huit mots — la parenthèse, et l'accord du verbe — dans un
 * message qui doit donner envie de raconter la suite. Laravel sait choisir
 * entre deux formes (`trans_choice`) ; s'en passer fait payer au lecteur le
 * confort de celui qui écrit.
 */
it('n’écrit jamais un pluriel entre parenthèses dans un texte visible', function (): void {
    $offenders = [];

    foreach (productLangFiles() as $file) {
        foreach (translatedStrings($file) as $value) {
            // `(s)`, `(e)`, `(es)` et leurs variantes accolées à un mot.
            if (preg_match('/\w\((s|e|es|x)\)/u', $value) === 1) {
                $offenders[] = basename($file).' : '.$value;
            }
        }
    }

    expect($offenders)->toBe([], 'Pluriels entre parenthèses : '.implode(' | ', $offenders));
});

/*
|--------------------------------------------------------------------------
| R-11 dans les autres langues (T-238)
|--------------------------------------------------------------------------
|
| Une promesse intenable ne devient pas tenable parce qu'elle est écrite en
| italien. Les expressions sont les équivalents usuels de la liste R-11 ; le
| périmètre est **le même** que pour le français, sections de vente comprises
| (voir `translatedStrings()`, qui dit pourquoi elles en sortent).
|
*/

it('n’emploie aucune expression interdite dans les autres langues', function (string $language, string $expression): void {
    $offenders = [];

    foreach (productLangFiles($language) as $file) {
        foreach (translatedStrings($file) as $string) {
            if (mb_stripos($string, $expression) !== false) {
                $offenders[] = $language.'/'.basename($file).' : « '.mb_substr($string, 0, 60).' »';
            }
        }
    }

    $directory = base_path("resources/views/legal/{$language}");

    if (is_dir($directory)) {
        foreach (Finder::create()->files()->in($directory)->name('*.md') as $file) {
            if (mb_stripos($file->getContents(), $expression) !== false) {
                $offenders[] = $file->getRelativePathname();
            }
        }
    }

    expect($offenders)->toBe([], "« {$expression} » est interdit (R-11) : ".implode(', ', $offenders));
})->with([
    ['it', 'per sempre'],
    ['it', 'illimitat'],
    ['it', 'garantito a vita'],
    ['it', 'appartengono alla famiglia'],
    ['it', 'convalida tacita'],
    ['it', 'convalida automatica'],
    ['it', 'QR autonomi'],
    ['es', 'para siempre'],
    ['es', 'ilimitad'],
    // « garantizado de por vida » et non « de por vida » seul : la page dit
    // « No prometemos una conservación de por vida », qui est exactement la
    // prudence que R-11 demande.
    ['es', 'garantizado de por vida'],
    ['es', 'pertenecen a la familia'],
    ['es', 'validación tácita'],
    ['es', 'validación automática'],
    ['es', 'QR autónomos'],
]);
