<?php

declare(strict_types=1);

use App\Services\Pdf\PdfPageCount;

/**
 * Compter les pages d'un PDF sans binaire externe.
 *
 * Le compte n'est pas cosmétique : R-6 exige un nombre de **pages** et non un
 * nombre d'histoires, et c'est ce critère qui est le verrou réel — à 280 mots
 * la page, les 12 000 mots du référentiel ne font que 48 pages.
 */
it('lit le compte dans l’arbre de pages', function (): void {
    $path = tempnam(sys_get_temp_dir(), 'pdf');
    file_put_contents($path, "%PDF-1.4\n2 0 obj<</Type/Pages/Kids[3 0 R 4 0 R]/Count 2>>endobj\n%%EOF");

    expect(PdfPageCount::of($path))->toBe(2);
});

it('retient la racine quand l’arbre a des nœuds intermédiaires', function (): void {
    $path = tempnam(sys_get_temp_dir(), 'pdf');
    file_put_contents($path, "%PDF-1.4\n"
        ."2 0 obj<</Type/Pages/Kids[5 0 R 6 0 R]/Count 60>>endobj\n"
        ."5 0 obj<</Type/Pages/Kids[7 0 R]/Count 30>>endobj\n"
        .'%%EOF');

    // Un nœud intermédiaire porte le compte de sa branche : prendre le
    // premier venu annoncerait un livre deux fois trop court.
    expect(PdfPageCount::of($path))->toBe(60);
});

it('retombe sur le décompte des objets page', function (): void {
    $path = tempnam(sys_get_temp_dir(), 'pdf');
    file_put_contents($path, "%PDF-1.4\n3 0 obj<</Type/Page/Parent 2 0 R>>endobj\n4 0 obj<</Type/Page/Parent 2 0 R>>endobj\n%%EOF");

    expect(PdfPageCount::of($path))->toBe(2);
});

it('rend zéro pour un fichier absent', function (): void {
    expect(PdfPageCount::of('/tmp/rien-du-tout.pdf'))->toBe(0);
});
