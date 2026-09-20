<?php

declare(strict_types=1);

use App\Enums\QuestionTheme;
use App\Enums\QuizAgeBand;
use App\Enums\QuizDistance;
use App\Enums\QuizOccasion;
use App\Enums\QuizRelationship;
use App\Enums\QuizStorytellerStyle;
use Illuminate\Support\Facades\Lang;

/*
 * Les libellés du tunnel de découverte, dans les trois langues.
 *
 * `I18nKeysTest` vérifie la parité des catalogues et les clés **écrites en
 * toutes lettres** dans le front. Trois jeux de libellés lui échappent, et ce
 * sont justement ceux du quiz : ils sont construits par concaténation à
 * partir d'une valeur d'énumération. Une clé manquante afficherait
 * « enums.quiz_theme.joys » au milieu d'un écran de vente, sans que rien
 * n'échoue.
 */

it('donne un libellé de quiz à chaque valeur, dans chaque langue', function (string $language): void {
    $missing = [];

    $sets = [
        'enums.quiz_relationship' => QuizRelationship::cases(),
        'enums.quiz_subject' => QuizRelationship::cases(),
        'enums.quiz_age_band' => QuizAgeBand::cases(),
        'enums.quiz_distance' => QuizDistance::cases(),
        'enums.quiz_storyteller_style' => QuizStorytellerStyle::cases(),
        'enums.quiz_occasion' => QuizOccasion::cases(),
        // Les thèmes ont deux jeux : celui du back-office et celui du quiz.
        'enums.quiz_theme' => QuestionTheme::cases(),
    ];

    foreach ($sets as $prefix => $cases) {
        foreach ($cases as $case) {
            $key = $prefix.'.'.$case->value;

            if (! Lang::has($key, $language)) {
                $missing[] = $key;
            }
        }
    }

    expect($missing)->toBe([], "Libellés absents de lang/{$language} : ".implode(', ', $missing));
})->with(['fr', 'it', 'es']);

it('garde `label()` et `subject()` sur des clés distinctes', function (): void {
    // Sans quoi l'écran afficherait « Ma mère est à l'aise avec un
    // téléphone ? » au lieu de « votre mère ».
    expect(QuizRelationship::Mother->label())->toBe('enums.quiz_relationship.mother')
        ->and(QuizRelationship::Mother->subject())->toBe('enums.quiz_subject.mother')
        ->and(__(QuizRelationship::Mother->label()))
        ->not->toBe(__(QuizRelationship::Mother->subject()));
});

it('sort soi-même de la liste des liens qui mènent au quiz', function (): void {
    // Les douze écrans suivants sont écrits pour quelqu'un qui offre : les
    // servir à quelqu'un qui raconte sa propre vie demanderait un second jeu
    // de textes dans trois langues.
    expect(QuizRelationship::forOthers())->not->toContain(QuizRelationship::Myself)
        ->and(QuizRelationship::forOthers())->toHaveCount(count(QuizRelationship::cases()) - 1);
});
