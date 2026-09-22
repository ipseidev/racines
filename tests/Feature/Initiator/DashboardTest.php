<?php

declare(strict_types=1);

use App\Enums\Cadence;
use App\Enums\EngineAudience;
use App\Enums\EngineRuleId;
use App\Enums\Offer;
use App\Enums\ProjectStatus;
use App\Enums\TokenType;
use App\Models\EngineEvent;
use App\Models\FamilyMember;
use App\Models\Narrator;
use App\Models\Project;
use App\Models\ProjectQuestionSetting;
use App\Models\Question;
use App\Models\Story;
use App\Models\User;
use App\Services\Tokens\TokenService;
use App\States\Story\Shared;
use App\States\Story\Transcribed;
use Illuminate\Support\Collection;
use Inertia\Testing\AssertableInertia;

/**
 * L'espace de l'Initiateur·rice.
 *
 * Une seule chose y compte plus que le reste : **elle voit où en est chaque
 * histoire, jamais son contenu tant que le narrateur ne l'a pas partagée.**
 * Titre compris — un titre est déjà du contenu. C'est le même invariant que
 * pour les proches, et il vaut aussi pour celle qui paie : le narrateur est
 * souverain, y compris face à son enfant qui a offert le service.
 *
 * @return array{User, Project, Narrator}
 */
function initiator(array $projectOverrides = []): array
{
    $owner = User::factory()->create();
    $owner->markEmailAsVerified();

    $project = Project::factory()->create(array_merge([
        'owner_user_id' => $owner->id,
        'status' => ProjectStatus::Active,
    ], $projectOverrides));

    $narrator = Narrator::factory()->create([
        'project_id' => $project->id,
        'is_primary' => true,
        'first_name' => 'Jeanne',
    ]);

    return [$owner, $project->refresh(), $narrator];
}

it('renvoie une page d’attente quand aucun projet n’existe', function (): void {
    $user = User::factory()->create();
    $user->markEmailAsVerified();

    $this->actingAs($user)
        ->get('/espace')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('initiator/NoProject'));
});

it('montre l’état de chaque histoire et son titre seulement si partagée', function (): void {
    [$owner, $project] = initiator();

    $question = Question::factory()->create(['text' => 'Où avez-vous grandi ?']);

    $private = Story::factory()->create([
        'project_id' => $project->id,
        'question_id' => $question->id,
        'state' => Transcribed::class,
        'title' => 'Le village de mon enfance',
        'sequence' => 1,
    ]);

    $shared = Story::factory()->create([
        'project_id' => $project->id,
        'question_id' => $question->id,
        'state' => Shared::class,
        'title' => 'Ma première maison',
        'sequence' => 2,
        'shared_at' => now(),
    ]);

    $this->actingAs($owner)
        ->get(spaceUrl($project))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('initiator/Dashboard')
            ->has('stories', 2)
            // Dans le sens du temps : une frise se lit du passé vers ce
            // qui vient, et celle-ci se prolonge par les questions à venir.
            ->where('stories.0.id', $private->id)
            // Le titre d'une histoire non partagée n'apparaît pas : un titre
            // est déjà du contenu.
            ->where('stories.0.title', null)
            // La question, elle, est visible : c'est elle qui l'a choisie.
            ->where('stories.0.question', 'Où avez-vous grandi ?')
            ->where('stories.1.id', $shared->id)
            ->where('stories.1.title', 'Ma première maison'),
        );
});

it('ne rend jamais le texte ni l’audio d’une histoire', function (): void {
    [$owner, $project] = initiator();

    Story::factory()->create([
        'project_id' => $project->id,
        'state' => Shared::class,
        'shared_at' => now(),
    ]);

    $this->actingAs($owner)
        ->get(spaceUrl($project))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->missing('stories.0.body')
            ->missing('stories.0.transcript')
            ->missing('stories.0.audioUrl'),
        );
});

it('ne rend pas le lien de la semaine, il le réémet', function (): void {
    [$owner, $project] = initiator();

    Story::factory()->proposed()->create(['project_id' => $project->id]);

    // Les jetons sont stockés hachés : un lien en clair n'existe qu'entre son
    // émission et son envoi (bloc 03). Il ne peut donc pas être relu.
    $this->actingAs($owner)
        ->get(spaceUrl($project))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('hasCurrentStory', true)
            ->where('copiedLink', null),
        );

    $this->actingAs($owner)
        ->post(spaceUrl($project, '/lien/question'))
        ->assertRedirect();

    expect(session('copied_link'))->toBeString()
        ->and(session('copied_whatsapp'))->toContain('wa.me')
        ->and(session('copied_sms'))->toStartWith('sms:')
        ->and(session('copied_sms'))->toContain('body=');
});

