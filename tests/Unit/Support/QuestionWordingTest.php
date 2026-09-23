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

/*
 * Les accolades doubles parlent de l'acheteur : son prénom, son genre. Les
 * simples restent celles de la narratrice. Une même phrase peut mêler les deux.
 */
it('nomme l’acheteur et l’accorde à son genre', function (): void {
    $template = 'Raconte le jour où {{prénom}} est né{{|e}}, et la première fois que tu l’as vu{{|e}}.';

    expect(QuestionWording::resolve($template, null, 'Claire', GrammaticalGender::Feminine))
        ->toBe('Raconte le jour où Claire est née, et la première fois que tu l’as vue.')
        ->and(QuestionWording::resolve($template, null, 'Paul', GrammaticalGender::Masculine))
        ->toBe('Raconte le jour où Paul est né, et la première fois que tu l’as vu.');
});

it('accorde la narratrice et l’acheteur chacun de son côté', function (): void {
    $template = 'Ce qui t’a frappé{|e} chez {{lui|elle|lui ou elle}}.';

    expect(QuestionWording::resolve($template, GrammaticalGender::Feminine, 'Paul', GrammaticalGender::Masculine))
        ->toBe('Ce qui t’a frappée chez lui.')
        ->and(QuestionWording::resolve($template, GrammaticalGender::Masculine, 'Claire', GrammaticalGender::Feminine))
        ->toBe('Ce qui t’a frappé chez elle.');
});

it('retombe sur la forme neutre quand le genre de l’acheteur est inconnu', function (): void {
    expect(QuestionWording::resolve('{{prénom}} est né{{|e}} quand {{il|elle|il ou elle}} était petit{{|e}}.', null, 'Camille', null))
        ->toBe('Camille est né·e quand il ou elle était petit·e.');
});

it('laisse le prénom en marqueur quand on ne le connaît pas', function (): void {
    // Une telle question ne part jamais (voir `Question::scopeSendableWithoutProfile`) ;
    // l'admin, lui, doit pouvoir la lire.
    expect(QuestionWording::resolve('Ton premier souvenir de {{prénom}} ?', null))
        ->toBe('Ton premier souvenir de {{prénom}} ?')
        ->and(QuestionWording::needsBuyerName('Ton premier souvenir de {{prénom}} ?'))->toBeTrue()
        ->and(QuestionWording::needsBuyerName('Ton premier souvenir ?'))->toBeFalse();
});

it('signale les marqueurs d’acheteur mal formés', function (string $template, string $problem): void {
    expect(implode(' ', QuestionWording::problems($template)))->toContain($problem);
})->with([
    'variable inconnue' => ['Le jour où {{nom}} est né ?', 'variable'],
    'une seule forme' => ['{{prénom}} est né{{e}} ?', 'formes'],
    'neutre impossible à déduire' => ['chez {{lui|elle}}', 'neutre'],
    'accolades doubles non fermées' => ['chez {{lui|elle|lui ou elle}', 'accolade'],
]);

it('accepte un gabarit qui mêle les deux marqueurs', function (): void {
    expect(QuestionWording::problems('Ce qui t’a frappé{|e} chez {{lui|elle|lui ou elle}}, {{prénom}}.'))->toBe([]);
});
