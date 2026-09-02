<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ConsentKind;
use App\Models\ConsentText;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConsentText>
 */
final class ConsentTextFactory extends Factory
{
    /** @var class-string<ConsentText> */
    protected $model = ConsentText::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'kind' => ConsentKind::VoiceRecording,
            'version' => '1.0',
            'locale' => 'fr',
            'body' => 'Texte provisoire de consentement. [À VALIDER PAR CONSEIL]',
            'effective_from' => now()->subDay(),
        ];
    }

    public function ofKind(ConsentKind $kind): static
    {
        return $this->state(fn (): array => ['kind' => $kind]);
    }
}
