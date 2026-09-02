<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Channel;
use App\Enums\ConsentKind;
use App\Enums\ConsentStatus;
use Carbon\CarbonImmutable;
use Database\Factories\NarratorFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Le narrateur·rice n'a pas de compte : il agit par jeton, et son veto prime
 * toujours sur l'Initiateur·rice (glossaire §1, R-1).
 *
 * @property string $id
 * @property string $project_id
 * @property string $first_name
 * @property string|null $last_name
 * @property string $display_name
 * @property string|null $email
 * @property string|null $phone_e164
 * @property Channel $preferred_channel
 * @property bool $is_primary
 * @property int|null $birth_year
 * @property CarbonImmutable|null $opted_in_at
 * @property CarbonImmutable|null $opted_out_at
 * @property CarbonImmutable|null $contact_deletion_due_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
final class Narrator extends Model
{
    /** @use HasFactory<NarratorFactory> */
    use HasFactory, HasUuids;

    /** @var list<string> */
    protected $fillable = [
        'first_name', 'last_name', 'display_name', 'email', 'phone_e164',
        'preferred_channel', 'is_primary', 'birth_year', 'opted_in_at',
    ];

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** @return HasMany<Story, $this> */
    public function stories(): HasMany
    {
        return $this->hasMany(Story::class);
    }

    /** @return MorphMany<Consent, $this> */
    public function consents(): MorphMany
    {
        return $this->morphMany(Consent::class, 'subject');
    }

    /**
     * Un consentement vaut par sa dernière ligne : une révocation en ajoute
     * une, elle n'efface pas l'accord d'origine (doc 04 §2).
     */
    public function hasConsent(ConsentKind $kind): bool
    {
        $latest = $this->consents()
            ->where('kind', $kind->value)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();

        return $latest?->status === ConsentStatus::Granted;
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'preferred_channel' => Channel::class,
            'is_primary' => 'boolean',
            'birth_year' => 'integer',
            'opted_in_at' => 'immutable_datetime',
            'opted_out_at' => 'immutable_datetime',
            'contact_deletion_due_at' => 'immutable_datetime',
        ];
    }
}