it('ouvre l’écoute directement, avec un lien à soi', function (): void {
    [$owner, $project] = initiator();

    FamilyMember::factory()->create([
        'project_id' => $project->id,
        'invited_by_user_id' => $owner->id,
        'display_name' => $owner->name,
        'email' => $owner->email,
    ]);

    // Pas de lien à copier pour soi-même : la page d'écoute s'ouvre, et le
    // jeton est réémis au passage (T-149).
    $response = $this->actingAs($owner)->get(spaceUrl($project, '/ecoute'));

    $response->assertRedirect();

    expect($response->headers->get('Location'))->toContain('/l/');
});

it('dit le rythme en clair', function (): void {
    [$owner, $project] = initiator();

    $this->actingAs($owner)
        ->get(spaceUrl($project))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('project.cadenceLabel', fn (mixed $label): bool => is_string($label) && $label !== ''),
        );
});

it('montre les alertes du moteur qui lui sont adressées', function (): void {
    [$owner, $project] = initiator();

    EngineEvent::factory()->create([
        'project_id' => $project->id,
        'rule_id' => EngineRuleId::NarratorSilence21d,
        'action_taken' => ['told' => [EngineAudience::Initiator->value]],
        'outcome' => null,
    ]);

    EngineEvent::factory()->create([
        'project_id' => $project->id,
        'rule_id' => EngineRuleId::NarratorSilence21d,
        // Adressée au narrateur : ce n'est pas son affaire.
        'action_taken' => ['told' => [EngineAudience::Narrator->value]],
        'outcome' => null,
    ]);

    $this->actingAs($owner)
        ->get(spaceUrl($project))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('alerts', 1)
            ->where('alerts.0.ruleId', EngineRuleId::NarratorSilence21d->value)
            ->where('alerts.0.message', fn (mixed $message) => is_string($message)
                && ! str_starts_with($message, 'initiator.alert.')),
        );
});

it('réordonne, écarte et ajoute une question', function (): void {
    [$owner, $project] = initiator();

    $questions = Question::factory()->count(3)->create();

    /*
     * La file porte les deux natures depuis T-255 : chaque entrée dit la
     * sienne, et les rangs partent de 0 — celui qu'une question écrite par la
     * famille reçoit à sa création.
     */
    $this->actingAs($owner)
        ->post(spaceUrl($project, '/questions/ordre'), [
            'order' => $questions
                ->map(fn ($question): array => ['kind' => 'question', 'id' => $question->id])
                ->all(),
        ])
        ->assertRedirect();

    expect(ProjectQuestionSetting::query()
        ->where('project_id', $project->id)
        ->where('question_id', $questions[0]->id)
        ->firstOrFail()
        ->custom_order)->toBe(0);

    $this->actingAs($owner)
        ->post(spaceUrl($project, "/questions/{$questions[1]->id}/exclure"), ['excluded' => true])
        ->assertRedirect();

    expect(ProjectQuestionSetting::query()
        ->where('project_id', $project->id)
        ->where('question_id', $questions[1]->id)
        ->firstOrFail()
        ->excluded)->toBeTrue();

    $this->actingAs($owner)
        ->post(spaceUrl($project, '/questions/personnalisee'), [
            'text' => 'Quelle chanson te rappelle ton mariage ?',
        ])
        ->assertRedirect();

    // Une question de famille ne rejoint pas le corpus : elle devient une
    // histoire proposée avec son texte propre.
    expect(Story::query()
        ->where('project_id', $project->id)
        ->whereNotNull('custom_question_text')
        ->count())->toBe(1);
});

it('invite un proche et lui retire son accès', function (): void {
    [$owner, $project] = initiator();

    $this->actingAs($owner)
        ->post(spaceUrl($project, '/proches'), [
            'display_name' => 'Claire',
            'email' => 'claire@exemple.test',
        ])
        ->assertRedirect();

    $member = FamilyMember::query()->where('display_name', 'Claire')->firstOrFail();

    $this->actingAs($owner)
        ->delete(spaceUrl($project, "/proches/{$member->id}"))
        ->assertRedirect();

    // Retiré, pas supprimé : savoir qu'une personne a eu accès fait partie de
    // ce qu'on doit pouvoir répondre plus tard.
    expect($member->refresh()->removed_at)->not->toBeNull();
});

it('réémet le lien d’un proche et dit pour qui', function (): void {
    [$owner, $project] = initiator();

    $member = FamilyMember::factory()->create([
        'project_id' => $project->id,
        'invited_by_user_id' => $owner->id,
        'display_name' => 'Claire',
        'email' => 'claire@example.test',
    ]);

    $this->actingAs($owner)
        ->post(spaceUrl($project, "/proches/{$member->id}/renvoyer"))
        ->assertRedirect();

    expect(session('copied_link'))->toContain('/l/')
        ->and(session('copied_for'))->toBe($member->id);
});

