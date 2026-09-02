<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\Channel;

/**
 * Une notification dont on veut suivre la livraison.
 *
 * Sans ce suivi, le moteur de complétion (bloc 09) ne peut pas distinguer
 * « lien non ouvert » de « SMS jamais reçu » — et c'est toute la différence
 * entre relancer un narrateur et lui adresser un reproche injuste.
 */
interface TracksDelivery
{
    /**
     * Clé d'idempotence : deux exécutions du même envoi ne doivent produire
     * qu'un message.
     */
    public function dedupeKey(Channel $channel): string;

    /** Nom du gabarit, pour les statistiques et le support. */
    public function template(): string;

    public function projectId(): ?string;

    /**
     * Contexte du message, **sans donnée personnelle en clair**.
     *
     * @return array<string, mixed>
     */
    public function deliveryPayload(): array;
}
