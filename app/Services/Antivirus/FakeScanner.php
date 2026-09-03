<?php

declare(strict_types=1);

namespace App\Services\Antivirus;

use Illuminate\Http\UploadedFile;

/**
 * Le scanner de l'intégration continue et des tests.
 *
 * Il reconnaît la **chaîne EICAR**, le fichier de test standard des
 * antivirus : inoffensif, et reconnu par tous les vrais scanners. C'est ce
 * qui permet d'éprouver le chemin de refus sans mettre de virus dans un
 * dépôt, et sans faire dépendre la CI d'un démon d'un gigaoctet.
 *
 * Il ne prétend rien détecter d'autre. Un faux qui ferait semblant de scanner
 * donnerait une confiance qu'il ne mérite pas — d'où le nom.
 */
final class FakeScanner implements Scanner
{
    /**
     * La signature de test EICAR, coupée en deux dans le code source.
     *
     * Coupée pour une raison amusante et réelle : un fichier source qui
     * contient la chaîne complète est détecté comme un virus par les
     * antivirus des postes de travail, et le dépôt devient impossible à
     * cloner sur une machine d'entreprise.
     */
    private const SIGNATURE_HEAD = 'X5O!P%@AP[4\PZX54(P^)7CC)7}$EICAR-STANDARD-';

    private const SIGNATURE_TAIL = 'ANTIVIRUS-TEST-FILE!$H+H*';

    public function isClean(UploadedFile $file): bool
    {
        $path = $file->getRealPath();

        if ($path === false) {
            return false;
        }

        $contents = (string) file_get_contents($path);
        $clean = ! str_contains($contents, self::SIGNATURE_HEAD.self::SIGNATURE_TAIL);

        if (! $clean) {
            Scanners::logRejection($file, 'fake');
        }

        return $clean;
    }
}
