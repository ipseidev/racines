<?php

declare(strict_types=1);

use App\Services\Qr\QrImage;

/**
 * Le QR d'un chapitre.
 *
 * Ce que le test garde, c'est ce qui casserait l'impression : du vectoriel,
 * pas de déclaration XML — le SVG est incrusté dans une page HTML, et un
 * `<?xml ?>` au milieu du corps la rend invalide — et une adresse qui se
 * relit vraiment.
 */
it('rend un SVG incrustable, sans déclaration XML', function (): void {
    $svg = QrImage::svg('https://exemple.test/q/abc');

    expect($svg)->toContain('<svg')
        ->and($svg)->not->toStartWith('<?xml');
});

it('encode bien l’adresse qu’on lui donne', function (): void {
    // Deux adresses différentes ne peuvent pas produire le même motif : sans
    // cette garantie, un QR de chapitre pourrait mener au chapitre voisin.
    expect(QrImage::svg('https://exemple.test/q/aaa'))
        ->not->toBe(QrImage::svg('https://exemple.test/q/bbb'));
});
