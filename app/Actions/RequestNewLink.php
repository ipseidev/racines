<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\TokenType;
use App\Events\NewLinkRequested;
use App\Models\AccessToken;
use Illuminate\Support\Facades\Log;

/**
 * Enregistre la demande d'un nouveau lien d'enregistrement.
 *
 * L'action n'émet volontairement aucun jeton : c'est l'Initiateur·rice ou le
 * support qui renvoie le lien, après avoir vu la demande. Un lien qui se
 * régénérerait tout seul depuis une page publique donnerait à quiconque
 * détient l'ancienne URL le pouvoir d'en obtenir une neuve.
 */
final class RequestNewLink
{
    public function handle(AccessToken $token): void
    {
        Log::info('token.new_link_requested', [
            'token_id' => $token->id,
            'token_type' => $token->type->value,
            'subject_type' => $token->subject_type,
            'subject_id' => $token->subject_id,
        ]);

        NewLinkRequested::dispatch($token);
    }

    public function canRequestFor(AccessToken $token): bool
    {
        return $token->type === TokenType::Record && ! $token->isUsable();
    }
}
