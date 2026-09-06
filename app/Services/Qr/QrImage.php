<?php

declare(strict_types=1);

namespace App\Services\Qr;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\SvgWriter;

/**
 * Le QR d'un chapitre, en SVG.
 *
 * **SVG et non PNG** : le QR est imprimé, et une image matricielle de 28 mm
 * ressortirait crénelée sur un papier à 300 points par pouce. Le vectoriel
 * est net à toute taille, et il s'incruste dans le HTML sans fichier
 * intermédiaire — donc sans ressource distante à charger au moment du rendu,
 * ce qu'un critère de sortie du bloc interdit.
 *
 * **Niveau de correction M** : un livre se corne, se tache et prend le
 * soleil. `L` ne pardonne rien ; `H` grossit le motif au point de le rendre
 * illisible à 28 mm. `M` restaure 15 % du code, ce qui couvre une trace de
 * café.
 *
 * La marge de deux modules est la « zone de silence » : sans elle, un lecteur
 * confond le bord du code avec le texte qui l'entoure.
 */
final readonly class QrImage
{
    private const SIZE_PX = 300;

    private const MARGIN_MODULES = 2;

    public static function svg(string $url): string
    {
        // Le constructeur porte tout : la version 6 du paquet a remplacé
        // l'enchaînement de méthodes par des paramètres nommés.
        $result = (new Builder(
            writer: new SvgWriter,
            writerOptions: [SvgWriter::WRITER_OPTION_EXCLUDE_XML_DECLARATION => true],
            data: $url,
            errorCorrectionLevel: ErrorCorrectionLevel::Medium,
            size: self::SIZE_PX,
            margin: self::MARGIN_MODULES,
        ))->build();

        return $result->getString();
    }
}
