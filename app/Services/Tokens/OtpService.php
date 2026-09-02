<?php

declare(strict_types=1);

namespace App\Services\Tokens;

use App\Enums\Channel;
use App\Enums\OtpPurpose;
use App\Exceptions\Domain\OtpExpired;
use App\Exceptions\Domain\OtpInvalid;
use App\Exceptions\Domain\OtpLocked;
use App\Exceptions\Domain\OtpNotDeliverable;
use App\Exceptions\Domain\OtpThrottled;
use App\Models\FamilyMember;
use App\Models\Narrator;
use App\Models\OtpChallenge;
use App\Notifications\OtpCodeNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Codes à usage unique pour les actes sensibles (doc 04 §12).
 *
 * Le narrateur n'a pas de compte : c'est ce code, envoyé sur un canal qu'il
 * possède déjà, qui prouve que c'est bien lui avant une suppression, un
 * retrait ou une directive post-mortem. Trois propriétés le rendent solide :
 *
 *  - le code n'est jamais stocké, seule son empreinte salée par le défi ;
 *  - cinq essais et le défi se verrouille, le bon code y compris ;
 *  - trois codes par heure et par sujet, pas un de plus.
 */
final class OtpService
{
    public function __construct(private readonly TokenService $tokens) {}

    public function challenge(
        Narrator|FamilyMember $subject,
        OtpPurpose $purpose,
        Channel $channel,
    ): OtpChallenge {
        $destination = self::destinationFor($subject, $channel);

        if ($destination === null) {
            throw OtpNotDeliverable::on($channel->value);
        }

        $this->guardChallengeRate($subject);

        return DB::transaction(function () use ($subject, $purpose, $channel, $destination): OtpChallenge {
            // Un nouveau code périme les précédents : deux codes valides en
            // même temps doublent la surface d'attaque pour rien.
            $this->expirePendingChallenges($subject);

            $code = self::generateCode();
            $id = (string) Str::uuid7();

            $challenge = new OtpChallenge([
                'purpose' => $purpose,
                'channel' => $channel,
                'sent_to_masked' => self::mask($destination),
                'expires_at' => now()->addMinutes(self::setting('ttl_minutes')),
            ]);

            $challenge->id = $id;
            $challenge->code_hash = self::hashCode($code, $id);
            $challenge->{$subject instanceof Narrator ? 'narrator_id' : 'family_member_id'} = $subject->getKey();
            $challenge->save();

            $subject->notify(new OtpCodeNotification($code, $channel, self::setting('ttl_minutes')));

            Log::info('otp.challenged', [
                'challenge_id' => $challenge->id,
                'purpose' => $purpose->value,
                'channel' => $channel->value,
                'to_masked' => $challenge->sent_to_masked,
            ]);

            return $challenge;
        });
    }

    /**
     * Vérifie un code et, si c'est le bon, émet le jeton correspondant.
     *
     * L'ordre compte : verrou d'abord, expiration ensuite, code en dernier.
     * Un défi verrouillé refuse même le bon code, sinon le verrou ne servirait
     * qu'à ralentir l'attaquant qui a déjà trouvé.
     */
    public function verify(OtpChallenge $challenge, string $code): IssuedToken
    {
        if ($challenge->isLocked()) {
            throw OtpLocked::forMinutes(self::setting('lockout_minutes'));
        }

        if ($challenge->isVerified()) {
            throw OtpInvalid::make();
        }

        if ($challenge->isExpired()) {
            throw OtpExpired::make();
        }

        if (! hash_equals($challenge->code_hash, self::hashCode($code, $challenge->id))) {
            $this->countFailedAttempt($challenge);

            throw OtpInvalid::make();
        }

        return DB::transaction(function () use ($challenge): IssuedToken {
            $challenge->verified_at = now();
            $challenge->save();

            $type = $challenge->purpose->grants();

            Log::info('otp.verified', [
                'challenge_id' => $challenge->id,
                'granted' => $type->value,
            ]);

            return $this->tokens->issue($type, $challenge->subject());
        });
    }

    /**
     * Empreinte du code, salée par l'identifiant du défi.
     *
     * Deux défis portant le même code n'ont donc pas la même empreinte : une
     * lecture de la base ne permet pas de reconnaître un code par son
     * empreinte, ni de rejouer celui d'un autre défi.
     */
    public static function hashCode(string $code, string $challengeId): string
    {
        return hash('sha256', $code.':'.$challengeId);
    }

    public static function generateCode(): string
    {
        $length = self::setting('length');

        return str_pad((string) random_int(0, (10 ** $length) - 1), $length, '0', STR_PAD_LEFT);
    }

    /**
     * Garde de quoi dire « code envoyé au 06 •• •• •• 12 » sans conserver la
     * coordonnée complète une seconde fois.
     */
    public static function mask(string $destination): string
    {
        if (str_contains($destination, '@')) {
            [$user, $domain] = explode('@', $destination, 2);

            return Str::mask($user, '•', 1).'@'.$domain;
        }

        return Str::mask($destination, '•', 4, max(strlen($destination) - 6, 0));
    }

    private function countFailedAttempt(OtpChallenge $challenge): void
    {
        $challenge->attempts++;

        if ($challenge->attempts >= self::setting('max_attempts')) {
            $challenge->locked_until = now()->addMinutes(self::setting('lockout_minutes'));
        }

        $challenge->save();
    }

    private function guardChallengeRate(Narrator|FamilyMember $subject): void
    {
        $column = $subject instanceof Narrator ? 'narrator_id' : 'family_member_id';

        $recent = OtpChallenge::query()
            ->where($column, $subject->getKey())
            ->where('created_at', '>=', now()->subHour())
            ->count();

        if ($recent >= self::setting('max_challenges_per_hour')) {
            throw OtpThrottled::make();
        }
    }

    private function expirePendingChallenges(Narrator|FamilyMember $subject): void
    {
        $column = $subject instanceof Narrator ? 'narrator_id' : 'family_member_id';

        OtpChallenge::query()
            ->where($column, $subject->getKey())
            ->whereNull('verified_at')
            ->where('expires_at', '>', now())
            ->update(['expires_at' => now()->subSecond()]);
    }

    private static function destinationFor(Narrator|FamilyMember $subject, Channel $channel): ?string
    {
        $destination = match ($channel) {
            Channel::Sms => $subject->phone_e164,
            Channel::Email => $subject->email,
            Channel::PhoneOperator => null,
        };

        return $destination === '' ? null : $destination;
    }

    private static function setting(string $key): int
    {
        $otp = config('product.otp');

        return is_array($otp) && is_int($otp[$key] ?? null) ? $otp[$key] : 6;
    }
}
