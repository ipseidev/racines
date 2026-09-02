<?php

declare(strict_types=1);

use App\Actions\PickNextQuestion;
use App\Models\Narrator;
use App\Models\Project;
use App\Models\ProjectQuestionSetting;
use App\Models\Question;
use App\Models\Story;
use Database\Seeders\QuestionSeeder;

function projectReady(): Project
{
    $project = Project::factory()->create();
    Narrator::factory()->primary()->create(['project_id' => $project->id]);

    return $project->refresh();
}

function pick(Project $project, bool $easier = false): ?Question
{
    return app(PickNextQuestion::class)->handle($project, easier: $easier);
}

/** Propose une question et amène l'histoire à l'état demandé. */
function proposeAndReach(Project $project, Question $question, string $state): Story
{
    return Story::factory()
        ->forProject($project)
        ->create(['question_id' => $question->id, 'state' => $state]);
}

it('règle 1 — commence par la question la plus facile et la plus haute dans l’ordre', function (): void {
    $project = projectReady();

    $first = pick($project);

    expect($first?->slug)->toBe('naissance-recit')
        ->and($first?->difficulty)->toBe(1);
});

it('règle 2 — poursuit dans l’ordre croissant, sans repasser par les questions déjà posées', function (): void {
    $project = projectReady();

    $seen = [];

    for ($i = 0; $i < 4; $i++) {
        $question = pick($project);
        expect($question)->not->toBeNull();

        $seen[] = $question->slug;
        proposeAndReach($project, $question, 'proposed');
    }

    expect($seen)->toBe(['naissance-recit', 'premier-souvenir', 'maison-enfance', 'betise-enfant'])
        ->and($seen)->toHaveCount(count(array_unique($seen)));
});

it('règle 3 — respecte les exclusions et l’ordre personnalisé de l’Initiateur·rice', function (): void {
    $project = projectReady();

    $excluded = Question::query()->where('slug', 'naissance-recit')->sole();
    $advanced = Question::query()->where('slug', 'ce-quon-retienne')->sole();

    ProjectQuestionSetting::query()->create([
        'project_id' => $project->id,
        'question_id' => $excluded->id,
        'excluded' => true,
    ]);

    expect(pick($project)?->slug)->toBe('premier-souvenir');

    ProjectQuestionSetting::query()->create([
        'project_id' => $project->id,
        'question_id' => $advanced->id,
        'custom_order' => 1,
    ]);

    // Une question avancée passe devant, **même intime** et même avant la
    // sixième histoire validée : la règle 5 protège du séquencement
    // automatique, pas d'un choix délibéré de l'Initiateur·rice.
    expect(pick($project)?->slug)->toBe('ce-quon-retienne')
        ->and($advanced->difficulty)->toBe(5);
});

it('règle 4 — recule sur une question facile quand on demande plus doux', function (): void {
    $project = projectReady();

    // On épuise le facile, sauf une question restée loin dans le corpus :
    // la sélection normale propose donc du plus exigeant, la sélection
    // « plus douce » va rechercher celle qui reste.
    $spared = Question::query()->where('slug', 'surnoms-famille')->sole();

    foreach (Question::query()->where('difficulty', '<=', 2)->get() as $question) {
        if ($question->id !== $spared->id) {
            proposeAndReach($project, $question, 'validated');
        }
    }

    $normal = pick($project);
    $easier = pick($project, easier: true);

    expect($normal?->difficulty)->toBeGreaterThanOrEqual(3)
        ->and($easier?->slug)->toBe('surnoms-famille')
        ->and($easier?->difficulty)->toBeLessThanOrEqual(2);
});

it('règle 5 — ne propose rien de difficile avant la sixième histoire validée', function (): void {
    $project = projectReady();

    // On exclut tout ce qui est facile : sans la règle 5, la sélection
    // remonterait sur une question de difficulté 4.
    $easy = Question::query()->where('difficulty', '<=', 3)->pluck('id');

    foreach ($easy as $id) {
        ProjectQuestionSetting::query()->create([
            'project_id' => $project->id,
            'question_id' => $id,
            'excluded' => true,
        ]);
    }

    expect(pick($project))->toBeNull();

    // Six histoires validées plus tard, l'intime s'ouvre.
    foreach (Question::query()->orderBy('order_hint')->limit(6)->get() as $question) {
        proposeAndReach($project, $question, 'validated');
    }

    expect(pick($project)?->difficulty)->toBeGreaterThanOrEqual(4);
});

it('règle 6 — dit quels thèmes sont déjà couverts par une histoire validée', function (): void {
    $project = projectReady();

    expect(app(PickNextQuestion::class)->coveredThemes($project))->toBe([]);

    $childhood = Question::query()->where('theme', 'childhood')->orderBy('order_hint')->firstOrFail();
    $legacy = Question::query()->where('theme', 'legacy')->orderBy('order_hint')->firstOrFail();

    proposeAndReach($project, $childhood, 'validated');
    proposeAndReach($project, $legacy, 'proposed');

    // Un thème n'est couvert que par une histoire **validée** (R-6).
    expect(app(PickNextQuestion::class)->coveredThemes($project))->toBe(['childhood']);
});

it('rend null quand le corpus est épuisé', function (): void {
    $project = projectReady();

    foreach (Question::query()->get() as $question) {
        proposeAndReach($project, $question, 'validated');
    }

    expect(pick($project))->toBeNull();
});

it('ignore les questions désactivées du corpus', function (): void {
    $project = projectReady();

    Question::query()->where('slug', 'naissance-recit')->update(['is_active' => false]);

    expect(pick($project)?->slug)->toBe('premier-souvenir');
});

it('sème bien le corpus attendu', function (): void {
    expect(QuestionSeeder::count())->toBe(60);
});
