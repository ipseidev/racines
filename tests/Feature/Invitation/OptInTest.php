<?php

declare(strict_types=1);

use App\Enums\Cadence;
use App\Enums\Channel;
use App\Enums\ConsentKind;
use App\Enums\PostMortemWish;
use App\Enums\ProjectStatus;
use App\Enums\PromptSlot;
use App\Enums\RefusalReason;
use App\Enums\SupportTicketKind;
use App\Enums\TokenType;
use App\Models\Consent;
use App\Models\Invitation;
use App\Models\Narrator;
use App\Models\PostMortemDirective;
use App\Models\Project;
use App\Models\SupportTicket;
use App\Notifications\InvitationRefusedNotification;
use App\Services\Tokens\TokenService;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia;

/**
 * Le moment H0.
 *
 * Le test le plus important du bloc, et sans doute du produit après la
 * garde de visibilité : c'est ici qu'un cadeau devient un consentement, ou
 * qu'il devient un refus respecté.
 *
 * Ce que la page **ne fait pas** est éprouvé aussi sérieusement que ce qu'elle
 * fait : aucun enregistrement n'est proposé avant l'acceptation, et aucune
 * question ne part avant `accepted_at`.
 *
 * @return array{Project, Narrator, string}
 */
function invitedProject(array $projectOverrides = []): array
{
    $project = Project::factory()->create(array_merge([
        'status' => ProjectStatus::AwaitingAcceptance,
        'gift_message' => 'J’aimerais garder tes histoires, maman.',
        'cadence' => Cadence::Weekly,
        'prompt_day' => 1,
        'prompt_slot' => PromptSlot::Morning,
    ], $projectOverrides));

    $narrator = Narrator::factory()->byEmail()->create([
        'project_id' => $project->id,
        'is_primary' => true,
        'first_name' => 'Jeanne',
        'opted_in_at' => null,
    ]);

    $issued = app(TokenService::class)->issue(
        TokenType::Invitation,
        $project,
        ['opt_in'],
        now()->addDays(30),
        $project->owner,
        issuedTo: $narrator,
    );

    Invitation::factory()->create([
        'project_id' => $project->id,
        'narrator_id' => $narrator->id,
        'token_id' => $issued->token->id,
        'attempt' => 1,
        'sent_at' => now()->subHour(),
        'opened_at' => null,
        'accepted_at' => null,
        'refused_at' => null,
    ]);

    return [$project->refresh(), $narrator, $issued->plain];
}

/** @return array<string, bool|string|int> */
function acceptancePayload(array $overrides = []): array
{
    return array_merge([
        'consent_voice_recording' => true,
        'consent_transcription' => true,
        'consent_ai_rendering' => true,
        'consent_family_sharing' => true,
        'consent_sensitive_categories' => true,
        'preferred_channel' => Channel::Email->value,
        'cadence' => Cadence::Weekly->value,
        'prompt_day' => 3,
        'prompt_slot' => PromptSlot::Evening->value,
        'address_form' => 'tu',
    ], $overrides);
}

it('montre le message personnel et jamais d’invitation à enregistrer', function (): void {
    [, , $plain] = invitedProject();

    $this->get("/i/{$plain}")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('narrator/OptIn')
            ->where('personalMessage', 'J’aimerais garder tes histoires, maman.')
            ->has('inviterName')
            ->has('consents', 5)
            // Aucun micro, aucune question, aucun aperçu : quelqu'un qui
            // découvre le service par un cadeau doit pouvoir comprendre de
            // quoi il s'agit sans être déjà en train de faire quelque chose.
            ->missing('question')
            ->missing('limits')
            ->missing('recordAction'),
        );
});

it('marque l’invitation comme vue', function (): void {
    [, $narrator, $plain] = invitedProject();

    $this->get("/i/{$plain}")->assertOk();

    // « Vu » sépare « jamais reçu » de « reçu et pas répondu », et c'est toute
    // la différence entre relancer et respecter un silence.
    expect(Invitation::query()->where('narrator_id', $narrator->id)->firstOrFail()->opened_at)
        ->not->toBeNull();
});

it('exige cinq consentements distincts', function (): void {
    [, , $plain] = invitedProject();

    foreach ([
        'consent_voice_recording',
        'consent_transcription',
        'consent_ai_rendering',
        'consent_family_sharing',
        'consent_sensitive_categories',
    ] as $field) {
        $this->post("/i/{$plain}/accepter", acceptancePayload([$field => false]))
            ->assertSessionHasErrors($field);
    }
});

