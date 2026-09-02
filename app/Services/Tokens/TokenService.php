<?php

declare(strict_types=1);

namespace App\Services\Tokens;

use App\Enums\TokenIssuedReason;
use App\Enums\TokenType;
use App\Exceptions\Domain\TokenExpired;
use App\Exceptions\Domain\TokenNotFound;
use App\Exceptions\Domain\TokenRevoked;
use App\Exceptions\Domain\TokenTypeMismatch;
use App\Exceptions\Domain\TokenUsed;
use App\Models\AccessToken;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Émission, résolution et révocation de tous les liens du produit.
 *
 * Trois propriétés tiennent le reste du produit (doc 04 §12) :
 *
 *  1. **Entropie.** 32 octets tirés par `random_bytes`, encodés en base64url :
 *     256 bits, très au-delà des 128 exigés.
 *  2. **Stockage haché.** Seule l'empreinte SHA-256 est enregistrée, et c'est
 *     sur elle que porte la recherche. Aucun endroit du code ne compare un
 *     jeton en clair à autre chose qu'à une empreinte.
 *  3. **Périmètre strict.** Un jeton vaut pour un type, un sujet et une liste
 *     d'actions. Présenté à une route d'un autre type, il est refusé.
 */
final class TokenService
{
    /**
     * @param  list<string>  $scope
     */
    public function issue(
        TokenType $type,
        Model $subject,
        array $scope = [],
        ?DateTimeInterface $expiresAt = null,
        ?Model $issuedBy = null,
        TokenIssuedReason $reason = TokenIssuedReason::Initial,
        ?Model $issuedTo = null,
    ): IssuedToken {
        $plain = self::generate();

        $token = new AccessToken([
            'type' => $type,
            'scope' => $scope === [] ? null : $scope,
            'single_use' => $type->isSingleUse(),
            'issued_reason' => $reason,
        ]);

        $token->token_hash = self::hash($plain);
        $token->expires_at = self::expiryFor($type, $expiresAt);
        $token->subject()->associate($subject);

        if ($issuedBy instanceof Model) {
            $token->issuedBy()->associate($issuedBy);
        }

        if ($issuedTo instanceof Model) {
            $token->issuedTo()->associate($issuedTo);
        }

        $token->save();

        return new IssuedToken($plain, $token);
    }

    /**
     * Résout un lien présenté par un visiteur, en n'acceptant que les types
     * annoncés.
     *
     * L'ordre des vérifications est délibéré : le type d'abord, pour ne jamais
     * révéler par un message d'erreur qu'un jeton d'un autre périmètre existe.
     *
     * Plusieurs types sont admis parce que l'espace famille en a deux — un
     * lien de projet et un lien d'histoire mènent à la même page. Le
     * périmètre reste **déclaré**, jamais deviné : c'est ce qui le rend
     * vérifiable d'un coup d'œil sur le fichier de routes.
     */
    public function resolve(string $plain, TokenType $expected, TokenType ...$alsoAccepted): AccessToken
    {
        $accepted = [$expected, ...$alsoAccepted];
        $token = $this->locate($plain);

        if ($token === null) {
            throw TokenNotFound::make($expected);
        }

        if (! in_array($token->type, $accepted, true)) {
            throw TokenTypeMismatch::make($expected);
        }

        if ($token->isRevoked()) {
            throw TokenRevoked::make($token->type);
        }

        if ($token->isExpired()) {
            throw TokenExpired::make($token->type);
        }

        if ($token->isUsed()) {
            throw TokenUsed::make($token->type);
        }

        $token->use_count++;
        $token->last_used_at = now();

        if ($token->single_use) {
            $token->used_at = now();
        }

        $token->save();

        return $token;
    }

    /**
     * Retrouve un jeton sans vérifier sa validité.
     *
     * Réservé aux cas où l'on doit agir *parce que* le lien est mort : la
     * demande d'un nouveau lien depuis la page d'erreur, par exemple.
     */
    public function locate(string $plain): ?AccessToken
    {
        return AccessToken::query()->where('token_hash', self::hash($plain))->first();
    }

    public function revoke(AccessToken $token, string $reason): void
    {
        if ($token->isRevoked()) {
            return;
        }

        $token->revoked_at = now();
        $token->save();

        Log::info('token.revoked', [
            'token_id' => $token->id,
            'token_type' => $token->type->value,
            'reason' => $reason,
        ]);
    }

    /**
     * Émet un remplaçant et révoque l'ancien, en gardant le lien entre les
     * deux : le support doit pouvoir raconter l'histoire d'un lien.
     */
    public function rotate(AccessToken $token, TokenIssuedReason $reason = TokenIssuedReason::Rotation): IssuedToken
    {
        return DB::transaction(function () use ($token, $reason): IssuedToken {
            $subject = $token->subject;

            if (! $subject instanceof Model) {
                throw TokenNotFound::make($token->type);
            }

            $issued = $this->issue(
                $token->type,
                $subject,
                $token->scope ?? [],
                issuedBy: $token->issuedBy instanceof Model ? $token->issuedBy : null,
                reason: $reason,
            );

            $this->revoke($token, $reason->value);

            $token->replaced_by_token_id = $issued->token->id;
            $token->save();

            return $issued;
        });
    }

    /**
     * Révoque d'un coup tous les jetons d'un type pour un sujet.
     *
     * C'est ce qui ferme un lien d'enregistrement au moment de la validation :
     * une histoire validée ne se réenregistre pas par l'ancien lien.
     */
    public function revokeAllFor(Model $subject, TokenType $type, string $reason): int
    {
        $tokens = AccessToken::query()
            ->where('subject_type', $subject->getMorphClass())
            ->where('subject_id', (string) $subject->getKey())
            ->where('type', $type->value)
            ->whereNull('revoked_at')
            ->get();

        foreach ($tokens as $token) {
            $this->revoke($token, $reason);
        }

        return $tokens->count();
    }

    /**
     * 32 octets aléatoires en base64url, sans remplissage : 43 caractères.
     */
    public static function generate(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }

    public static function hash(string $plain): string
    {
        return hash('sha256', $plain);
    }

    private static function expiryFor(TokenType $type, ?DateTimeInterface $explicit): ?CarbonImmutable
    {
        if ($explicit !== null) {
            return CarbonImmutable::instance($explicit);
        }

        $ttl = $type->ttl();

        return $ttl === null ? null : now()->add($ttl);
    }
}
