<?php

declare(strict_types=1);

use App\Actions\PickNextQuestion;
use App\Enums\AddressForm;
use App\Enums\BuyerFocus;
use App\Enums\BuyerRelation;
use App\Enums\GrammaticalGender;
use App\Models\Narrator;
use App\Models\Project;
use App\Models\ProjectProfile;
use App\Models\Question;
use App\Models\Story;

/*
 * Le choix des questions selon le profil que la famille a rempli après
 * l'achat. Ces tests tournent sur le vrai corpus (semé par le TestCase) : ce
 * qu'ils protègent, c'est ce qu'une vraie narratrice recevra.
 */

/** @param array<string, mixed> $profile */
function profiled(array $profile = []): Project
{
    $project = Project::factory()->create(['address_form' => AddressForm::Tu]);
    Narrator::factory()->primary()->create(['project_id' => $project->id, 'grammatical_gender' => GrammaticalGender::Feminine]);
    ProjectProfile::query()->create(['project_id' => $project->id, ...$profile]);

    return $project->refresh();
}

/**
 * Ce qui partirait, semaine après semaine, si chaque histoire était validée
 * aussitôt : les règles de l'envoi sans l'attente des réponses.
 *
 * @return list<Question>
 */
function sent(Project $project, int $weeks = 52): array
{
    $picker = app(PickNextQuestion::class);
    $sent = [];

    for ($week = 0; $week < $weeks; $week++) {
        $question = $picker->handle($project);

        if ($question === null) {
            break;
        }

        $sent[] = $question;
        Story::factory()->forProject($project)->create(['question_id' => $question->id, 'state' => 'validated']);
    }

    return $sent;
}

/** @param list<Question> $questions */
function slugs(array $questions): array
{
    return array_map(fn (Question $question): string => $question->slug, $questions);
}

/** @param list<Question> $questions */
function aboutBuyer(array $questions): array
{
    return array_values(array_filter($questions, fn (Question $question): bool => in_array('about_buyer', $question->conditions, true)));
}

it('ne change rien quand le profil est vide : le tunnel passé, c’est le cas par défaut', function (): void {
    $without = Project::factory()->create();
    Narrator::factory()->primary()->create(['project_id' => $without->id]);

    expect(slugs(sent(profiled(), 20)))->toBe(slugs(sent($without->refresh(), 20)));
});

it('écarte une question dont la condition est fausse, garde celle qu’on ne connaît pas', function (): void {
    $single = sent(profiled(['facts' => ['partner' => false]]));
    $unknown = sent(profiled(['facts' => ['children' => true]]));

    $couple = fn (array $questions): int => count(array_filter($questions, fn (Question $question): bool => in_array('partner', $question->conditions, true)));

    expect($couple($single))->toBe(0)
        ->and($couple($unknown))->toBeGreaterThan(0);
});

it('écarte les sujets que la famille a demandé d’éviter', function (): void {
    $questions = sent(profiled(['avoided_topics' => ['war', 'bereavement']]), 80);

    foreach ($questions as $question) {
        expect(array_intersect($question->sensitive_topics, ['war', 'bereavement']))->toBe([], $question->slug);
    }
});

it('pose quatre à six questions sur l’acheteur, la première à la sixième semaine, espacées', function (): void {
    $questions = sent(profiled([
        'buyer_relation' => BuyerRelation::Child,
        'buyer_focus' => BuyerFocus::Buyer,
        'buyer_first_name' => 'Claire',
        'buyer_gender' => GrammaticalGender::Feminine,
    ]));

    $positions = array_keys(array_filter($questions, fn (Question $question): bool => in_array('about_buyer', $question->conditions, true)));

    expect(count($positions))->toBeGreaterThanOrEqual(4)->toBeLessThanOrEqual(6)
        ->and($positions[0])->toBe(5);

    foreach (array_slice($positions, 1) as $index => $position) {
        expect($position - $positions[$index])->toBeGreaterThanOrEqual(8);
    }

    // Les questions d'un enfant, et celles de tous les liens : jamais celles
    // d'un petit-enfant.
    foreach (aboutBuyer($questions) as $question) {
        expect($question->conditions)->not->toContain('buyer_is_grandchild');
    }
});

it('ne parle pas de l’acheteur sans son accord, sans son prénom, ni pour un conjoint ou soi-même', function (array $profile): void {
    expect(aboutBuyer(sent(profiled($profile))))->toBe([]);
})->with([
    'sur tous ses enfants' => [['buyer_relation' => BuyerRelation::Child, 'buyer_focus' => BuyerFocus::Everyone, 'buyer_first_name' => 'Claire']],
    'sans prénom' => [['buyer_relation' => BuyerRelation::Child, 'buyer_focus' => BuyerFocus::Buyer]],
    'le conjoint' => [['buyer_relation' => BuyerRelation::Partner, 'buyer_focus' => BuyerFocus::Buyer, 'buyer_first_name' => 'Jean']],
    'pour soi' => [['buyer_relation' => BuyerRelation::Myself, 'buyer_focus' => BuyerFocus::Buyer, 'buyer_first_name' => 'Jeanne']],
]);

it('avance les thèmes choisis sans oublier les autres', function (): void {
    $default = sent(profiled());
    $favored = sent(profiled(['favored_themes' => ['work', 'places']]));

    $count = fn (array $questions): int => count(array_filter($questions, fn (Question $question): bool => in_array($question->theme->value, ['work', 'places'], true)));
    $themes = fn (array $questions): int => count(array_unique(array_map(fn (Question $question): string => $question->theme->value, $questions)));

    expect($count($favored))->toBeGreaterThan($count($default))
        ->and($themes($favored))->toBeGreaterThanOrEqual((int) config('product.book_ready.min_themes'));
});

it('montre dans la file exactement ce qui partira', function (): void {
    $profile = [
        'facts' => ['partner' => false, 'career' => true],
        'avoided_topics' => ['illness'],
        'favored_themes' => ['joys'],
        'buyer_relation' => BuyerRelation::Grandchild,
        'buyer_focus' => BuyerFocus::Buyer,
        'buyer_first_name' => 'Léo',
        'buyer_gender' => GrammaticalGender::Masculine,
    ];

    $queue = app(PickNextQuestion::class)->queue(profiled($profile))->take(40)->pluck('slug')->all();

    // L'envoi réel attend six histoires validées avant l'intime ; la file,
    // elle, l'ignore (elle montre l'ordre, pas le calendrier). On compare donc
    // à un envoi où chaque histoire est validée aussitôt.
    expect(slugs(sent(profiled($profile), 40)))->toBe($queue);
});

it('nomme l’acheteur dans la question, accordée à son genre', function (): void {
    $project = profiled([
        'buyer_relation' => BuyerRelation::Child,
        'buyer_focus' => BuyerFocus::Buyer,
        'buyer_first_name' => 'Claire',
        'buyer_gender' => GrammaticalGender::Feminine,
    ]);
    $question = Question::query()->where('slug', 'prenom-naissance')->sole();

    $story = Story::factory()->forProject($project)->proposed()->create(['question_id' => $question->id]);

    expect($story->questionText())->toStartWith('Raconte le jour où Claire est née');
});