it('masque les coordonnées des proches', function (): void {
    [$owner, $project] = initiator();

    FamilyMember::factory()->create([
        'project_id' => $project->id,
        'display_name' => 'Claire',
        'email' => 'claire@exemple.test',
        'phone_e164' => null,
    ]);

    // Cette page se laisse ouverte sur un écran : le carnet d'adresses d'une
    // famille n'a pas à s'y afficher.
    $this->actingAs($owner)
        ->get(spaceUrl($project, '/proches'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('members', fn (mixed $members): bool => collect($members)
                ->every(fn (array $member): bool => $member['contact'] === null
                    || ! str_contains((string) $member['contact'], 'claire@'))),
        );
});

it('change la cadence et recalcule le prochain envoi', function (): void {
    [$owner, $project] = initiator(['next_prompt_at' => now()->addDays(6)]);

    $this->actingAs($owner)
        ->post(spaceUrl($project, '/reglages'), [
            'cadence' => 'biweekly',
            'prompt_day' => 4,
            'prompt_slot' => 'evening',
            'address_form' => 'tu',
        ])
        ->assertRedirect();

    $project->refresh();

    // Recalculé tout de suite : sinon le réglage paraîtrait sans effet
    // jusqu'à la semaine suivante.
    expect($project->cadence->value)->toBe('biweekly')
        ->and($project->prompt_day)->toBe(4)
        ->and($project->next_prompt_at)->not->toBeNull()
        ->and($project->next_prompt_at?->dayOfWeekIso)->toBe(4);
});

it('ajoute un terme au lexique', function (): void {
    [$owner, $project] = initiator();

    $this->actingAs($owner)
        ->post(spaceUrl($project, '/reglages/lexique'), [
            'term' => 'Saint-Aubin-du-Cormier',
            'replacement' => 'Saint-Aubin-du-Cormier',
        ])
        ->assertRedirect();

    expect($project->lexiconEntries()->count())->toBe(1);
});

it('demande une pause qui a toujours un terme', function (): void {
    [$owner, $project] = initiator();

    $this->actingAs($owner)
        ->post(spaceUrl($project, '/reglages/pause'), ['weeks' => 3])
        ->assertRedirect();

    expect($project->refresh()->paused_until)->not->toBeNull();
});

it('n’ouvre l’espace de personne d’autre', function (): void {
    [, $project] = initiator();

    $intruder = User::factory()->create();
    $intruder->markEmailAsVerified();

    $member = FamilyMember::factory()->create(['project_id' => $project->id]);

    $this->actingAs($intruder)
        ->post(spaceUrl($project, "/proches/{$member->id}/renvoyer"))
        ->assertNotFound();
});

it('n’affiche pas le mandat quand le drapeau est fermé', function (): void {
    [$owner, $project] = initiator();

    // Une fonctionnalité fermée ne s'annonce pas (T-82).
    $this->actingAs($owner)
        ->get(spaceUrl($project, '/reglages'))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('mandateOpen', false));
});

it('exige un compte vérifié, sauf pour la commande', function (): void {
    $owner = User::factory()->unverified()->create();
    Project::factory()->create(['owner_user_id' => $owner->id]);

    $this->actingAs($owner)->get('/espace')->assertRedirect();

    // Un droit légal ne se conditionne pas à un clic dans une boîte de
    // réception.
    $this->actingAs($owner)->get('/espace/commandes')->assertOk();
});

/*
|--------------------------------------------------------------------------
| La frise et la jauge (T-257)
|--------------------------------------------------------------------------
|
| La page ne regardait que derrière elle : une pile d'histoires passées, et
| rien qui dise ce qui allait arriver ni ce que tout cela fabriquait. Les deux
| réponses existaient déjà ailleurs — `QuestionQueue` pour l'ordre d'envoi,
| `ComputeBookReadiness` pour la matière — mais sur d'autres pages.
|
*/

it('prolonge la frise par les questions à venir, avec leurs dates', function (): void {
    [$owner, $project] = initiator(['next_prompt_at' => now()->addDays(3)]);

    $question = Question::factory()->create(['text' => 'Quel métier vouliez-vous faire ?']);

    ProjectQuestionSetting::query()->create([
        'project_id' => $project->id,
        'question_id' => $question->id,
        'custom_order' => 1,
    ]);

    // Une question écrite par la famille : elle passe devant le fonds.
    Story::factory()->create([
        'project_id' => $project->id,
        'question_id' => null,
        'custom_question_text' => 'Comment avez-vous rencontré papa ?',
        'sequence' => 1,
        'queue_order' => 0,
    ]);

    $this->actingAs($owner)
        ->get(spaceUrl($project))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('upcoming.0.kind', 'story')
            ->where('upcoming.0.text', 'Comment avez-vous rencontré papa ?')
            // La date vient du planificateur, jamais d'un calcul refait ici.
            ->where('upcoming.0.sendAt', $project->next_prompt_at?->toIso8601String())
            ->where('upcoming.1.kind', 'question')
            ->where('upcoming.1.text', 'Quel métier vouliez-vous faire ?')
            ->etc()
        );
});

