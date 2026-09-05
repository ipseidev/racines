<?php

declare(strict_types=1);

use App\Enums\EngineAudience;
use App\Enums\EngineRuleId;
use App\Enums\ProjectStatus;
use App\Models\EngineEvent;
use App\Models\FamilyMember;
use App\Models\Narrator;
use App\Models\Project;
use App\Models\ProjectQuestionSetting;
use App\Models\Question;
use App\Models\Story;
use App\Models\User;
use App\States\Story\Shared;
use App\States\Story\Transcribed;
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
        ->get('/espace')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('initiator/Dashboard')
            ->has('stories', 2)
            // La plus récente d'abord.
            ->where('stories.0.id', $shared->id)
            ->where('stories.0.title', 'Ma première maison')
            ->where('stories.1.id', $private->id)
            // Le titre d'une histoire non partagée n'apparaît pas : un titre
            // est déjà du contenu.
            ->where('stories.1.title', null)
            // La question, elle, est visible : c'est elle qui l'a choisie.
            ->where('stories.1.question', 'Où avez-vous grandi ?'),
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
        ->get('/espace')
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
        ->get('/espace')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('hasCurrentStory', true)
            ->where('copiedLink', null),
        );

    $this->actingAs($owner)
        ->post('/espace/lien/question')
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
    $response = $this->actingAs($owner)->get('/espace/ecoute');

    $response->assertRedirect();

    expect($response->headers->get('Location'))->toContain('/l/');
});

it('dit le rythme en clair', function (): void {
    [$owner] = initiator();

    $this->actingAs($owner)
        ->get('/espace')
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
        ->get('/espace')
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

    $this->actingAs($owner)
        ->post('/espace/questions/ordre', ['order' => $questions->pluck('id')->all()])
        ->assertRedirect();

    expect(ProjectQuestionSetting::query()
        ->where('project_id', $project->id)
        ->where('question_id', $questions[0]->id)
        ->firstOrFail()
        ->custom_order)->toBe(1);

    $this->actingAs($owner)
        ->post("/espace/questions/{$questions[1]->id}/exclure", ['excluded' => true])
        ->assertRedirect();

    expect(ProjectQuestionSetting::query()
        ->where('project_id', $project->id)
        ->where('question_id', $questions[1]->id)
        ->firstOrFail()
        ->excluded)->toBeTrue();

    $this->actingAs($owner)
        ->post('/espace/questions/personnalisee', [
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
        ->post('/espace/proches', [
            'display_name' => 'Claire',
            'email' => 'claire@exemple.test',
        ])
        ->assertRedirect();

    $member = FamilyMember::query()->where('display_name', 'Claire')->firstOrFail();

    $this->actingAs($owner)
        ->delete("/espace/proches/{$member->id}")
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
        ->post("/espace/proches/{$member->id}/renvoyer")
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
        ->get('/espace/proches')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('members', fn (mixed $members): bool => collect($members)
                ->every(fn (array $member): bool => $member['contact'] === null
                    || ! str_contains((string) $member['contact'], 'claire@'))),
        );
});

it('change la cadence et recalcule le prochain envoi', function (): void {
    [$owner, $project] = initiator(['next_prompt_at' => now()->addDays(6)]);

    $this->actingAs($owner)
        ->post('/espace/reglages', [
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
        ->post('/espace/reglages/lexique', [
            'term' => 'Saint-Aubin-du-Cormier',
            'replacement' => 'Saint-Aubin-du-Cormier',
        ])
        ->assertRedirect();

    expect($project->lexiconEntries()->count())->toBe(1);
});

it('demande une pause qui a toujours un terme', function (): void {
    [$owner, $project] = initiator();

    $this->actingAs($owner)
        ->post('/espace/reglages/pause', ['weeks' => 3])
        ->assertRedirect();

    expect($project->refresh()->paused_until)->not->toBeNull();
});

it('n’ouvre l’espace de personne d’autre', function (): void {
    [, $project] = initiator();

    $intruder = User::factory()->create();
    $intruder->markEmailAsVerified();

    $member = FamilyMember::factory()->create(['project_id' => $project->id]);

    $this->actingAs($intruder)
        ->post("/espace/proches/{$member->id}/renvoyer")
        ->assertNotFound();
});

it('n’affiche pas le mandat quand le drapeau est fermé', function (): void {
    [$owner] = initiator();

    // Une fonctionnalité fermée ne s'annonce pas (T-82).
    $this->actingAs($owner)
        ->get('/espace/reglages')
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
