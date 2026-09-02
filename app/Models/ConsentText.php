<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\StoresDatesWithOffset;
use App\Enums\ConsentKind;
use Carbon\CarbonImmutable;
use Database\Factories\ConsentTextFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Le texte exact lu au moment du consentement, versionné.
 *
 * Un consentement n'a de valeur que si l'on peut réafficher ce qui a été lu :
 * `consents.text_version` pointe ici et le texte n'est jamais recopié.
 *
 * @property int $id
 * @property ConsentKind $kind
 * @property string $version
 * @property string $locale
 * @property string $body
 * @property CarbonImmutable $effective_from
 * @property CarbonImmutable|null $created_at
 */
final class ConsentText extends Model
{
    /** @use HasFactory<ConsentTextFactory> */
    use HasFactory, StoresDatesWithOffset;

    public const UPDATED_AT = null;

    /** @var list<string> */
    protected $fillable = ['kind', 'version', 'locale', 'body', 'effective_from'];

    /**
     * Version en vigueur d'un type de consentement.
     */
    public static function current(ConsentKind $kind, ?string $locale = null): ?self
    {
        return self::query()
            ->where('kind', $kind->value)
            ->where('locale', $locale ?? (string) config('app.locale'))
            ->where('effective_from', '<=', now())
            ->orderByDesc('effective_from')
            ->orderByDesc('id')
            ->first();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'kind' => ConsentKind::class,
            'effective_from' => 'immutable_datetime',
        ];
    }
}
