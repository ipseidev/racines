<?php

declare(strict_types=1);

namespace App\Services\Antivirus;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

/**
 * Le scanner débranché, assumé comme tel (D-12, T-216).
 *
 * Il ne contrôle rien et laisse tout passer. C'est une décision, pas un
 * défaut : le démon ClamAV n'a jamais été installé en production — la ligne
 * 48 du bloc 16 est restée à faire — et comme `ClamavScanner` refuse tout
 * fichier quand il ne joint personne, **aucune photo n'a jamais pu être
 * déposée en ligne**. Entre installer le démon et débrancher le contrôle, le
 * fondateur a tranché pour le second le 2026-09-07.
 *
 * Pourquoi un troisième driver plutôt que `ANTIVIRUS_SCANNER=fake`, qui
 * aurait débloqué les dépôts en une variable : `FakeScanner` prétend
 * reconnaître la chaîne EICAR. En production il aurait donné un scanner qui
 * répond, une sonde verte et un test qui passe, sans que rien ne rappelle
 * dans six mois que les fichiers entrent sans contrôle. Le faux et le
 * débranché sont deux états différents ; les confondre est la manière
 * ordinaire de perdre la mémoire d'un écart.
 *
 * D'où la trace. Chaque fichier admis sans contrôle part au journal, parce
 * que le jour où l'on rebranche, la question sera « qu'est-ce qui est entré
 * pendant ce temps », et elle n'a de réponse que si on l'a écrite au moment
 * du dépôt. C'est le prix du débranchement, et il est modeste : une ligne par
 * photo, sur un service où une photo est un geste rare.
 */
final class NullScanner implements Scanner
{
    public function isClean(UploadedFile $file): bool
    {
        $path = $file->getRealPath();

        /*
         * Le nom, la taille, l'empreinte — comme un refus, et pour la même
         * raison : c'est ce qui permet de répondre plus tard. Jamais le
         * contenu, un journal n'est pas un endroit où déposer un fichier dont
         * personne n'a vérifié la nature.
         *
         * `decision` porte le numéro de la décision : celui qui tombera sur
         * cette ligne dans six mois trouve R-12 sans avoir à deviner.
         */
        Log::warning('antivirus.disabled', [
            'file_name' => $file->getClientOriginalName(),
            'size' => $path === false ? null : filesize($path),
            'sha256' => $path === false ? null : hash_file('sha256', $path),
            'decision' => 'D-12',
        ]);

        return true;
    }
}
