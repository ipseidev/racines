<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Channel;
use App\Enums\OtpPurpose;
use Carbon\CarbonImmutable;
use Database\Factories\OtpChallengeFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un code à usage unique en attente de vérification.
 *
 * Le code n'est pas stocké : `code_hash` est l'empreinte de `code:id`, donc
 * deux défis portant le même code n'ont pas la même empreinte. Une lecture de
 * la base ne permet pas de rejouer un code.
 *
 * @property string $id
 * @property string|null $narrator_id
 * @property string|null $family_member_id
 * @property OtpPurpose $purpose
 * @property string $code_hash
 * @property Channel $channel
 * @property string $sent_to_masked
 * @property int $attempts
 * @property CarbonImmutable $expires_at
 * @property CarbonImmutable|null $verified_at
 * @property CarbonImmutable|null $locked_until
 * @property CarbonImmutable|null $created_at
 */
final class OtpChallenge extends Model
{
    /** @use HasFactory<OtpChallengeFactory> */
    use HasFactory, HasUuids;

    public const UPDATED_AT = null;

    /** @var array<string, mixed> */
    protected $attributes = ['attempts' => 0];

    /** @var list<string> */
    protected $fillable = ['purpose', 'channel', 'sent_to_masked', 'expires_at'];

    /** @return BelongsTo<Narrator, $this> */
    public function narrator(): BelongsTo
    {
        return $this->belongsTo(Narrator::class);
    }

    /** @return BelongsTo<FamilyMember, $this> */
    public function familyMember(): BelongsTo
    {
        return $this->belongsTo(FamilyMember::class);
    }

    public function subject(): Narrator|FamilyMember
    {
        $subject = $this->narrator ?? $this->familyMember;

        // La contrainte `otp_challenges_one_subject_check` garantit qu'il y en
        // a exactement un ; ce garde-fou n'est là que pour le typage.
        return $subject ?? throw new \LogicException("OTP challenge [{$this->id}] has no subject.");
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isLocked(): bool
    {
        return $this->locked_until !== null && $this->locked_until->isFuture();
    }

    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'purpose' => OtpPurpose::class,
            'channel' => Channel::class,
            'attempts' => 'integer',
            'expires_at' => 'immutable_datetime',
            'verified_at' => 'immutable_datetime',
            'locked_until' => 'immutable_datetime',
        ];
    }
}
