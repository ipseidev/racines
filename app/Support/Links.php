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
     * Le domaine sur lequel les routes à jeton sont servies, ou `null` pour
     * n'en contraindre aucun.
     *
     * En production, le domaine court est contraignant : c'est lui qu'on
     * annonce dès l'invitation, et le servir ailleurs affaiblirait la seule
     * défense anti-hameçonnage du produit (doc 04 §9).
     *
     * Ailleurs, aucune contrainte — et ce n'est pas un relâchement, c'est ce
     * qui rend les vérifications jouables. Trois blocs se vérifient sur un
     * appareil qui n'est pas la machine de développement : le spike navigateur
     * (04), l'écoute famille sur un vrai téléphone (08), la photo HEIC (12).
     * L'adresse vue par l'appareil — l'IP du réseau local, un tunnel — n'est
     * jamais celle du domaine court, et le lien à jeton y recevait un **404**
     * pendant que la page d'accueil répondait 200 (T-156). Aligner
     * l'environnement sur l'appareil cassait en retour la suite bout en bout,
     * qui attaque `localhost` : les deux doivent tenir en même temps.
     *
     * Le jeton reste la seule pièce d'authentification, ici comme là-bas. Le
     * domaine n'a jamais été une garde, il est une promesse d'adresse.
     */
    public static function routeDomain(): ?string
    {
        if (! app()->isProduction()) {
            return null;
        }

        $domain = (string) config('brand.links_domain');

        return $domain === '' ? null : $domain;
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
