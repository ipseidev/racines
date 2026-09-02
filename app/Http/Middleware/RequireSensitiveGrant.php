<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\TokenType;
use App\Exceptions\Domain\TokenUnavailable;
use App\Services\Tokens\TokenService;
use App\Support\SensitiveGrant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Exige une autorisation d'acte sensible fraîche (doc 04 §12).
 *
 * En cas de doute sur le caractère sensible d'une action, elle est sensible :
 * c'est la règle de décision par défaut du bloc 03. La frontière est
 * documentée au glossaire §4 — depuis un lien `record`, les actions sur *cette*
 * histoire n'exigent rien de plus ; tout ce qui touche une autre histoire, une
 * suppression, un réglage durable ou une directive post-mortem passe par ici.
 *
 * Sans autorisation valable, on ne refuse pas : on envoie le narrateur au
 * défi par code, puis on le ramène où il allait.
 */
final readonly class RequireSensitiveGrant
{
    public function __construct(private TokenService $tokens) {}

    public function handle(Request $request, Closure $next): Response
    {
        $plain = $request->cookie(SensitiveGrant::COOKIE);

        if (is_string($plain) && $plain !== '') {
            try {
                $this->tokens->resolve($plain, TokenType::SensitiveGrant);

                return $next($request);
            } catch (TokenUnavailable) {
                // Autorisation expirée ou déjà consommée : on redemande un code.
            }
        }

        $token = $request->route('token');

        return redirect()
            ->route('narrator.otp.show', ['token' => $token])
            ->with('url.intended', $request->fullUrl())
            ->withCookie(SensitiveGrant::forget());
    }
}