it('ne propose au narrateur que le SMS, le courriel, ou les deux', function (): void {
    [, , $plain] = invitedProject();

    // Le téléphone opéré est l'option D-9 : il se vend, il ne se choisit pas
    // depuis une invitation (T-234).
    $this->get("/i/{$plain}")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('narrator/OptIn')
            ->where('channels', fn ($channels) => collect($channels)->pluck('value')->all() === ['sms', 'email', 'both'])
            ->where('email', fn ($email) => is_string($email) && str_contains($email, '@')));
});

it('refuse le canal du téléphone opéré', function (): void {
    [$project, , $plain] = invitedProject();

    $this->post("/i/{$plain}/accepter", acceptancePayload(['preferred_channel' => Channel::PhoneOperator->value]))
        ->assertSessionHasErrors('preferred_channel');

    expect($project->refresh()->accepted_at)->toBeNull();
});

it('enregistre l’adresse corrigée, en minuscules', function (): void {
    [, $narrator, $plain] = invitedProject();

    $this->post("/i/{$plain}/accepter", acceptancePayload(['narrator_email' => '  Jeanne.Nouvelle@Example.test ']))
        ->assertRedirect();

    expect($narrator->refresh()->email)->toBe('jeanne.nouvelle@example.test');
});

it('refuse le SMS sans numéro, à l’endroit du champ', function (): void {
    [$project, $narrator, $plain] = invitedProject();

    // Invitée par courriel, sans numéro connu : choisir le SMS sans en donner
    // un, c'est accepter un cadeau dont aucune question n'arriverait.
    expect($narrator->phone_e164)->toBeNull();

    $this->post("/i/{$plain}/accepter", acceptancePayload(['preferred_channel' => Channel::Sms->value]))
        ->assertSessionHasErrors('narrator_phone');

    expect($project->refresh()->accepted_at)->toBeNull();

    // Avec un numéro, tapé comme on le tape, ça passe.
    $this->post("/i/{$plain}/accepter", acceptancePayload([
        'preferred_channel' => Channel::Sms->value,
        'narrator_phone' => '06 12 34 56 78',
    ]))->assertRedirect();

    expect($narrator->refresh()->phone_e164)->toBe('+33612345678');
});

it('active le projet et planifie la première question le lendemain', function (): void {
    [$project, $narrator, $plain] = invitedProject();

    $this->post("/i/{$plain}/accepter", acceptancePayload())
        ->assertRedirect("/i/{$plain}/bienvenue");

    $project->refresh();

    expect($project->status)->toBe(ProjectStatus::Active)
        ->and($project->accepted_at)->not->toBeNull()
        ->and($project->collection_started_at)->not->toBeNull()
        ->and($project->collection_ends_at?->toDateString())
        ->toBe(now()->addWeeks(12)->toDateString())
        ->and($project->finalization_ends_at?->toDateString())
        ->toBe(now()->addWeeks(16)->toDateString())
        // Le lendemain, pas dans l'heure : une question qui arrive dans la
        // minute donne l'impression d'une machine qui attendait.
        ->and($project->next_prompt_at)->not->toBeNull()
        ->and($project->next_prompt_at?->greaterThan(now()))->toBeTrue()
        // Les préférences choisies à l'opt-in gagnent contre celles saisies
        // par l'acheteur : c'est la personne qui raconte qui décide du rythme.
        ->and($project->prompt_day)->toBe(3)
        ->and($project->prompt_slot)->toBe(PromptSlot::Evening)
        ->and($project->address_form->value)->toBe('tu');

    expect($narrator->refresh()->opted_in_at)->not->toBeNull()
        ->and($narrator->contact_deletion_due_at)->toBeNull();
});

it('journalise les cinq consentements avec la version du texte lu', function (): void {
    [$project, $narrator, $plain] = invitedProject();

    $this->post("/i/{$plain}/accepter", acceptancePayload());

    $consents = Consent::query()
        ->where('project_id', $project->id)
        ->where('subject_id', $narrator->id)
        ->get();

    expect($consents)->toHaveCount(5);

    foreach ([
        ConsentKind::VoiceRecording,
        ConsentKind::Transcription,
        ConsentKind::AiRendering,
        ConsentKind::FamilySharing,
        ConsentKind::SensitiveCategories,
    ] as $kind) {
        $consent = $consents->firstWhere('kind', $kind);

        expect($consent)->not->toBeNull()
            // Sans version, on ne peut pas dire ce qui a été accepté.
            ->and($consent->text_version)->not->toBeNull()
            ->and($consent->granted_at)->not->toBeNull();
    }
});

