<?php

declare(strict_types=1);

namespace App\Services\Pdf;

/**
 * Combien de pages fait ce PDF.
 *
 * Le compte sert à deux choses très concrètes : dire à la famille où elle en
 * est de la matière (R-6 exige un nombre de pages, pas un nombre
 * d'histoires), et donner à l'imprimeur une pagination avant le devis.
 *
 * **Lu dans le fichier, sans binaire externe.** `pdfinfo` de poppler serait
 * plus robuste, mais il faudrait l'ajouter à l'image de développement, à
 * l'intégration continue et au serveur — trois endroits, pour compter un
 * entier. Le PDF porte l'information dans son arbre de pages, et on la lit là
 * où elle est.
 *
 * Deux passes, dans cet ordre : `/Count` du nœud racine, qui est
 * l'information faisant autorité ; à défaut, le nombre d'objets `/Type /Page`.
 * La seconde se trompe si le producteur compresse son arbre dans un flux
 * d'objets — ce que Chromium ne fait pas — d'où l'ordre.
 */
final readonly class PdfPageCount
{
    public static function of(string $path): int
    {
        if (! is_file($path)) {
            return 0;
        }

        $bytes = (string) file_get_contents($path);

        $counts = [];

        if (preg_match_all('/\/Type\s*\/Pages\b[^>]*?\/Count\s+(\d+)/s', $bytes, $matches) > 0) {
            $counts = array_map('intval', $matches[1]);
        }

        // Le plus grand : un arbre de pages peut avoir des nœuds
        // intermédiaires, et chacun porte le compte de **sa** branche. La
        // racine est celle qui les couvre toutes.
        $fromTree = $counts === [] ? 0 : max($counts);

        if ($fromTree > 0) {
            return $fromTree;
        }

        return (int) preg_match_all('/\/Type\s*\/Page[^s]/s', $bytes);
    }
}
