<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasTranslatedLabel;
use Carbon\CarbonInterval;

/**
 * Types de jetons porteurs (glossaire §4 et §8).
 *
 * Un jeton = un type = un périmètre. Il n'existe pas de jeton polyvalent : le
 * lien d'enregistrement ne lit rien, le lien d'écoute n'écrit rien, et le
 * préfixe d'URL suffit à savoir ce qu'un lien peut faire.
 */
enum TokenType: string
{
    use HasTranslatedLabel;

    case Record = 'record';
    case ListenProject = 'listen_project';
    case ListenStory = 'listen_story';
    case Qr = 'qr';
    case Invitation = 'invitation';
    case Action = 'action';
    case Export = 'export';
    case NarratorSpace = 'narrator_space';
    case SensitiveGrant = 'sensitive_grant';

    /**
     * Durée de vie par défaut, lue dans `config('product.tokens')`.
     *
     * `null` pour le QR imprimé : le papier ne se met pas à jour, donc le
     * jeton n'expire pas techniquement. L'engagement de durée est publié
     * (R-10) et la révocation par la famille reste possible (D-8).
     */
    public function ttl(): ?CarbonInterval
    {
        return match ($this) {
            self::Record => CarbonInterval::days($this->setting('record_days')),
            self::ListenProject => CarbonInterval::months($this->setting('listen_project_months')),
            self::ListenStory => CarbonInterval::days($this->setting('listen_story_days')),
            self::Qr => null,
            self::Invitation => CarbonInterval::days($this->setting('invitation_days')),
            self::Action => CarbonInterval::days($this->setting('action_days')),
            self::Export => CarbonInterval::days($this->setting('export_days')),
            self::NarratorSpace => CarbonInterval::days($this->setting('narrator_space_days')),
            self::SensitiveGrant => CarbonInterval::minutes($this->setting('sensitive_grant_minutes')),
        };
    }

    /**
     * Un jeton à usage unique cesse de valoir dès qu'il a servi : une action
     * en un tap ne se rejoue pas, une autorisation d'acte sensible non plus.
     */
    public function isSingleUse(): bool
    {
        return in_array($this, [self::Action, self::SensitiveGrant], true);
    }

    /**
     * Préfixe d'URL du type (glossaire §8). `null` pour l'autorisation d'acte
     * sensible, qui voyage dans un cookie et jamais dans un lien.
     */
    public function urlPrefix(): ?string
    {
        return match ($this) {
            self::Record => 'r',
            self::NarratorSpace => 'n',
            self::ListenProject, self::ListenStory => 'l',
            self::Qr => 'q',
            self::Invitation => 'i',
            self::Action => 'a',
            self::Export => 'x',
            self::SensitiveGrant => null,
        };
    }

    /**
     * Espace d'interface auquel le jeton appartient : décide de la page
     * d'erreur amicale servie quand le lien n'est plus valable.
     */
    public function space(): ?string
    {
        return match ($this) {
            self::Record, self::NarratorSpace, self::Invitation => 'narrator',
            self::ListenProject, self::ListenStory, self::Qr, self::Action, self::Export => 'family',
            self::SensitiveGrant => null,
        };
    }

    public static function fromUrlPrefix(string $prefix): ?self
    {
        return match ($prefix) {
            'r' => self::Record,
            'n' => self::NarratorSpace,
            'l' => self::ListenProject,
            'q' => self::Qr,
            'i' => self::Invitation,
            'a' => self::Action,
            'x' => self::Export,
            default => null,
        };
    }

    /** @return list<string> */
    public static function urlPrefixes(): array
    {
        return ['r', 'n', 'l', 'q', 'i', 'a', 'x'];
    }

    private function setting(string $key): int
    {
        $tokens = config('product.tokens');

        return is_array($tokens) && is_int($tokens[$key] ?? null) ? $tokens[$key] : 1;
    }
}
