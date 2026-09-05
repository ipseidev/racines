<?php

declare(strict_types=1);

use App\Actions\PickNextQuestion;
use App\Enums\ProjectStatus;
use App\Models\Narrator;
use App\Models\Project;
use App\Models\Question;
use App\Models\Story;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

/**
 * La page « Les questions » montre **ce qui va partir, dans l'ordre où ça
 * partira**. Une liste qui promettrait un ordre que l'envoi ne tient pas
 * ferait perdre confiance à la seule personne qui organise.
 *
 * @return array{User, Project}
 */
function questionsProject(): array
{
    $owner = User::factory()->create();
    $owner->markEmailAsVerified();

    $project = Project::factory()->create([
        'owner_user_id' => $owner->id,
        'status' => ProjectStatus::Active,
    ]);

    Narrator::factory()->create([
        'project_id' => $project->id,
        'is_primary' => true,
        'first_name' => 'Jeanne',
    ]);

    return [$owner, $project->refresh()];
}

/**
 * Quatre questions à soi. Le corpus semé par l'application est éteint d'abord :
 * la file le montrerait, et le test compte.
 *
 * @return array<int, Question>
 */
function corpus(): array
{
    Question::query()->update(['is_active' => false]);

    return collect(range(1, 4))
        ->map(fn (int $i): Question => Question::factory()->create([
            'slug' => "q-{$i}",
            'text' => "Question {$i} ?",
            'order_hint' => $i,
            'difficulty' => 1,
            'is_active' => true,
        ]))
        ->all();
}

it('montre la file dans l’ordre du moteur : avancées d’abord, puis le corpus', function (): void {
    [$owner, $project] = questionsProject();
    [$first, $second, $third, $fourth] = corpus();

    // Elle a avancé la troisième, écarté la deuxième ; la première est posée.
    $project->questionSettings()->create(['question_id' => $third->id, 'custom_order' => 1]);
    $project->questionSettings()->create(['question_id' => $second->id, 'excluded' => true]);
    Story::factory()->proposed()->create([
        'project_id' => $project->id,
        'question_id' => $first->id,
    ]);

    $this->actingAs($owner)
        ->get('/espace/questions')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('initiator/Questions')
            ->has('queue', 2)
            ->where('queue.0.id', $third->id)
            ->where('queue.1.id', $fourth->id)
            ->where('queue.0.themeLabel', fn (mixed $label): bool => is_string($label) && $label !== '')
            ->has('excluded', 1)
            ->where('excluded.0.id', $second->id)
            ->has('asked', 1)
            ->where('asked.0.id', $first->id),
        );
});

it('affiche en tête ce que le moteur posera', function (): void {
    [, $project] = questionsProject();
    [, , $third] = corpus();

    $project->questionSettings()->create(['question_id' => $third->id, 'custom_order' => 1]);

    $picker = app(PickNextQuestion::class);

    expect($picker->queue($project)->first()?->id)->toBe($third->id)
        ->and($picker->handle($project)?->id)->toBe($third->id);
});

it('ne montre jamais une question inactive', function (): void {
    [$owner] = questionsProject();
    corpus();

    Question::factory()->create([
        'slug' => 'q-fantome',
        'text' => 'Question fantôme ?',
        'order_hint' => 0,
        'is_active' => false,
    ]);

    $this->actingAs($owner)
        ->get('/espace/questions')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('queue', 4)
            ->where('queue.0.text', 'Question 1 ?'),
        );
});
