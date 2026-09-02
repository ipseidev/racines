<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\TokenType;
use InvalidArgumentException;

/**
 * Construction des liens envoyés aux narrateurs et aux proches.
 *
 * Un seul endroit décide de la forme d'une URL à jeton, pour trois raisons :
 * le domaine court est éditable dans l'administration (bloc 01), aucune donnée
 * personnelle ne doit jamais s'y glisser (doc 04 §12), et le domaine annoncé
 * dès l'invitation doit rester le même partout (doc 04 §9, anti-phishing).
 */
final class Links
{
    public static function for(TokenType $type, string $plain): string
    {
        $prefix = $type->urlPrefix();

        if ($prefix === null) {
            throw new InvalidArgumentException("Token type [{$type->value}] never travels in a link.");
        }

        return self::base().'/'.$prefix.'/'.$plain;
    }

    public static function record(string $plain): string
    {
        return self::for(TokenType::Record, $plain);
    }

    public static function narratorSpace(string $plain): string
    {
        return self::for(TokenType::NarratorSpace, $plain);
    }

    public static function listen(string $plain): string
    {
        return self::for(TokenType::ListenProject, $plain);
    }

    /**
     * Le lien direct vers **une** histoire.
     *
     * Même préfixe que le lien de projet — les deux vivent sur `/l` — parce
     * qu'un proche n'a pas à distinguer deux formes d'adresse : il clique, il
     * écoute.
     */
    public static function listenStory(string $plain): string
    {
        return self::for(TokenType::ListenStory, $plain);
    }

    public static function qr(string $plain): string
    {
        return self::for(TokenType::Qr, $plain);
    }

    public static function invitation(string $plain): string
    {
        return self::for(TokenType::Invitation, $plain);
    }

    public static function action(string $plain): string
    {
        return self::for(TokenType::Action, $plain);
    }

    public static function export(string $plain): string
    {
        return self::for(TokenType::Export, $plain);
    }

    /**
     * Racine des liens : `https://{domaine court}`.
     *
     * Quand le domaine court est celui de l'application — c'est le cas en
     * local — on reprend son schéma et son port, sans quoi le lien serait
     * inouvrable sur la machine de développement (ports décalés, T-34).
     */
    private static function base(): string
    {
        $domain = Brand::linksDomain();
        $parts = parse_url((string) config('app.url'));

        if (is_array($parts) && ($parts['host'] ?? null) === $domain) {
            $scheme = is_string($parts['scheme'] ?? null) ? $parts['scheme'] : 'https';
            $port = isset($parts['port']) ? ':'.$parts['port'] : '';

            return "{$scheme}://{$domain}{$port}";
        }

        return "https://{$domain}";
    }
}
