<?php

declare(strict_types=1);

use App\Enums\AddressForm;
use App\Enums\GrammaticalGender;
use App\Models\Question;
use App\Support\QuestionWording;

it('accorde un marqueur au genre du narrateur', function (): void {
    $template = 'Où êtes-vous né{|e} ?';

    expect(QuestionWording::resolve($template, GrammaticalGender::Feminine))->toBe('Où êtes-vous née ?')
        ->and(QuestionWording::resolve($template, GrammaticalGender::Masculine))->toBe('Où êtes-vous né ?');
});

it('retombe sur le point médian quand le genre est inconnu', function (): void {
    expect(QuestionWording::resolve('Où êtes-vous né{|e} ?', null))->toBe('Où êtes-vous né·e ?')
        ->and(QuestionWording::resolve('Vous étiez seul{|e}.', null))->toBe('Vous étiez seul·e.');
});

it('prend la forme neutre écrite quand le féminin ne prolonge pas le masculin', function (): void {
    $template = 'De quoi êtes-vous le plus {fier|fière|fier·ère} ?';

    expect(QuestionWording::resolve($template, null))->toBe('De quoi êtes-vous le plus fier·ère ?')
        ->and(QuestionWording::resolve($template, GrammaticalGender::Feminine))->toBe('De quoi êtes-vous le plus fière ?')
        ->and(QuestionWording::resolve('heureu{x|se|x·se}', GrammaticalGender::Masculine))->toBe('heureux');
});

it('laisse intact un texte sans marqueur', function (): void {
    expect(QuestionWording::resolve('Quel est votre tout premier souvenir ?', GrammaticalGender::Feminine))
        ->toBe('Quel est votre tout premier souvenir ?');
});

it('choisit le texte du tutoiement quand le projet tutoie', function (): void {
    $question = Question::factory()->make([
        'text' => 'Où êtes-vous né{|e} ?',
        'text_tu' => 'Où es-tu né{|e} ?',
    ]);

    expect(QuestionWording::for($question, AddressForm::Tu, GrammaticalGender::Feminine))->toBe('Où es-tu née ?')
        ->and(QuestionWording::for($question, AddressForm::Vous, null))->toBe('Où êtes-vous né·e ?');
});

it('garde le vouvoiement quand une question n’a pas encore de texte au tutoiement', function (): void {
    // Une question ajoutée à la main dans l'admin, ou par une fabrique de test :
    // mieux vaut vouvoyer une narratrice qu'on tutoie que lui envoyer un vide.
    $question = Question::factory()->make(['text' => 'Quel est votre premier souvenir ?', 'text_tu' => null]);

    expect(QuestionWording::for($question, AddressForm::Tu, null))->toBe('Quel est votre premier souvenir ?');
});

it('signale les gabarits mal formés', function (string $template, string $problem): void {
    expect(implode(' ', QuestionWording::problems($template)))->toContain($problem);
})->with([
    'accolade non fermée' => ['Où êtes-vous né{|e ?', 'accolade'],
    'accolade orpheline' => ['Où êtes-vous né|e} ?', 'accolade'],
    'une seule forme' => ['Où êtes-vous né{e} ?', 'formes'],
    'quatre formes' => ['{a|b|c|d}', 'formes'],
    'point médian en dur' => ['Où êtes-vous né·e ?', 'point médian'],
    'neutre impossible à déduire' => ['le plus {fier|fière} ?', 'neutre'],
]);

it('accepte un gabarit correct', function (): void {
    expect(QuestionWording::problems('Où êtes-vous né{|e} ? Le plus {fier|fière|fier·ère}.'))->toBe([]);
});
