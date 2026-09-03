<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Book;
use App\Models\BookChapter;
use App\Models\Story;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BookChapter>
 */
final class BookChapterFactory extends Factory
{
    /** @var class-string<BookChapter> */
    protected $model = BookChapter::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'book_id' => Book::factory(),
            'story_id' => Story::factory(),
            'position' => 1,
            'included' => true,
        ];
    }
}
