<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Mandate;
use Illuminate\Support\Facades\Log;

/**
 * Retire un mandat, tout de suite.
 *
 * Aucun délai, aucune grâce : un mandat révoqué qui vaudrait encore une
 * minute vaudrait encore une histoire. La ligne reste, avec sa date de
 * révocation — savoir qu'un mandat a existé fait partie de l'audit.
 */
final class RevokeMandate
{
    public function handle(Mandate $mandate): Mandate
    {
        if ($mandate->revoked_at !== null) {
            return $mandate;
        }

        $mandate->revoked_at = now();
        $mandate->save();

        Log::warning('mandate.revoked', [
            'mandate_id' => $mandate->id,
            'narrator_id' => $mandate->narrator_id,
        ]);

        return $mandate;
    }
}
