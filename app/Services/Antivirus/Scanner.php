<?php

declare(strict_types=1);

namespace App\Services\Antivirus;

use Illuminate\Http\UploadedFile;

/**
 * Le contrôle antivirus d'un fichier déposé.
 *
 * Un port, comme l'ASR, le LLM et le paiement. Ici la raison est double :
 * ClamAV parle un protocole de socket que rien dans Laravel n'intercepte — un
 * test qui aurait oublié un doublon aurait attendu une connexion pendant
 * trente secondes avant d'échouer sans dire pourquoi — et le démon n'existe
 * pas en intégration continue.
 *
 * Pourquoi scanner du tout : les photos arrivent des téléphones de toute une
 * famille, y compris d'un cousin dont l'appareil est infecté. Ce qu'on stocke
 * est ensuite servi aux autres, et un fichier vérolé qui traverse notre
 * stockage devient notre responsabilité.
 */
interface Scanner
{
    /**
     * Le fichier est-il sain ?
     *
     * Rend un booléen et non un rapport : l'appelant n'a qu'une décision à
     * prendre, et un rapport détaillé invite à des exceptions au cas par cas.
     * Le refus est journalisé par l'implémentation.
     */
    public function isClean(UploadedFile $file): bool;
}
