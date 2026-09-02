<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\LexiconEntry;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LexiconEntry>
 */
final class LexiconEntryFactory extends Factory
{
    /** @var class-string<LexiconEntry> */
    protected $model = LexiconEntry::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'term' => fake()->unique()->lastName(),
            'replacement' => null,
            'notes' => null,
        ];
    }

    /**
     * Une entrée qui corrige une graphie : l'ASR entend « Ker Austin », la
     * famille écrit « Kerhostin ».
     */
    public function correcting(string $heard, string $spelling): static
    {
        return $this->state(fn (): array => ['term' => $heard, 'replacement' => $spelling]);
    }
}
