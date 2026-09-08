<?php

declare(strict_types=1);

namespace App\Listeners;

use Illuminate\Support\Facades\Log;
use Inertia\Ssr\SsrRenderFailed;

/**
 * Un rendu serveur qui échoue se voit dans le journal.
 *
 * Sans cela, il ne se voit nulle part : Inertia retombe en silence sur le
 * rendu client, la page s'affiche, et le site reste lisible par les visiteurs
 * tout en devenant vide pour les robots. C'est arrivé en production, et
 * personne ne l'a su avant d'ouvrir la console. La page elle-même n'est pas
 * journalisée — ses propriétés pèsent quatre-vingts kilo-octets — seulement
 * ce qui permet de comprendre l'échec.
 */
final readonly class LogSsrRenderFailure
{
    public function handle(SsrRenderFailed $event): void
    {
        Log::error('Le rendu serveur a échoué ; la page est partie en rendu client.', $event->toArray());
    }
}
