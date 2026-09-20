<?php

declare(strict_types=1);

use App\Enums\BookTitle;

/*
 * Le titre de la couverture.
 *
 * Ce qui s'y joue tient en une phrase : le titre montré au tunnel de
 * découverte est celui qui s'imprimera. Ces cas éprouvent la composition
 * serveur ; `resources/js/lib/quiz.test.ts` éprouve la même sur les mêmes
 * exemples, et les deux doivent rendre les mêmes octets.
 */

it('compose chaque formule avec le prénom élidé', function (): void {
    expect(BookTitle::FirstName->compose('Jeanne', null, 'fr'))->toBe('Jeanne')
        ->and(BookTitle::Story->compose('Jeanne', null, 'fr'))->toBe('L’histoire de Jeanne')
        ->and(BookTitle::Stories->compose('Odette', null, 'fr'))->toBe('Les histoires d’Odette')
        ->and(BookTitle::Life->compose('Marcelle', null, 'fr'))->toBe('La vie de Marcelle')
        ->and(BookTitle::Memories->compose('Yvonne', null, 'fr'))->toBe('Les souvenirs d’Yvonne');
});

it('compose dans la langue du projet', function (): void {
    expect(BookTitle::Story->compose('Anna', null, 'it'))->toBe('La storia di Anna')
        ->and(BookTitle::Memories->compose('Ana', null, 'es'))->toBe('Los recuerdos de Ana');
});

it('imprime le titre libre tel qu’il a été écrit', function (): void {
    // Un titre libre ne se traduit pas : ce que la famille a écrit est ce
    // qui s'imprime.
    expect(BookTitle::Custom->compose('Jeanne', 'Les dimanches chez Mamie', 'fr'))
        ->toBe('Les dimanches chez Mamie');
});

it('retombe sur le prénom quand le titre libre est vide', function (): void {
    // Le cas arrive : un champ ouvert puis abandonné en cours de route. Mieux
    // vaut une couverture sobre qu'une couverture muette.
    expect(BookTitle::Custom->compose('Jeanne', '   ', 'fr'))->toBe('Jeanne')
        ->and(BookTitle::Custom->compose('Jeanne', null, 'fr'))->toBe('Jeanne');
});

it('garde le prénom seul comme valeur par défaut', function (): void {
    // C'est exactement ce qu'imprimaient les couvertures avant ce choix :
    // aucun livre en cours ne change de titre parce qu'on a ajouté un écran.
    expect(BookTitle::default())->toBe(BookTitle::FirstName)
        ->and(BookTitle::cases()[0])->toBe(BookTitle::FirstName);
});

it('n’a pas de patron pour le titre libre, et un pour chaque formule', function (): void {
    expect(BookTitle::Custom->pattern())->toBeNull();

    foreach (BookTitle::cases() as $case) {
        if ($case === BookTitle::Custom) {
            continue;
        }

        expect(__($case->pattern() ?? ''))->not->toBe($case->pattern());
    }
});

it('envoie au front des formules, pas des titres tout faits', function (): void {
    // Le prénom se tape à l'écran d'avant : le serveur ne peut pas composer,
    // il transmet le patron et l'écran substitue.
    $options = collect(BookTitle::options('fr'))->keyBy('value');

    expect($options['story']['pattern'])->toBe('L’histoire :of')
        ->and($options['story']['label'])->toBe('L’histoire de…')
        ->and($options['custom']['pattern'])->toBeNull();
});
