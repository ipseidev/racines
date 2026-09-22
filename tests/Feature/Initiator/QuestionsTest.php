<?php

declare(strict_types=1);

use App\Actions\PickNextQuestion;
use App\Enums\ProjectStatus;
use App\Models\Narrator;
use App\Models\Project;
use App\Models\Question;
use App\Models\Story;
use App\Models\User;
use App\Support\PhotoPresenter;
use Illuminate\Http\Testing\File;
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
        ->get(spaceUrl($project, '/questions'))
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
    [$owner, $project] = questionsProject();
    corpus();

    Question::factory()->create([
        'slug' => 'q-fantome',
        'text' => 'Question fantôme ?',
        'order_hint' => 0,
        'is_active' => false,
    ]);

    $this->actingAs($owner)
        ->get(spaceUrl($project, '/questions'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('queue', 4)
            ->where('queue.0.text', 'Question 1 ?'),
        );
});

/**
 * Une question écrite par la famille, avec ses photos (T-253).
 *
 * « Raconte-nous cette photo » est la question la plus naturelle qui soit, et
 * elle ne se pose pas sans l'image. Le dépôt existait depuis le bloc 12, mais
 * seulement **après coup**, depuis le tableau de bord : on écrivait la
 * question, on la validait, puis on retournait joindre l'image ailleurs.
 *
 * Ce que ces tests protègent : les photos jointes ici sont des photos **de
 * question** — `is_prompt` —, donc celles que la narratrice voit avec l'énoncé,
 * et non celles d'une réponse qui n'existe pas encore.
 */
it('joint les photos à la question personnalisée, marquées comme question', function (): void {
    [$owner, $project] = questionsProject();

    $this->actingAs($owner)
        ->post(spaceUrl($project, '/questions/personnalisee'), [
            'text' => 'Raconte-nous cette photo, prise devant la maison.',
            'photos' => [
                File::image('un.jpg', 900, 600),
                File::image('deux.jpg', 900, 600),
            ],
        ])
        ->assertRedirect();

    $story = $project->stories()->latest('sequence')->firstOrFail();

    expect($story->custom_question_text)
        ->toBe('Raconte-nous cette photo, prise devant la maison.');

    $photos = PhotoPresenter::promptsForStory($story);

    expect($photos)->toHaveCount(2);
});

it('refuse plus de quatre photos sur une question', function (): void {
    [$owner, $project] = questionsProject();

    $this->actingAs($owner)
        ->post(spaceUrl($project, '/questions/personnalisee'), [
            'text' => 'Une question avec beaucoup trop d’images jointes.',
            'photos' => array_fill(
                0,
                5,
                File::image('trop.jpg', 400, 300),
            ),
        ])
        ->assertSessionHasErrors('photos');
});

it('pose la question même sans photo', function (): void {
    [$owner, $project] = questionsProject();

    $this->actingAs($owner)
        ->post(spaceUrl($project, '/questions/personnalisee'), [
            'text' => 'Une question toute simple, sans la moindre image.',
        ])
        ->assertRedirect();

    $story = $project->stories()->latest('sequence')->firstOrFail();

    expect(PhotoPresenter::promptsForStory($story))->toBe([]);
});