it('note l’acceptation sur l’invitation, avec son numéro d’envoi', function (): void {
    [, $narrator, $plain] = invitedProject();

    $this->post("/i/{$plain}/accepter", acceptancePayload());

    $invitation = Invitation::query()->where('narrator_id', $narrator->id)->firstOrFail();

    expect($invitation->accepted_at)->not->toBeNull()
        ->and($invitation->attempt)->toBe(1);
});

it('propose la fiche contact sur l’écran de bienvenue, sans redemander les souhaits', function (): void {
    [, , $plain] = invitedProject();

    $this->post("/i/{$plain}/accepter", acceptancePayload());

    $this->get("/i/{$plain}/bienvenue")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('narrator/OptInWelcome')
            ->where('firstName', 'Jeanne')
            ->has('vcardUrl')
            ->has('nextPromptAt')
            // Les souhaits ne sont plus demandés ici (T-236) : la page dit
            // seulement s'ils ont été choisis.
            ->missing('wishes')
            ->where('directivesRecorded', false),
        );
});

it('propose les souhaits sur la page d’acceptation, « transmettre » d’avance', function (): void {
    [, , $plain] = invitedProject();

    $this->get("/i/{$plain}")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('narrator/OptIn')
            ->has('wishes', 3)
            ->where('defaultWish', PostMortemWish::TransferToFamily->value));
});

it('n’écrit aucune directive quand les souhaits restent au choix proposé', function (): void {
    [, , $plain] = invitedProject();

    // Le choix proposé n'est pas un choix : c'est la politique sans directive
    // (doc 04 §6). Une directive s'accompagne d'un consentement journalisé, et
    // un journal de cases remplies d'avance ne prouverait rien (T-236).
    $this->post("/i/{$plain}/accepter", acceptancePayload([
        'wishes' => PostMortemWish::TransferToFamily->value,
        'referent_name' => '',
        'referent_contact' => '',
    ]))->assertRedirect();

    expect(PostMortemDirective::query()->count())->toBe(0);
});

it('écrit la directive dès que la personne choisit autre chose à l’acceptation', function (): void {
    [$project, $narrator, $plain] = invitedProject();

    $this->post("/i/{$plain}/accepter", acceptancePayload([
        'wishes' => PostMortemWish::Freeze->value,
    ]))->assertRedirect();

    $directive = PostMortemDirective::query()->firstOrFail();

    expect($directive->wishes)->toBe(PostMortemWish::Freeze)
        ->and($directive->narrator_id)->toBe($narrator->id)
        ->and($directive->project_id)->toBe($project->id)
        ->and($directive->consent_id)->not->toBeNull();

    // Et la bienvenue le sait.
    $this->get("/i/{$plain}/bienvenue")
        ->assertInertia(fn (AssertableInertia $page) => $page->where('directivesRecorded', true));
});

it('écrit la directive quand la personne désigne quelqu’un, même sans changer le choix', function (): void {
    [, , $plain] = invitedProject();

    $this->post("/i/{$plain}/accepter", acceptancePayload([
        'wishes' => PostMortemWish::TransferToFamily->value,
        'referent_name' => 'Claire',
        'referent_contact' => 'Claire@Exemple.FR',
    ]))->assertRedirect();

    $directive = PostMortemDirective::query()->firstOrFail();

    expect($directive->wishes)->toBe(PostMortemWish::TransferToFamily)
        ->and($directive->referent_name)->toBe('Claire')
        ->and($directive->referent_contact_masked)->toBe('Cl•••@Exemple.FR');
});

it('laisse remettre les souhaits à plus tard, puis les enregistrer', function (): void {
    [$project, $narrator, $plain] = invitedProject();

    $this->post("/i/{$plain}/accepter", acceptancePayload());

    // « Plus tard » ne poste rien : on ne demande pas à quelqu'un qui vient
    // d'accepter de raconter sa vie de penser d'abord à sa mort.
    expect(PostMortemDirective::query()->count())->toBe(0);

    $this->post("/i/{$plain}/souhaits", [
        'wishes' => PostMortemWish::Freeze->value,
        'referent_name' => 'Claire',
        'referent_contact' => 'Claire@Exemple.FR',
    ])->assertRedirect("/i/{$plain}/bienvenue");

    $directive = PostMortemDirective::query()->firstOrFail();

    expect($directive->wishes)->toBe(PostMortemWish::Freeze)
        ->and($directive->narrator_id)->toBe($narrator->id)
        ->and($directive->project_id)->toBe($project->id)
        // Masqué et haché, jamais en clair : on doit pouvoir vérifier qu'une
        // personne est bien celle désignée, sans garder le carnet d'adresses
        // d'une famille en deuil.
        ->and($directive->referent_contact_masked)->toBe('Cl•••@Exemple.FR')
        ->and($directive->referent_contact_hash)
        ->toBe(hash('sha256', 'claire@exemple.fr'))
        // Une directive sans consentement journalisé n'a aucune valeur.
        ->and($directive->consent_id)->not->toBeNull();
});

