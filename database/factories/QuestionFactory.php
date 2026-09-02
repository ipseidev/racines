<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\QuestionTheme;
use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Question>
 */
final class QuestionFactory extends Factory
{
    /** @var class-string<Question> */
    protected $model = Question::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $text = 'Quel est votre premier souvenir '.fake()->unique()->word().' ?';

        return [
            'slug' => Str::slug($text),
            'text' => $text,
            'theme' => QuestionTheme::Childhood,
            'difficulty' => 1,
            'order_hint' => fake()->numberBetween(1, 60),
            'is_active' => true,
            'locale' => 'fr',
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }
}
