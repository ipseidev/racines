<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Twilio\Security\RequestValidator;

/**
 * Vérifie la signature de Twilio avant de lire le corps de la requête.
 *
 * Un webhook de livraison non signé serait un moyen simple de faire croire au
 * produit qu'un SMS est arrivé alors qu'il n'est jamais parti — et donc de
 * faire taire les relances du moteur de complétion.
 */
final class VerifyTwilioSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = (string) config('services.twilio.token');
        $signature = (string) $request->header('X-Twilio-Signature', '');

        abort_if($token === '' || $signature === '', 403, 'Signature Twilio absente.');

        /** @var array<string, string> $parameters */
        $parameters = $request->post();

        $valid = (new RequestValidator($token))->validate(
            $signature,
            // L'URL doit être celle que Twilio a appelée, à l'octet près.
            $request->fullUrl(),
            $parameters,
        );

        abort_unless($valid, 403, 'Signature Twilio invalide.');

        return $next($request);
    }
}
