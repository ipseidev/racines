<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Svix\Webhook;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Vérifie la signature Svix des webhooks Resend.
 *
 * Même raison que pour Twilio : sans signature, n'importe qui peut déclarer
 * qu'un courriel a été reçu.
 */
final class VerifyResendSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = (string) config('services.resend.webhook_secret');

        abort_if($secret === '', 403, 'Secret Resend absent.');

        try {
            (new Webhook($secret))->verify($request->getContent(), [
                'svix-id' => (string) $request->header('svix-id', ''),
                'svix-timestamp' => (string) $request->header('svix-timestamp', ''),
                'svix-signature' => (string) $request->header('svix-signature', ''),
            ]);
        } catch (Throwable) {
            abort(403, 'Signature Resend invalide.');
        }

        return $next($request);
    }
}
