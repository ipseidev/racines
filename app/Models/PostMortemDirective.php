<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\StoresDatesWithOffset;
use App\Enums\PostMortemWish;
use Carbon\CarbonImmutable;
use Database\Factories\PostMortemDirectiveFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ce que le narrateur veut qu'il advienne de ses histoires après sa mort.
 *
 * @property string $id
 * @property string $project_id
 * @property string $narrator_id
 * @property PostMortemWish $wishes
 * @property string|null $referent_name
 * @property string|null $referent_contact_masked
 * @property string|null $referent_contact_hash
 * @property string $consent_id
 * @property CarbonImmutable $recorded_at
 */
final class PostMortemDirective extends Model
{
    /** @use HasFactory<PostMortemDirectiveFactory> */
    use HasFactory, HasUuids, StoresDatesWithOffset;

    /** @var list<string> */
    protected $fillable = [
        'wishes', 'referent_name', 'referent_contact_masked',
        'referent_contact_hash', 'recorded_at',
    ];

    /** @return BelongsTo<Narrator, $this> */
    public function narrator(): BelongsTo
    {
        return $this->belongsTo(Narrator::class);
    }

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** @return BelongsTo<Consent, $this> */
    public function consent(): BelongsTo
    {
        return $this->belongsTo(Consent::class);
    }

    /**
     * La personne qui se présente est-elle celle que le narrateur a désignée ?
     *
     * On compare des empreintes : le produit doit pouvoir reconnaître le
     * référent sans conserver son carnet d'adresses.
     */
    public function matchesReferent(string $contact): bool
    {
        if ($this->referent_contact_hash === null) {
            return false;
        }

        return hash_equals($this->referent_contact_hash, self::hashContact($contact));
    }

    public static function hashContact(string $contact): string
    {
        return hash('sha256', mb_strtolower(trim($contact)));
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'wishes' => PostMortemWish::class,
            'recorded_at' => 'immutable_datetime',
        ];
    }
}
