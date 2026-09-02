<?php

declare(strict_types=1);

use App\Support\Wer;

it('ignore la casse et la ponctuation', function (): void {
    expect(Wer::compute('La maison de Kerhostin.', 'la maison de kerhostin'))->toBe(0.0);
});

it('ne compte pas une apostrophe typographique comme une erreur', function (): void {
    // Sans cette normalisation, tous les fournisseurs seraient pénalisés de
    // la même façon et l'écart qu'on cherche à mesurer disparaîtrait.
    expect(Wer::compute("c'était l'été", 'c’était l’été'))->toBe(0.0);
});

it('garde les mots composés et les élisions comme des mots', function (): void {
    // Un mot composé est un mot ; une élision aussi. Les découper gonflerait
    // le WER sans rien dire de la qualité du fournisseur.
    expect(Wer::normalize('quatre-vingts, c’est l’âge !'))
        ->toBe(['quatre-vingts', "c'est", "l'âge"]);
});

it('ne normalise pas les nombres : « 80 » n’est pas « quatre-vingts »', function (): void {
    // Ils ne se lisent pas pareil dans un livre.
    expect(Wer::compute('quatre-vingts ans', '80 ans'))->toBe(0.5);
});

it('compte une substitution', function (): void {
    expect(Wer::compute('la maison de mon enfance', 'la maison de son enfance'))->toBe(0.2);
});

it('compte une insertion', function (): void {
    expect(Wer::compute('la maison de mon enfance', 'la grande maison de mon enfance'))->toBe(0.2);
});

it('compte une suppression', function (): void {
    expect(Wer::compute('la maison de mon enfance', 'la maison de enfance'))->toBe(0.2);
});

it('cumule les trois sortes d’erreurs', function (): void {
    expect(Wer::compute('un deux trois quatre', 'un deux cinq'))->toBe(0.5);
});

it('rend 1 quand rien n’a été entendu', function (): void {
    expect(Wer::compute('la maison de mon enfance', ''))->toBe(1.0);
});

it('rend 1 quand tout a été inventé sur une référence vide', function (): void {
    expect(Wer::compute('', 'du texte'))->toBe(1.0)
        ->and(Wer::compute('', ''))->toBe(0.0);
});

it('calcule la médiane et le neuvième décile', function (): void {
    $values = [0.1, 0.2, 0.3, 0.4, 0.5];

    expect(Wer::median($values))->toBe(0.3)
        ->and(Wer::percentile($values, 90))->toBe(0.5)
        ->and(Wer::median([0.1, 0.3]))->toBe(0.2)
        ->and(Wer::median([]))->toBeNull();
});
