<?php

declare(strict_types=1);

use App\Support\Names;

/*
 * « de Marie », « d'Odette », « di Marco ».
 *
 * Les mêmes cas que `resources/js/lib/intl.test.ts`, volontairement : les
 * deux fonctions sont jumelles et doivent rendre les mêmes octets. Le titre
 * d'un livre se choisit à l'écran et s'imprime des mois plus tard ; si l'une
 * élidait et l'autre non, la couverture reçue ne serait pas celle qui avait
 * été montrée.
 */

it('élide en français devant une voyelle ou un h muet', function (): void {
    expect(Names::of('Odette', 'fr'))->toBe('d’Odette')
        ->and(Names::of('Élise', 'fr'))->toBe('d’Élise')
        ->and(Names::of('Henri', 'fr'))->toBe('d’Henri')
        ->and(Names::of('Yvonne', 'fr'))->toBe('d’Yvonne')
        ->and(Names::of('Marie', 'fr'))->toBe('de Marie')
        ->and(Names::of('  Yvonne ', 'fr'))->toBe('d’Yvonne');
});

it('n’élide ni en italien ni en castillan', function (): void {
    // L'italien élide à l'oral soigné, mais l'usage écrit courant garde
    // « di Anna » ; l'espagnol n'élide jamais.
    expect(Names::of('Anna', 'it'))->toBe('di Anna')
        ->and(Names::of('Marco', 'it'))->toBe('di Marco')
        ->and(Names::of('Ana', 'es'))->toBe('de Ana');
});

it('emploie une apostrophe typographique, pas une droite', function (): void {
    // Une apostrophe droite sur une couverture composée en Fraunces se voit,
    // et c'est le genre de détail qu'on découvre à la livraison.
    expect(Names::of('Odette', 'fr'))->toContain('’')
        ->and(Names::of('Odette', 'fr'))->not->toContain("'");
});
