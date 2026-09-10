<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\ProjectStatus;
use App\Jobs\SendGiftInvitation;
use App\Models\Project;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * Le filet des cadeaux programmés.
 *
 * `FulfillOrder` pousse l'invitation en file avec un report jusqu'à l'heure
 * choisie, et c'est la voie normale. Mais un report n'est **pas** une
 * promesse : sur une file `sync`, `deferred` ou `background`, `->delay()` est
 * ignoré et le travail s'exécute dans la requête qui le pousse — le cadeau
 * part à la seconde du paiement. C'est arrivé en production le 10 septembre
 * 2026, et rien ne pouvait le voir : la suite tourne elle-même sur `sync`,
 * où le report n'existe pas (T-239).
 *
 * Depuis, l'envoi refuse de partir avant l'heure. Cette commande est l'autre
 * moitié : elle reprend, à la minute, ce que la garde a refusé. Les deux
 * ensemble font une garantie que ni un réglage d'environnement ni un ouvrier
 * mort ne peuvent défaire — même leçon que le filet de la transcription, et
 * que l'`afterCommit` du tunnel : la justesse d'un achat ne doit pas dépendre
 * d'un réglage qu'on peut basculer.
 *
 * Toutes les minutes, et non toutes les cinq : un cadeau annoncé pour dix
 * heures ne doit pas arriver à dix heures cinq, et la requête est une seule
 * ligne indexée sur un statut.
 *
 * Elle ne rattrape que les projets **en brouillon** : c'est exactement l'état
 * d'un cadeau dont l'invitation n'est jamais partie. Un projet remboursé
 * passe en `cancelled` et sort donc du filet, sans qu'il ait à le savoir.
 */
#[AsCommand(name: 'gifts:dispatch-due', description: 'Envoie les invitations de cadeau dont l’heure est venue')]
final class DispatchDueGifts extends Command
{
    /** @var string */
    protected $signature = 'gifts:dispatch-due';

    /** @var string */
    protected $description = 'Envoie les invitations de cadeau dont l’heure est venue';

    public function handle(): int
    {
        $due = Project::query()
            ->where('status', ProjectStatus::Draft->value)
            ->whereNotNull('gift_send_at')
            ->where('gift_send_at', '<=', now())
            ->whereNull('gift_sent_at')
            ->whereNull('accepted_at')
            ->whereNull('refused_at')
            ->pluck('id');

        foreach ($due as $id) {
            // Le travail garde ses gardes : c'est lui qui décide de partir ou
            // non, ici comme quand la file le réveille à l'heure dite.
            SendGiftInvitation::dispatch((string) $id, 1);
        }

        if ($due->isNotEmpty()) {
            Log::info('gift.due_dispatched', ['count' => $due->count()]);
        }

        $this->components->info(sprintf('%d cadeau(x) dû(s).', $due->count()));

        return self::SUCCESS;
    }
}
