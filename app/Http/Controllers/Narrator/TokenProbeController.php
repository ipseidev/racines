<?php

declare(strict_types=1);

namespace App\Http\Controllers\Narrator;

use App\Models\AccessToken;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Inertia\Response;

/**
 * Page de vérification d'un lien d'enregistrement.
 *
 * Elle n'existe que pour prouver, au bloc 03, qu'un lien résolu arrive bien
 * au contrôleur avec son sujet. Le bloc 04 la remplace par la vraie page
 * d'enregistrement.
 *
 * Elle n'affiche ni nom, ni téléphone, ni courriel : un lien porteur ne doit
 * pas révéler l'identité de la personne à qui il a été envoyé.
 */
final class TokenProbeController
{
    public function __invoke(Request $request): Response
    {
        $token = $request->attributes->get('access_token');
        $subject = $request->attributes->get('token_subject');

        abort_unless($token instanceof AccessToken, 404);

        return inertia('narrator/TokenProbe', [
            'tokenType' => $token->type->value,
            'subjectType' => $subject instanceof Model ? $subject->getMorphClass() : null,
            'subjectId' => $subject instanceof Model ? (string) $subject->getKey() : null,
            'scope' => $token->scope ?? [],
            'expiresAt' => $token->expires_at?->toIso8601String(),
        ]);
    }
}
