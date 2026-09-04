<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\StoresDatesWithOffset;
use Carbon\CarbonImmutable;
use Database\Factories\LeadFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\Notifiable;

/**
 * Une personne qui a laissé son adresse contre un code de réduction (T-141).
 *
 * Une ligne par adresse, et un seul code par ligne : quelqu'un qui redemande
 * reçoit le même code, ou un neuf si le sien a expiré. Le code ne sert
 * qu'une fois ; `code_used_at` et `order_id` disent quand et pour quelle
 * commande.
 *
 * @property string $id
 * @property string $email
 * @property string $email_hash
 * @property string $discount_code
 * @property int $discount_percent
 * @property string $source
 * @property CarbonImmutable|null $news_opted_in_at
 * @property string|null $consent_text_version
 * @property string|null $ip_hash
 * @property string|null $user_agent
 * @property CarbonImmutable $code_expires_at
 * @property CarbonImmutable|null $code_used_at
 * @property string|null $order_id
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
final class Lead extends Model
{
    /** @use HasFactory<LeadFactory> */
    use HasFactory, HasUuids, Notifiable, StoresDatesWithOffset;

    public const SOURCE_LANDING = 'landing_popup';

    /** Un an, comme le leader l'annonce : « valid all year long ». */
    public const CODE_LIFETIME_DAYS = 365;

    /**
     * Sans 0, O, 1 ni I : un code se lit sur un téléphone et se recopie à la
     * main, et ces quatre signes se confondent dans la plupart des polices.
     */
    private const CODE_ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    private const CODE_LENGTH = 8;

    /** @var list<string> */
    protected $fillable = [
        'email', 'source', 'discount_percent', 'news_opted_in_at',
        'consent_text_version', 'ip_hash', 'user_agent', 'code_expires_at',
    ];

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** Là où le canal courriel écrit : l'adresse déchiffrée. */
    public function routeNotificationForMail(): string
    {
        return $this->email;
    }

    public function codeUsable(): bool
    {
        return $this->code_used_at === null && $this->code_expires_at->isFuture();
    }

    /** `usable`, `used` ou `expired` : la raison d'un refus, pour la dire à l'acheteur. */
    public function codeStatus(): string
    {
        if ($this->code_used_at !== null) {
            return 'used';
        }

        return $this->code_expires_at->isFuture() ? 'usable' : 'expired';
    }

    public static function hashEmail(string $email): string
    {
        return hash('sha256', mb_strtolower(trim($email)));
    }

    /**
     * Le code tel qu'on le compare : en capitales, sans espaces, avec son
     * tiret au milieu. « abcd efgh » et « ABCD-EFGH » sont le même code.
     */
    public static function normalizeCode(string $code): string
    {
        $clean = preg_replace('/[^A-Z0-9]/', '', mb_strtoupper($code)) ?? '';

        if (mb_strlen($clean) !== self::CODE_LENGTH) {
            return $clean;
        }

        return substr($clean, 0, 4).'-'.substr($clean, 4);
    }

    /** Un code neuf, unique en base. */
    public static function generateCode(): string
    {
        do {
            $raw = '';

            for ($i = 0; $i < self::CODE_LENGTH; $i++) {
                $raw .= self::CODE_ALPHABET[random_int(0, strlen(self::CODE_ALPHABET) - 1)];
            }

            $code = self::normalizeCode($raw);
        } while (self::query()->where('discount_code', $code)->exists());

        return $code;
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'email' => 'encrypted',
            'discount_percent' => 'integer',
            'news_opted_in_at' => 'immutable_datetime',
            'code_expires_at' => 'immutable_datetime',
            'code_used_at' => 'immutable_datetime',
        ];
    }
}
