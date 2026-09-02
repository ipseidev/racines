<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\TokenType;
use App\Exceptions\Domain\TokenNotFound;
use App\Services\Tokens\TokenService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Résout le jeton d'une route et le met à disposition du contrôleur.
 *
 * Le type attendu est déclaré sur la route (`resolve.token:record`) : c'est ce
 * qui rend le périmètre strict vérifiable d'un coup d'œil sur le fichier de
 * routes, et non enfoui dans un contrôleur.
 *
 * Le contrôleur ne reçoit jamais le jeton en clair : il lit `access_token` et
 * `token_subject` dans les attributs de la requête.
 */
final readonly class ResolveAccessToken
{
    public function __construct(private TokenService $tokens) {}

    /**
     * @param  string  $types  Un ou plusieurs types, séparés par `|`. L'espace
     *                         famille en accepte deux : un lien de projet et
     *                         un lien d'histoire mènent à la même page, et le
     *                         périmètre reste déclaré sur la route.
     */
    /**
     * @param  string  $mode  `peek` vérifie le lien **sans le consommer** : la
     *                        page de confirmation d'une action en un tap doit
     *                        pouvoir montrer un lien à usage unique sans le
     *                        griller avant que le bouton soit touché.
     */
    public function handle(Request $request, Closure $next, string $types, string $mode = 'consume'): Response
    {
        $expected = array_map(
            fn (string $type): TokenType => TokenType::tryFrom($type)
                ?? throw new \InvalidArgumentException("Unknown token type [{$type}] on route."),
            explode('|', $types),
        );

        $plain = $request->route('token');

        if (! is_string($plain)) {
            throw TokenNotFound::make($expected[0]);
        }

        $token = $mode === 'peek'
            ? $this->tokens->peek($plain, ...$expected)
            : $this->tokens->resolve($plain, ...$expected);

        $request->attributes->set('access_token', $token);
        $request->attributes->set('token_subject', $token->subject);

        return $next($request);
    }
}
