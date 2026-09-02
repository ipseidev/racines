<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\TokenType;
use App\Models\AccessToken;
use App\Models\Story;

/**
 * Où passe la frontière de l'acte sensible.
 *
 * Une seule exception, et elle est étroite : depuis un lien
 * d'enregistrement, agir sur **l'histoire que ce lien porte** ne demande pas
 * de code. Le narrateur vient de raconter ; lui redemander de prouver qui il
 * est pour masquer ce qu'il vient de dire serait absurde, et le ferait
 * renoncer.
 *
 * Tout le reste passe par un code : une autre histoire, une suppression, un
 * réglage durable. C'est la règle de décision par défaut du bloc 03 — en cas
 * de doute, l'acte est sensible.
 */
final class SensitiveActs
{
    public static function requiresGrant(Story $target, AccessToken $current): bool
    {
        if ($current->type !== TokenType::Record) {
            return true;
        }

        return $current->subject_id !== $target->id;
    }
}