it('ne montre jamais les questions déjà parties dans ce qui vient', function (): void {
    [$owner, $project, $narrator] = initiator();

    $story = Story::factory()->create([
        'project_id' => $project->id,
        'question_id' => null,
        'custom_question_text' => 'Une question déjà posée',
        'sequence' => 1,
    ]);

    app(TokenService::class)->issue(
        TokenType::Record,
        $story,
        ['record'],
        issuedTo: $narrator,
    );

    $this->actingAs($owner)
        ->get(spaceUrl($project))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('upcoming', fn (Collection $entries): bool => $entries
                ->doesntContain(fn (array $entry): bool => $entry['text'] === 'Une question déjà posée'))
            ->etc()
        );
});

it('compte ce qui est parti sans jamais donner un nombre d’histoires à atteindre', function (): void {
    [$owner, $project] = initiator([
        'offer' => Offer::Core,
        'collection_started_at' => now()->subWeeks(7),
        'collection_ends_at' => now()->addWeeks(45),
    ]);

    Story::factory()->count(2)->sequence(
        ['sequence' => 1],
        ['sequence' => 2],
    )->create([
        'project_id' => $project->id,
        'state' => Shared::class,
        'shared_at' => now(),
        'recorded_at' => now(),
    ]);

    Story::factory()->create([
        'project_id' => $project->id,
        'state' => Transcribed::class,
        'sequence' => 3,
        'recorded_at' => now(),
        'shared_at' => null,
    ]);

    $this->actingAs($owner)
        ->get(spaceUrl($project))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('counts.asked', 3)
            ->where('counts.recorded', 3)
            ->where('counts.shared', 2)
            /*
             * Un seul dénominateur, et c'est celui qui est **vendu** :
             * 52 questions (R-2, v3.0), quel que soit le rythme. Jamais un
             * nombre d'histoires à atteindre pour le livre — R-6 refuse de
             * compter en récits — et jamais « sur 65 », qui ferait du fonds
             * un stock à épuiser.
             */
            ->where('counts.planned', 52)
            ->etc()
        );
});

/*
 * Le rythme change la durée, jamais le nombre.
 *
 * C'est l'inversion de la v3.0 du dossier : « 12 mois de collecte » donnait
 * 26 histoires à qui reçoit une question tous les quinze jours et 104 à qui
 * en reçoit deux par semaine, au même prix. Le nombre est ce qu'on achète.
 */
it('vend les mêmes 52 questions quel que soit le rythme', function (Cadence $cadence): void {
    [$owner, $project] = initiator([
        'offer' => Offer::Core,
        'cadence' => $cadence,
    ]);

    $project->startCollection(now());

    $this->actingAs($owner)
        ->get(spaceUrl($project))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('counts.planned', 52)
            // La date de fin, elle, suit le rythme : c'est une date et non
            // un dénominateur, et elle bouge quand le rythme bouge.
            ->where('counts.endsAt', $project->fresh()?->collection_ends_at?->toIso8601String())
            ->etc()
        );
})->with([
    'hebdomadaire' => [Cadence::Weekly],
    'deux fois par semaine' => [Cadence::TwiceWeekly],
    'trois fois par semaine' => [Cadence::ThriceWeekly],
    'tous les quinze jours' => [Cadence::Biweekly],
]);

it('n’affiche ni plan ni date tant que la collecte n’a pas commencé', function (): void {
    [$owner, $project] = initiator([
        'status' => ProjectStatus::AwaitingAcceptance,
        'collection_started_at' => null,
        'collection_ends_at' => null,
    ]);

    $this->actingAs($owner)
        ->get(spaceUrl($project))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('counts.planned', null)
            ->where('counts.endsAt', null)
            ->etc()
        );
});

it('mesure la matière selon R-6, et jamais en nombre d’histoires', function (): void {
    [$owner, $project] = initiator();

    $this->actingAs($owner)
        ->get(spaceUrl($project))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('readiness.words')
            ->has('readiness.audioMinutes')
            ->has('readiness.estimatedPages')
            ->has('readiness.themes')
            ->where('readiness.thresholds.words', config('product.book_ready.min_words'))
            ->where('readiness.thresholds.pages', config('product.book_ready.min_pages'))
            ->where('readiness.ready', false)
            ->etc()
        );
});
