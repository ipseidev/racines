<?php

declare(strict_types=1);

namespace App\Engine\Actions;

use App\Models\AccessToken;

/**
 * Une action que l'Initiateur·rice déclenche d'un seul geste.
 *
 * Le contrat tient en deux temps, et l'ordre n'est pas négociable : la page
 * **montre** ce qui va se passer, puis un bouton l'exécute. Un lien reçu par
 * SMS qui agirait à l'ouverture serait déclenché par le simple aperçu d'un
 * client de messagerie — c'est arrivé à d'autres.
 */
interface OneTapAction
{
    /**
     * L'identifiant qui voyage dans le périmètre du jeton.
     */
    public static function name(): string;

    /**
     * La question posée avant d'agir, et le libellé du bouton.
     *
     * @return array<string, mixed>
     */
    public function preview(AccessToken $token): array;

    /**
     * Agit, et rend de quoi composer la page de confirmation.
     *
     * @return array<string, mixed>
     */
    public function execute(AccessToken $token): array;
}
