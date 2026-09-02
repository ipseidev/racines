<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\StoresDatesWithOffset;
use App\Enums\Channel;
use Carbon\CarbonImmutable;
use Database\Factories\FamilyMemberFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;

/**
 * Un proche : pas de compte, un jeton de lecture, et rien de visible avant que
 * le narrateur ait validé (R-4).
 *
 * @property string $id
 * @property string $project_id
 * @property int $invited_by_user_id
 * @property string $display_name
 * @property string|null $relationship
 * @property string|null $email
 * @property string|null $phone_e164
 * @property bool $can_contribute
 * @property CarbonImmutable|null $invited_at
 * @property CarbonImmutable|null $first_seen_at
 * @property CarbonImmutable|null $removed_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
final class FamilyMember extends Model
{
    /** @use HasFactory<FamilyMemberFactory> */
    use HasFactory, HasUuids, Notifiable, StoresDatesWithOffset;

    /** @var array<string, mixed> */
    protected $attributes = [
        'can_contribute' => false,
    ];

    /** @var list<string> */
    protected $fillable = [
        'display_name', 'relationship', 'email', 'phone_e164',
        'can_contribute', 'invited_at',
    ];

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** @return BelongsTo<User, $this> */
    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by_user_id');
    }

    /** @return MorphMany<Consent, $this> */
    public function consents(): MorphMany
    {
        return $this->morphMany(Consent::class, 'subject');
    }

    /**
     * Canal de contact d'un proche.
     *
     * Ils n'ont pas de préférence enregistrée : un proche est invité par
     * courriel, et son code éventuel suit le même chemin.
     */
    public function getPreferredChannelAttribute(): Channel
    {
        return $this->email === null ? Channel::Sms : Channel::Email;
    }

    /**
     * Ni compte ni mot de passe : les notifications partent sur la coordonnée
     * que la personne a donnée, et sur elle seule.
     */
    public function routeNotificationForMail(?Notification $notification = null): ?string
    {
        return $this->email;
    }

    public function routeNotificationForSms(?Notification $notification = null): ?string
    {
        return $this->phone_e164;
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'can_contribute' => 'boolean',
            'invited_at' => 'immutable_datetime',
            'first_seen_at' => 'immutable_datetime',
            'removed_at' => 'immutable_datetime',
        ];
    }
}
