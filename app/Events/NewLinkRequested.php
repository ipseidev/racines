<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\AccessToken;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Un narrateur a demandé un nouveau lien depuis la page d'erreur.
 *
 * L'événement est levé ici et écouté au bloc 05, qui saura alerter le support
 * et l'Initiateur·rice par un vrai message. Lever l'événement dès maintenant
 * évite que le bouton de la page d'erreur soit décoratif : la demande est
 * journalisée et rattrapable à la main en attendant.
 */
final class NewLinkRequested
{
    use Dispatchable;

    public function __construct(public readonly AccessToken $token) {}
}