it('remplace la directive précédente au lieu d’en empiler', function (): void {
    [, , $plain] = invitedProject();

    $this->post("/i/{$plain}/accepter", acceptancePayload());

    $this->post("/i/{$plain}/souhaits", ['wishes' => PostMortemWish::Delete->value]);
    $this->post("/i/{$plain}/souhaits", ['wishes' => PostMortemWish::TransferToFamily->value]);

    // Savoir que quelqu'un a d'abord voulu tout supprimer puis changé d'avis
    // n'aide personne, et pourrait servir contre lui.
    expect(PostMortemDirective::query()->count())->toBe(1)
        ->and(PostMortemDirective::query()->firstOrFail()->wishes)
        ->toBe(PostMortemWish::TransferToFamily);
});

it('accepte un refus sans motif, et prévient l’Initiateur·rice avec tact', function (): void {
    Notification::fake();

    [$project, $narrator, $plain] = invitedProject();

    $this->post("/i/{$plain}/refuser", [])
        ->assertRedirect('/i/farewell');

    $project->refresh();
    $narrator->refresh();

    expect($project->refused_at)->not->toBeNull()
        ->and($project->refusal_reason)->toBeNull()
        // Plus une seule question ne part.
        ->and($project->next_prompt_at)->toBeNull()
        ->and($narrator->opted_out_at)->not->toBeNull()
        // On ne garde pas le téléphone de quelqu'un qui a dit non.
        ->and($narrator->contact_deletion_due_at?->toDateString())
        ->toBe(now()->addDays(30)->toDateString());

    Notification::assertSentTo($project->owner, InvitationRefusedNotification::class);

    // Le remboursement se propose, il ne s'attend pas.
    expect(SupportTicket::query()
        ->where('project_id', $project->id)
        ->where('kind', SupportTicketKind::RefundOffer)
        ->exists())->toBeTrue();
});

it('accepte un motif de refus quand il est donné', function (): void {
    Notification::fake();

    [$project, , $plain] = invitedProject();

    $this->post("/i/{$plain}/refuser", ['reason' => RefusalReason::NotTheRightTime->value]);

    expect($project->refresh()->refusal_reason)
        ->toBe(RefusalReason::NotTheRightTime->value);
});

it('refuse un motif inventé', function (): void {
    [, , $plain] = invitedProject();

    $this->post("/i/{$plain}/refuser", ['reason' => 'parce_que'])
        ->assertSessionHasErrors('reason');
});

it('ne redemande rien à quelqu’un qui a déjà répondu', function (): void {
    [, , $plain] = invitedProject();

    $this->post("/i/{$plain}/accepter", acceptancePayload());

    $this->get("/i/{$plain}")->assertInertia(fn (AssertableInertia $page) => $page
        ->where('answered', true),
    );
});

it('n’envoie aucune question avant l’acceptation', function (): void {
    // Critère de sortie du bloc. `prompts:dispatch-due` filtre sur les projets
    // actifs, et un projet ne devient actif qu'à l'acceptation.
    [$project] = invitedProject();

    expect($project->status)->toBe(ProjectStatus::AwaitingAcceptance)
        ->and($project->next_prompt_at)->toBeNull();

    $this->artisan('prompts:dispatch-due')->assertSuccessful();

    expect($project->refresh()->stories()->count())->toBe(0);
});

it('refuse un jeton d’un autre périmètre', function (): void {
    [$project, $narrator] = invitedProject();

    // Un jeton d'espace narrateur n'ouvre pas l'opt-in : périmètre strict
    // (bloc 03), un lien = un usage.
    $issued = app(TokenService::class)->issue(
        TokenType::NarratorSpace,
        $narrator,
        ['space'],
        now()->addDays(30),
    );

    $this->get("/i/{$issued->plain}")->assertNotFound();

    expect($project->refresh()->accepted_at)->toBeNull();
});
