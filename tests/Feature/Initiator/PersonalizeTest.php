<?php

declare(strict_types=1);

use App\Actions\PickNextQuestion;
use App\Enums\AddressForm;
use App\Enums\BuyerFocus;
use App\Enums\BuyerRelation;
use App\Enums\GrammaticalGender;
use App\Enums\ProjectStatus;
use App\Models\Narrator;
use App\Models\Project;
use App\Models\ProjectProfile;
use App\Models\Story;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

/*
 * Le tunnel de personnalisation, après l'achat.
 *
 * Ce qu'il protège : ce que la famille répond arrive tel quel dans le profil
 * que lit le choix des questions, les trois premières questions montrées sont
 * celles qui partiront vraiment, et passer le tunnel ne retire rien.
 */

/** @return array{User, Project} */
function personalizeProject(): array
{
    $owner = User::factory()->create();
    $owner->markEmailAsVerified();

    $project = Project::factory()->create([
        'owner_user_id' => $owner->id,
        'status' => ProjectStatus::Active,
        'address_form' => AddressForm::Tu,
    ]);

    Narrator::factory()->create(['project_id' => $project->id, 'is_primary' => true, 'first_name' => 'Jeanne']);

    return [$owner, $project->refresh()];
}

/** @return array<string, mixed> */
function answers(array $overrides = []): array
{
    return array_merge([
        'relation' => 'child',
        'narrator_gender' => 'feminine',
        'buyer_focus' => 'buyer',
        'buyer_first_name' => 'Claire',
        'buyer_gender' => 'feminine',
        'facts' => ['partner' => true, 'children' => true, 'career' => false],
        'avoided_topics' => ['war'],
        'favored_themes' => ['joys', 'places'],
    ], $overrides);
}

it('ouvre le tunnel avec ce qu’il faut pour le dessiner', function (): void {
    [$owner, $project] = personalizeProject();

    $this->actingAs($owner)
        ->get(spaceUrl($project, '/personnaliser'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('initiator/Personalize')
            ->where('narratorFirstName', 'Jeanne')
            ->where('step', null)
            ->has('themes', 10)
            ->has('deck', 3)
            ->where('previews.child.feminine.feminine', fn (string $text): bool => str_contains($text, '{{prénom}}') && str_contains($text, 'née'))
            ->where('profile', null),
        );
});

it('enregistre les réponses dans le profil et le genre sur la narratrice', function (): void {
    [$owner, $project] = personalizeProject();

    $this->actingAs($owner)
        ->post(spaceUrl($project, '/personnaliser'), answers())
        ->assertRedirect(spaceUrl($project, '/personnaliser').'?etape=premieres');

    $profile = ProjectProfile::query()->where('project_id', $project->id)->sole();

    expect($profile->buyer_relation)->toBe(BuyerRelation::Child)
        ->and($profile->buyer_focus)->toBe(BuyerFocus::Buyer)
        ->and($profile->buyer_first_name)->toBe('Claire')
        ->and($profile->buyer_gender)->toBe(GrammaticalGender::Feminine)
        ->and($profile->facts)->toEqual(['partner' => true, 'children' => true, 'career' => false])
        ->and($profile->avoided_topics)->toBe(['war'])
        ->and($profile->favored_themes)->toBe(['joys', 'places'])
        ->and($profile->completed_at)->not->toBeNull()
        ->and($project->primaryNarrator()->sole()->grammatical_gender)->toBe(GrammaticalGender::Feminine);
});

it('oublie le prénom quand on ne veut pas de questions sur soi', function (): void {
    [$owner, $project] = personalizeProject();

    $this->actingAs($owner)->post(spaceUrl($project, '/personnaliser'), answers(['buyer_focus' => null]));

    $profile = ProjectProfile::query()->where('project_id', $project->id)->sole();

    expect($profile->buyer_focus)->toBeNull()
        ->and($profile->buyer_first_name)->toBeNull()
        ->and($profile->buyer_gender)->toBeNull();
});

it('refuse des réponses qui ne veulent rien dire', function (array $bad, string $field): void {
    [$owner, $project] = personalizeProject();

    $this->actingAs($owner)
        ->post(spaceUrl($project, '/personnaliser'), answers($bad))
        ->assertSessionHasErrors($field);
})->with([
    'un lien inconnu' => [['relation' => 'voisin'], 'relation'],
    'une condition inventée' => [['facts' => ['chien' => true]], 'facts'],
    'un sujet inconnu' => [['avoided_topics' => ['impots']], 'avoided_topics.0'],
    'quatre thèmes' => [['favored_themes' => ['joys', 'places', 'work', 'love']], 'favored_themes'],
    'sur moi sans prénom' => [['buyer_first_name' => ''], 'buyer_first_name'],
    'un conjoint sans genre' => [['relation' => 'partner', 'narrator_gender' => null], 'narrator_gender'],
]);

it('montre les trois premières questions qui partiront vraiment', function (): void {
    [$owner, $project] = personalizeProject();
    $this->actingAs($owner)->post(spaceUrl($project, '/personnaliser'), answers());

    $expected = app(PickNextQuestion::class)->queue($project->refresh())
        ->reject(fn ($question): bool => in_array('about_buyer', $question->conditions, true))
        ->take(3)->pluck('id')->all();

    $this->actingAs($owner)
        ->get(spaceUrl($project, '/personnaliser').'?etape=premieres')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('step', 'premieres')
            ->has('firstQuestions', 3)
            ->where('firstQuestions', fn ($questions): bool => collect($questions)->pluck('id')->all() === $expected)
            ->where('firstQuestions.0.text', fn (string $text): bool => ! str_contains($text, '{')),
        );
});

