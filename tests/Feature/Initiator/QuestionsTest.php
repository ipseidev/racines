<?php

declare(strict_types=1);

use App\Actions\IssueRecordToken;
use App\Actions\PickNextQuestion;
use App\Enums\AddressForm;
use App\Enums\GrammaticalGender;
use App\Enums\ProjectStatus;
use App\Enums\TokenType;
use App\Models\AccessToken;
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

it('montre les questions du corpus comme la narratrice les recevra', function (): void {
    [$owner, $project] = questionsProject();
    $project->update(['address_form' => AddressForm::Tu]);
    $project->primaryNarrator()->firstOrFail()->update(['grammatical_gender' => GrammaticalGender::Feminine]);
    [$first] = corpus();
    $first->update(['text' => 'Où êtes-vous né{|e} ?', 'text_tu' => 'Où es-tu né{|e} ?']);

    $this->actingAs($owner)
        ->get(spaceUrl($project, '/questions'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('queue.0.text', 'Où es-tu née ?'),
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

/**
 * Une question écrite par la famille part **avant** le corpus (T-254).
 *
 * Elle devient une histoire PROPOSÉE dès qu'on l'écrit, et l'envoi
 * hebdomadaire ne la regardait pas : il piochait dans le corpus et proposait
 * une histoire de plus. Une question posée à la main pouvait donc n'être
 * jamais envoyée — tout en s'affichant dans l'espace, ce qui donnait toutes
 * les raisons de croire qu'elle partirait.
 */
it('envoie la question de la famille avant celles du corpus', function (): void {
    Notification::fake();

    [$owner, $project] = questionsProject();
    corpus();

    $project->forceFill([
        'status' => ProjectStatus::Active,
        'next_prompt_at' => now()->subMinute(),
    ])->save();

    $this->actingAs($owner)->post(spaceUrl($project, '/questions/personnalisee'), [
        'text' => 'Raconte-nous le jour où tu as rencontré papa.',
    ])->assertRedirect();

    $this->artisan('prompts:dispatch-due')->assertSuccessful();

    $envoyee = $project->stories()
        ->whereNotNull('custom_question_text')
        ->firstOrFail();

    // Le lien est parti pour **elle**, et non pour une question du corpus.
    expect(
        AccessToken::query()
            ->where('subject_type', 'story')
            ->where('subject_id', $envoyee->id)
            ->where('type', TokenType::Record->value)
            ->exists(),
    )->toBeTrue();

    // Et aucune histoire de corpus n'a été proposée par-dessus.
    expect($project->stories()->whereNotNull('question_id')->count())->toBe(0);
});

it('revient au corpus quand aucune question de la famille n’attend', function (): void {
    Notification::fake();

    [, $project] = questionsProject();
    corpus();

    $project->forceFill([
        'status' => ProjectStatus::Active,
        'next_prompt_at' => now()->subMinute(),
    ])->save();

    $this->artisan('prompts:dispatch-due')->assertSuccessful();

    expect($project->stories()->whereNotNull('question_id')->count())->toBe(1);
});

/**
 * L'ordre des questions écrites par la famille (T-254).
 *
 * Elles ne vivent pas dans la file du corpus, et rien ne permettait d'en
 * décider le rang : écrire trois questions sans pouvoir dire laquelle part en
 * premier n'est qu'une demi-fonctionnalité. Leur ordre est celui de
 * `stories.sequence`, que l'envoi lit.
 */
it('réordonne les questions de la famille, en permutant leurs rangs', function (): void {
    [$owner, $project] = questionsProject();

    foreach (['La première que j’ai écrite.', 'La deuxième que j’ai écrite.'] as $texte) {
        $this->actingAs($owner)
            ->post(spaceUrl($project, '/questions/personnalisee'), ['text' => $texte])
            ->assertRedirect();
    }

    $avant = $project->stories()->whereNotNull('custom_question_text')
        ->orderBy('queue_order')->orderBy('sequence')->get();

    $sequences = $avant->pluck('sequence')->all();

    $this->actingAs($owner)
        ->post(spaceUrl($project, '/questions/ordre'), [
            'order' => [
                ['kind' => 'story', 'id' => $avant[1]->id],
                ['kind' => 'story', 'id' => $avant[0]->id],
            ],
        ])
        ->assertRedirect();

    $apres = $project->stories()->whereNotNull('custom_question_text')
        ->orderBy('queue_order')->orderBy('sequence')->get();

    /*
     * L'ordre s'inverse — et `sequence` ne bouge pas d'un pouce.
     *
     * Le rang dans la file est `queue_order` ; `sequence` est la place de
     * l'histoire dans la vie du projet, que le livre et la frise lisent.
     * Réordonner ce qui n'est pas encore parti ne doit rien y changer.
     */
    expect($apres->pluck('id')->all())->toBe([$avant[1]->id, $avant[0]->id])
        ->and($apres->pluck('sequence')->sort()->values()->all())->toBe($sequences)
        ->and($avant[1]->refresh()->queue_order)->toBe(0)
        ->and($avant[0]->refresh()->queue_order)->toBe(1);
});

it('ne réordonne pas une question dont le lien est déjà parti', function (): void {
    Notification::fake();

    [$owner, $project] = questionsProject();

    $this->actingAs($owner)
        ->post(spaceUrl($project, '/questions/personnalisee'), ['text' => 'Celle qui est déjà partie.'])
        ->assertRedirect();

    $story = $project->stories()->whereNotNull('custom_question_text')->firstOrFail();
    $rang = $story->queue_order;

    // Le lien part : elle n'est plus à réordonner.
    app(IssueRecordToken::class)->handle($story);

    $this->actingAs($owner)
        ->post(spaceUrl($project, '/questions/ordre'), [
            'order' => [['kind' => 'story', 'id' => $story->id]],
        ])
        ->assertRedirect();

    expect($story->refresh()->queue_order)->toBe($rang);
});

/**
 * Une question du corpus peut passer **devant** une question de la famille
 * (T-255).
 *
 * C'était impossible : les deux natures vivaient dans deux échelles séparées,
 * et celles de la famille passaient toutes devant, sans recours. Elles
 * partagent maintenant le rang — `queue_order` d'un côté, `custom_order` de
 * l'autre — et l'envoi compare deux entiers sans connaître leur nature.
 */
it('laisse une question du corpus doubler une question de la famille', function (): void {
    Notification::fake();

    [$owner, $project] = questionsProject();
    corpus();

    $this->actingAs($owner)
        ->post(spaceUrl($project, '/questions/personnalisee'), ['text' => 'La mienne, que je veux finalement en second.'])
        ->assertRedirect();

    $mienne = $project->stories()->whereNotNull('custom_question_text')->firstOrFail();
    $duCorpus = Question::query()->active()->orderBy('order_hint')->firstOrFail();

    // Le corpus d'abord, la mienne ensuite.
    $this->actingAs($owner)
        ->post(spaceUrl($project, '/questions/ordre'), [
            'order' => [
                ['kind' => 'question', 'id' => $duCorpus->id],
                ['kind' => 'story', 'id' => $mienne->id],
            ],
        ])
        ->assertRedirect();

    $project->forceFill([
        'status' => ProjectStatus::Active,
        'next_prompt_at' => now()->subMinute(),
    ])->save();

    $this->artisan('prompts:dispatch-due')->assertSuccessful();

    // C'est la question du corpus qui est partie, pas la mienne.
    expect($project->stories()->where('question_id', $duCorpus->id)->exists())->toBeTrue()
        ->and(
            AccessToken::query()
                ->where('subject_type', 'story')
                ->where('subject_id', $mienne->id)
                ->exists(),
        )->toBeFalse();
});

it('retire une question qu’on a écrite, avec ses photos', function (): void {
    [$owner, $project] = questionsProject();

    $this->actingAs($owner)
        ->post(spaceUrl($project, '/questions/personnalisee'), [
            'text' => 'Celle que je vais finalement retirer.',
            'photos' => [File::image('regret.jpg', 800, 600)],
        ])
        ->assertRedirect();

    $story = $project->stories()->whereNotNull('custom_question_text')->firstOrFail();

    $this->actingAs($owner)
        ->delete(spaceUrl($project, '/questions/proposees/'.$story->id))
        ->assertRedirect();

    expect(Story::query()->whereKey($story->id)->exists())->toBeFalse();
});

it('ne retire jamais une question dont le lien est parti', function (): void {
    [$owner, $project] = questionsProject();

    $this->actingAs($owner)
        ->post(spaceUrl($project, '/questions/personnalisee'), ['text' => 'Celle qui appartient déjà à la narratrice.'])
        ->assertRedirect();

    $story = $project->stories()->whereNotNull('custom_question_text')->firstOrFail();

    app(IssueRecordToken::class)->handle($story);

    $this->actingAs($owner)
        ->delete(spaceUrl($project, '/questions/proposees/'.$story->id))
        ->assertNotFound();

    expect(Story::query()->whereKey($story->id)->exists())->toBeTrue();
});
