<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ConsentKind;
use App\Enums\PostMortemWish;
use App\Models\Consent;
use App\Models\Narrator;
use App\Models\PostMortemDirective;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PostMortemDirective>
 */
final class PostMortemDirectiveFactory extends Factory
{
    /** @var class-string<PostMortemDirective> */
    protected $model = PostMortemDirective::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'narrator_id' => Narrator::factory()->primary(),
            'project_id' => fn (array $attributes): string => (string) Narrator::query()
                ->whereKey($attributes['narrator_id'])->firstOrFail()->project_id,
            'wishes' => PostMortemWish::TransferToFamily,
            'consent_id' => fn (array $attributes): string => (string) Consent::factory()->create([
                'subject_id' => $attributes['narrator_id'],
                'project_id' => $attributes['project_id'],
                'kind' => ConsentKind::PostMortemDirectives,
            ])->id,
            'recorded_at' => now(),
        ];
    }

    public function withReferent(string $name, string $contact): static
    {
        return $this->state(fn (): array => [
            'referent_name' => $name,
            'referent_contact_masked' => mb_substr($contact, 0, 3).'•••',
            'referent_contact_hash' => PostMortemDirective::hashContact($contact),
        ]);
    }

    public function deleteEverything(): static
    {
        return $this->state(fn (): array => ['wishes' => PostMortemWish::Delete]);
    }
}
