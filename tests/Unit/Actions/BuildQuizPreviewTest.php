<?php

declare(strict_types=1);

use App\Actions\BuildQuizPreview;
use App\Enums\QuestionTheme;
use App\Models\Question;

/*
 * L'aperçu de fin de quiz : la question qu'on montre est celle qui partira.
 *
 * C'est tout l'écart avec une maquette. Montrer une couverture au prénom du
 * narrateur flatte ; montrer la vraie première question engage — et oblige à
 * la choisir avec les mêmes règles que le moteur, sans quoi l'aperçu
 * promettrait un parcours que personne ne recevra.
 */

/*
 * Le corpus réel est chargé par `ReferenceDataSeeder` dans chaque test. On le
 * vide ici : ces six cas éprouvent des règles de choix, et les éprouver sur
 * soixante questions reviendrait à réécrire l'annexe A dans les assertions.
 * Le corpus réel, lui, est éprouvé dans `tests/Feature/Quiz/QuizTest.php`.
 */
beforeEach(function (): void {
    Question::query()->delete();
});

function question(string $slug, QuestionTheme $theme, int $difficulty, int $order): Question
{
    return Question::query()->create([
        'slug' => $slug,
        'text' => 'Q '.$slug,
        'theme' => $theme,
        'difficulty' => $difficulty,
        'order_hint' => $order,
    ]);
}

it('ouvre sur la question la plus facile et la plus précoce du corpus', function (): void {
    question('intime', QuestionTheme::Legacy, 5, 10);
    question('facile-tard', QuestionTheme::Childhood, 1, 90);
    question('facile-tot', QuestionTheme::Childhood, 1, 20);

    $preview = app(BuildQuizPreview::class)->handle([QuestionTheme::Legacy]);

    expect($preview['first'])->toBe('Q facile-tot');
});

it('donne une suite par thème coché, dans l’ordre où ils ont été cochés', function (): void {
    question('enfance-1', QuestionTheme::Childhood, 1, 10);
    question('enfance-2', QuestionTheme::Childhood, 1, 20);
    question('metier-1', QuestionTheme::Work, 2, 200);
    question('metier-2', QuestionTheme::Work, 2, 210);
    question('amour-1', QuestionTheme::Love, 3, 300);

    $preview = app(BuildQuizPreview::class)->handle([
        QuestionTheme::Work,
        QuestionTheme::Love,
        QuestionTheme::Childhood,
    ]);

    // L'ouverture est prise dans le corpus entier ; les suites suivent les
    // thèmes, et aucune ne répète l'ouverture.
    expect($preview['first'])->toBe('Q enfance-1')
        ->and($preview['next'])->toBe(['Q metier-1', 'Q amour-1', 'Q enfance-2']);
});

it('complète avec le corpus quand les thèmes cochés sont trop pauvres', function (): void {
    question('enfance-1', QuestionTheme::Childhood, 1, 10);
    question('metier-1', QuestionTheme::Work, 2, 200);
    question('lieux-1', QuestionTheme::Places, 3, 300);
    question('lieux-2', QuestionTheme::Places, 3, 310);

    $preview = app(BuildQuizPreview::class)->handle([QuestionTheme::Work]);

    expect($preview['next'])->toHaveCount(3)
        ->and($preview['next'][0])->toBe('Q metier-1');
});

it('ignore une question retirée du corpus', function (): void {
    question('enfance-1', QuestionTheme::Childhood, 1, 10);
    question('retiree', QuestionTheme::Work, 2, 200)->update(['is_active' => false]);
    question('metier-1', QuestionTheme::Work, 2, 210);

    $preview = app(BuildQuizPreview::class)->handle([QuestionTheme::Work]);

    expect($preview['next'])->toBe(['Q metier-1']);
});

it('ne rend rien plutôt qu’une question inventée quand le corpus est vide', function (): void {
    $preview = app(BuildQuizPreview::class)->handle([QuestionTheme::Work]);

    expect($preview['first'])->toBeNull()
        ->and($preview['next'])->toBe([]);
});
