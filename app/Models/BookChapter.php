<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\StoresDatesWithOffset;
use Carbon\CarbonImmutable;
use Database\Factories\BookChapterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un chapitre : une histoire, une position, un QR.
 *
 * @property int $id
 * @property string $book_id
 * @property string $story_id
 * @property int $position
 * @property bool $included
 * @property string|null $qr_token_id
 * @property CarbonImmutable|null $created_at
 * @property-read Book $book
 * @property-read Story $story
 * @property-read AccessToken|null $qrToken
 */
final class BookChapter extends Model
{
    /** @use HasFactory<BookChapterFactory> */
    use HasFactory, StoresDatesWithOffset;

    /** @var list<string> */
    protected $fillable = ['position', 'included'];

    /** @return BelongsTo<Book, $this> */
    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    /** @return BelongsTo<Story, $this> */
    public function story(): BelongsTo
    {
        return $this->belongsTo(Story::class);
    }

    /**
     * Le jeton du QR imprimé.
     *
     * **Réutilisé** d'une génération à l'autre : un QR déjà imprimé doit
     * continuer de fonctionner, et régénérer le bon à tirer ne peut pas
     * invalider les livres déjà sur les étagères.
     *
     * @return BelongsTo<AccessToken, $this>
     */
    public function qrToken(): BelongsTo
    {
        return $this->belongsTo(AccessToken::class, 'qr_token_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'included' => 'boolean',
        ];
    }
}
