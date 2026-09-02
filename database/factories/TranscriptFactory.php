<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TranscriptKind;
use App\Models\Story;
use App\Models\Transcript;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transcript>
 */
final class TranscriptFactory extends Factory
{
    /** @var class-string<Transcript> */
    protected $model = Transcript::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'story_id' => Story::factory()->transcribed(),
            'kind' => TranscriptKind::Verbatim,
            'version' => 1,
            'provider' => 'fake',
            'language' => 'fr',
            'text' => 'Alors euh je me souviens de la maison de Kerhostin.',
            'words' => [['word' => 'Alors', 'start' => 0.0, 'end' => 0.3, 'confidence' => 0.9]],
            'is_current' => true,
        ];
    }

    public function fluide(): static
    {
        return $this->state(fn (): array => [
            'kind' => TranscriptKind::Fluide,
            'provider' => 'claude',
            'text' => 'Je me souviens de la maison de Kerhostin.',
            'words' => null,
        ]);
    }

    public function edited(): static
    {
        return $this->state(fn (): array => [
            'kind' => TranscriptKind::Edited,
            'provider' => 'human',
            'words' => null,
        ]);
    }
}
