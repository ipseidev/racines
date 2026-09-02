<?php

declare(strict_types=1);

namespace App\Http\Controllers\Narrator;

use Inertia\Response;

/**
 * L'écran de remerciement, **sans jeton**.
 *
 * Il en faut un parce que valider une histoire révoque son lien
 * d'enregistrement : renvoyer le narrateur sur la page qu'il vient de
 * quitter afficherait « ce lien n'est plus valable » juste après un geste
 * réussi, ce qui est la pire chose à lire à ce moment-là.
 *
 * Sans jeton, la page ne peut rien montrer de personnel — et c'est
 * exactement ce qu'on veut d'une URL qui n'en porte aucun.
 */
final class ThanksController
{
    public function __invoke(): Response
    {
        return inertia('narrator/Thanks', [
            'message' => session('thanks'),
        ]);
    }
}
