<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\BookFormat;
use App\Enums\BookStatus;
use App\Models\Book;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Book>
 */
final class BookFactory extends Factory
{
    /** @var class-string<Book> */
    protected $model = Book::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'template' => 'classic',
            'format' => BookFormat::FoundingChapter,
            'status' => BookStatus::Draft,
            'page_count_estimate' => 0,
        ];
    }

    public function proofing(): static
    {
        return $this->state(fn (): array => [
            'status' => BookStatus::Proofing,
            'proof_pdf_path' => 'books/proof-v1.pdf',
            'proof_version' => 1,
            'proof_generated_at' => now(),
        ]);
    }

    public function approved(): static
    {
        return $this->proofing()->state(fn (): array => [
            'status' => BookStatus::Approved,
            'proof_acknowledged_final_print' => true,
            'proof_acknowledged_lexicon_reviewed' => true,
            'proof_approved_at' => now(),
        ]);
    }
}
