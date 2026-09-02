<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AddressForm;
use App\Enums\Cadence;
use App\Enums\Offer;
use App\Enums\ProjectMemberRole;
use App\Enums\ProjectStatus;
use App\Enums\PromptSlot;
use App\Enums\ValidationVariant;
use App\Support\Product\ServiceWindow;
use Carbon\CarbonImmutable;
use Database\Factories\ProjectFactory;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Un projet : un livre, un narrateur principal, une Initiateur·rice
 * propriétaire (glossaire §2).
 *
 * @property string $id
 * @property int $owner_user_id
 * @property string|null $cohort_id
 * @property ProjectStatus $status
 * @property Offer $offer
 * @property AddressForm $address_form
 * @property Cadence $cadence
 * @property int $prompt_day
 * @property PromptSlot $prompt_slot
 * @property string $timezone
 * @property CarbonImmutable|null $next_prompt_at
 * @property CarbonImmutable|null $paused_until
 * @property CarbonImmutable|null $collection_started_at
 * @property CarbonImmutable|null $collection_ends_at
 * @property CarbonImmutable|null $finalization_ends_at
 * @property ValidationVariant $validation_variant
 * @property string|null $gift_message
 * @property string|null $gift_audio_recording_id
 * @property CarbonImmutable|null $gift_send_at
 * @property CarbonImmutable|null $gift_sent_at
 * @property CarbonImmutable|null $accepted_at
 * @property CarbonImmutable|null $refused_at
 * @property string|null $refusal_reason
 * @property string|null $family_code_hash
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read User $owner
 */
final class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory, HasUuids;

    /**
     * Reprend les valeurs par défaut de la migration : une instance qui sort
     * d'une action doit être lisible sans `refresh()`.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => ProjectStatus::Draft->value,
        'address_form' => AddressForm::Vous->value,
        'cadence' => Cadence::Weekly->value,
        'prompt_day' => 1,
        'prompt_slot' => PromptSlot::Morning->value,
        'timezone' => 'Europe/Paris',
        'validation_variant' => ValidationVariant::Immediate->value,
    ];

    /** @var list<string> */
    protected $fillable = [
        'cohort_id', 'status', 'offer', 'address_form', 'cadence', 'prompt_day',
        'prompt_slot', 'timezone', 'gift_message', 'gift_send_at', 'validation_variant',
    ];

    /** @return BelongsTo<User, $this> */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    /** @return BelongsTo<Cohort, $this> */
    public function cohort(): BelongsTo
    {
        return $this->belongsTo(Cohort::class);
    }

    /** @return HasMany<ProjectMember, $this> */
    public function members(): HasMany
    {
        return $this->hasMany(ProjectMember::class);
    }

    /** @return HasMany<Narrator, $this> */
    public function narrators(): HasMany
    {
        return $this->hasMany(Narrator::class);
    }

    /**
     * Le narrateur principal. Plusieurs narrateurs existent en base ; un seul
     * est principal, garanti par un index unique partiel (PRD §2).
     *
     * @return HasOne<Narrator, $this>
     */
    public function primaryNarrator(): HasOne
    {
        return $this->hasOne(Narrator::class)->where('is_primary', true);
    }

    /** @return HasMany<FamilyMember, $this> */
    public function familyMembers(): HasMany
    {
        return $this->hasMany(FamilyMember::class);
    }

    /** @return HasMany<Story, $this> */
    public function stories(): HasMany
    {
        return $this->hasMany(Story::class);
    }

    /** @return HasMany<Consent, $this> */
    public function consents(): HasMany
    {
        return $this->hasMany(Consent::class);
    }

    /**
     * Durées de collecte et de finalisation de l'offre souscrite (R-2).
     *
     * Le pilote dure douze semaines, finalisation comprise ; l'offre cœur
     * ouvre douze mois de collecte puis trois mois pour boucler le livre.
     */
    public function collectionWindow(?DateTimeInterface $from = null): ServiceWindow
    {
        $start = CarbonImmutable::instance($from ?? $this->collection_started_at ?? now());

        if ($this->offer === Offer::Pilot) {
            $end = $start->addWeeks((int) config('product.offer.pilot_weeks'));

            return new ServiceWindow($start, $end, $end);
        }

        $end = $start->addMonths((int) config('product.offer.core_months'));

        return new ServiceWindow(
            $start,
            $end,
            $end->addMonths((int) config('product.offer.finalization_months')),
        );
    }

    /**
     * Ouvre la collecte et fige les trois échéances.
     */
    public function startCollection(?DateTimeInterface $at = null): self
    {
        $window = $this->collectionWindow($at ?? now());

        $this->collection_started_at = $window->collectionStartsAt;
        $this->collection_ends_at = $window->collectionEndsAt;
        $this->finalization_ends_at = $window->finalizationEndsAt;
        $this->save();

        return $this;
    }

    public function isMember(User $user): bool
    {
        return $this->owner_user_id === $user->id
            || $this->members()->where('user_id', $user->id)->exists();
    }

    public function hasRole(User $user, ProjectMemberRole $role): bool
    {
        return $this->members()
            ->where('user_id', $user->id)
            ->where('role', $role->value)
            ->exists();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => ProjectStatus::class,
            'offer' => Offer::class,
            'address_form' => AddressForm::class,
            'cadence' => Cadence::class,
            'prompt_slot' => PromptSlot::class,
            'validation_variant' => ValidationVariant::class,
            'prompt_day' => 'integer',
            'next_prompt_at' => 'immutable_datetime',
            'paused_until' => 'immutable_datetime',
            'collection_started_at' => 'immutable_datetime',
            'collection_ends_at' => 'immutable_datetime',
            'finalization_ends_at' => 'immutable_datetime',
            'gift_send_at' => 'immutable_datetime',
            'gift_sent_at' => 'immutable_datetime',
            'accepted_at' => 'immutable_datetime',
            'refused_at' => 'immutable_datetime',
        ];
    }
}