it('ne propose jamais de commencer par une question sur l’acheteur', function (): void {
    // Un projet déjà commencé : cinq questions parties, la suivante sur
    // l'acheteur est due. Elle garde sa place ; elle n'ouvre pas le livre.
    [$owner, $project] = personalizeProject();
    $this->actingAs($owner)->post(spaceUrl($project, '/personnaliser'), answers());

    foreach (app(PickNextQuestion::class)->queue($project->refresh())->take(5) as $question) {
        Story::factory()->forProject($project)->create(['question_id' => $question->id, 'state' => 'validated']);
    }

    expect(in_array('about_buyer', app(PickNextQuestion::class)->queue($project->refresh())->first()?->conditions ?? [], true))->toBeTrue();

    $this->actingAs($owner)
        ->get(spaceUrl($project, '/personnaliser').'?etape=premieres')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('firstQuestions', fn ($questions): bool => collect($questions)->every(fn ($q): bool => ! str_contains($q['text'], 'Claire'))),
        );
});

it('fait partir en premier la question choisie', function (): void {
    [$owner, $project] = personalizeProject();
    $this->actingAs($owner)->post(spaceUrl($project, '/personnaliser'), answers());

    $third = app(PickNextQuestion::class)->queue($project->refresh())->get(2);

    $this->actingAs($owner)
        ->post(spaceUrl($project, '/personnaliser/premiere'), ['question_id' => $third->id])
        ->assertRedirect(spaceUrl($project, '/personnaliser').'?etape=fin');

    expect(app(PickNextQuestion::class)->handle($project->refresh())?->id)->toBe($third->id);
});

it('refuse de faire partir en premier une question qui n’était pas proposée', function (): void {
    [$owner, $project] = personalizeProject();
    $this->actingAs($owner)->post(spaceUrl($project, '/personnaliser'), answers());

    $far = app(PickNextQuestion::class)->queue($project->refresh())->get(30);

    $this->actingAs($owner)
        ->post(spaceUrl($project, '/personnaliser/premiere'), ['question_id' => $far->id])
        ->assertSessionHasErrors('question_id');
});

it('passe le tunnel sans rien retirer', function (): void {
    [$owner, $project] = personalizeProject();
    $before = app(PickNextQuestion::class)->queue($project)->pluck('id')->all();

    $this->actingAs($owner)
        ->post(spaceUrl($project, '/personnaliser/passer'))
        ->assertRedirect(spaceUrl($project, '/personnaliser').'?etape=fin');

    $profile = ProjectProfile::query()->where('project_id', $project->id)->sole();

    expect($profile->skipped_at)->not->toBeNull()
        ->and(app(PickNextQuestion::class)->queue($project->refresh())->pluck('id')->all())->toBe($before);
});

it('reprend les réponses déjà données quand on revient', function (): void {
    [$owner, $project] = personalizeProject();
    $this->actingAs($owner)->post(spaceUrl($project, '/personnaliser'), answers());

    $this->actingAs($owner)
        ->get(spaceUrl($project, '/personnaliser'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('profile.relation', 'child')
            ->where('profile.narratorGender', 'feminine')
            ->where('profile.buyerFirstName', 'Claire')
            ->where('profile.favoredThemes', ['joys', 'places']),
        );
});

it('ne propose pas le tunnel à qui raconte sa propre histoire', function (): void {
    [$owner, $project] = personalizeProject();
    ProjectProfile::query()->create(['project_id' => $project->id, 'buyer_relation' => BuyerRelation::Myself]);

    $this->actingAs($owner)
        ->get(spaceUrl($project, '/personnaliser'))
        ->assertRedirect(spaceUrl($project));
});

it('garde le tunnel d’un projet pour sa famille', function (): void {
    [, $project] = personalizeProject();
    $stranger = User::factory()->create();
    $stranger->markEmailAsVerified();

    $this->actingAs($stranger)->get(spaceUrl($project, '/personnaliser'))->assertNotFound();
    $this->actingAs($stranger)->post(spaceUrl($project, '/personnaliser'), answers())->assertNotFound();
});

it('mène l’ancienne porte au tunnel du projet', function (): void {
    [$owner, $project] = personalizeProject();

    $this->actingAs($owner)
        ->get('/espace/personnaliser')
        ->assertRedirect(spaceUrl($project, '/personnaliser'));
});

it('invite à personnaliser depuis le tableau de bord, jusqu’à ce que ce soit fait ou passé', function (): void {
    [$owner, $project] = personalizeProject();

    $this->actingAs($owner)
        ->get(spaceUrl($project))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('personalize', spaceUrl($project, '/personnaliser')));

    $this->actingAs($owner)->post(spaceUrl($project, '/personnaliser/passer'));

    $this->actingAs($owner)
        ->get(spaceUrl($project))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('personalize', null));
});
