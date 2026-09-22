<?php

declare(strict_types=1);

use App\Actions\InviteFamilyMember;
use App\Actions\ReissueFamilyLink;
use App\Enums\OutboundMessageStatus;
use App\Enums\TokenType;
use App\Models\AccessToken;
use App\Models\FamilyMember;
use App\Models\Narrator;
use App\Models\OutboundMessage;
use App\Models\Project;
use App\Notifications\Channels\SmsChannel;
use App\Notifications\FamilyInvitationNotification;
use App\Services\Tokens\TokenService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

it('crée le proche, son lien de la durée réglée et son invitation', function (): void {
    Notification::fake();
    $project = Project::factory()->create();

    $member = app(InviteFamilyMember::class)->handle($project, $project->owner, [
        'display_name' => 'Marie',
        'email' => 'marie@example.test',
    ]);

    $token = AccessToken::query()
        ->where('subject_type', $member->getMorphClass())
        ->where('subject_id', $member->id)
        ->sole();

    expect($member->project_id)->toBe($project->id)
        ->and($member->display_name)->toBe('Marie')
        ->and($token->type)->toBe(TokenType::ListenProject)
        /*
         * La durée **du réglage**, à la date près.
         *
         * Elle était écrite en dur dans l'action, et ce test l'acceptait :
         * « plus de 360 jours » passait aussi bien pour douze mois que pour
         * vingt-sept. Le réglage a été porté à vingt-sept sans effet, et
         * personne ne l'a vu — un seuil large ne garde rien.
         */
        ->and($token->expires_at?->toDateString())
        ->toBe(now()->addMonths((int) config('product.tokens.listen_project_months'))->toDateString())
        ->and($token->scope)->toContain('listen');

    Notification::assertSentTo($member, FamilyInvitationNotification::class);
});

it('n’émet jamais un lien partagé entre plusieurs proches', function (): void {
    Notification::fake();
    $project = Project::factory()->create();

    $first = app(InviteFamilyMember::class)->handle($project, $project->owner, ['display_name' => 'Marie', 'email' => 'marie@example.test']);
    $second = app(InviteFamilyMember::class)->handle($project, $project->owner, ['display_name' => 'Paul', 'email' => 'paul@example.test']);

    // Le lien est personnel et révocable : deux proches, deux jetons. Un lien
    // « famille » commun serait irrévocable sans punir tout le monde.
    expect(AccessToken::query()->where('type', TokenType::ListenProject->value)->count())->toBe(2)
        ->and($first->id)->not->toBe($second->id);
});

it('invite par SMS quand il n’y a pas de courriel', function (): void {
    Notification::fake();
    $project = Project::factory()->create();

    $member = app(InviteFamilyMember::class)->handle($project, $project->owner, [
        'display_name' => 'Paul',
        'phone_e164' => '+33600000021',
    ]);

    Notification::assertSentTo(
        $member,
        FamilyInvitationNotification::class,
        function (FamilyInvitationNotification $notification) use ($member): bool {
            return in_array(SmsChannel::class, $notification->via($member), true);
        },
    );
});

it('trace l’envoi dans les messages sortants', function (): void {
    Mail::fake();
    $project = Project::factory()->create();

    $member = app(InviteFamilyMember::class)->handle($project, $project->owner, [
        'display_name' => 'Marie',
        'email' => 'marie@example.test',
    ]);

    $message = OutboundMessage::query()->where('template', 'family_invitation')->sole();

    expect($message->project_id)->toBe($project->id)
        ->and($message->status)->not->toBe(OutboundMessageStatus::Failed)
        // Le lien ne se retrouve pas en clair dans la trace : un message
        // sortant est consultable au support (bloc 11).
        ->and(json_encode($message->payload))->not->toContain('/l/');
});

it('refuse un proche sans aucune coordonnée', function (): void {
    $project = Project::factory()->create();

    // Sans coordonnée, l'Initiateur·rice copie le lien et le transmet
    // lui-même (règle §9) — mais on ne crée pas un proche muet en silence.
    app(InviteFamilyMember::class)->handle($project, $project->owner, ['display_name' => 'Sans contact']);
})->throws(InvalidArgumentException::class);

it('fait tourner le lien d’un proche sans en créer un second valable', function (): void {
    Notification::fake();
    $project = Project::factory()->create();
    $member = app(InviteFamilyMember::class)->handle($project, $project->owner, [
        'display_name' => 'Marie',
        'email' => 'marie@example.test',
    ]);

    $issued = app(ReissueFamilyLink::class)->handle($member);

    $live = AccessToken::query()
        ->where('subject_id', $member->id)
        ->whereNull('revoked_at')
        ->get();

    // Un seul lien vivant : l'ancien est révoqué, sans quoi un lien transmis
    // par erreur resterait ouvert après qu'on a cru le remplacer.
    expect($live)->toHaveCount(1)
        ->and($live->first()?->token_hash)->toBe(TokenService::hash($issued->plain));
});

it('invite depuis la ligne de commande', function (): void {
    Notification::fake();
    $project = Project::factory()->create();

    $this->artisan('family:invite', [
        'project' => $project->id,
        'name' => 'Marie',
        'contact' => 'marie@example.test',
    ])->assertSuccessful();

    expect(FamilyMember::query()->where('display_name', 'Marie')->exists())->toBeTrue();
});

/*
 * Le lien d'écoute vit le temps du **projet**, et l'offre ne dure plus douze
 * mois pour tout le monde (R-2, v3.0) : au rythme quinzomadaire, la collecte
 * s'étale sur deux ans. Un lien de douze mois mourait alors en pleine
 * collecte, et le proche perdait l'accès pendant que les histoires arrivaient
 * encore.
 *
 * Ce test change le réglage plutôt que d'en recopier la valeur : c'est la
 * seule forme qui échoue si la durée revient en dur dans l'action.
 */
it('suit le réglage quand la durée des liens d’écoute change', function (): void {
    Notification::fake();
    config()->set('product.tokens.listen_project_months', 27);

    $project = Project::factory()->create();

    $member = app(InviteFamilyMember::class)->handle($project, $project->owner, [
        'display_name' => 'Marie',
        'email' => 'marie@example.test',
    ]);

    $invitation = AccessToken::query()
        ->where('subject_type', $member->getMorphClass())
        ->where('subject_id', $member->id)
        ->sole();

    expect($invitation->expires_at?->toDateString())->toBe(now()->addMonths(27)->toDateString());

    // Le renouvellement suit la même règle : c'est le second endroit où la
    // durée était recopiée.
    $renouvele = app(ReissueFamilyLink::class)->handle($member->refresh());

    expect($renouvele->token->expires_at?->toDateString())
        ->toBe(now()->addMonths(27)->toDateString());
});

/*
|--------------------------------------------------------------------------
| Ce que l'invitation dit, et à qui
|--------------------------------------------------------------------------
|
| Le message tenait en une ligne — « X vous invite à écouter les histoires que
| Y enregistre » — et arrivait chez quelqu'un qui n'a jamais entendu parler de
| nous : un lien sans raison de cliquer. Il explique maintenant d'où ça vient,
| ce que ça coûte, ce qu'on verra et ce qu'on peut rendre.
|
*/

it('explique le cadeau, la gratuité et la souveraineté du narrateur', function (): void {
    $project = Project::factory()->create();

    Narrator::factory()->create([
        'project_id' => $project->id,
        'is_primary' => true,
        'first_name' => 'Odette',
    ]);

    $member = FamilyMember::factory()->make([
        'display_name' => 'Paul',
        'email' => 'paul@example.test',
        'can_ask' => false,
    ]);

    $mail = (new FamilyInvitationNotification($project->refresh(), str_repeat('a', 43), $project->owner))
        ->toMail($member);

    // Les lignes d'avant le bouton **et** celles d'après : la garde
    // anti-hameçonnage vit sous l'action, là où on la lit en dernier.
    $rendu = implode(' ', array_map('strval', [...$mail->introLines, ...$mail->outroLines]));

    expect($mail->subject)
        // L'élision, par `Names::of()` : « de Odette » se lit sur chaque
        // invitation, et le jumeau de `ofName()` existait déjà côté serveur.
        ->toContain('d’Odette')
        ->and($rendu)->toContain('a offert à Odette de raconter sa vie')
        ->and($rendu)->toContain('C’est gratuit')
        // Le point du produit : un proche qui l'ignore croit avoir accès à
        // tout ce que la narratrice enregistre.
        ->and($rendu)->toContain('relit chaque histoire avant que quiconque l’entende')
        // Et la garde anti-hameçonnage du doc 04 §9, qui ne bouge pas.
        ->and($rendu)->toContain('Ce lien est personnel');
});

it('ne promet de poser des questions qu’à ceux qui en ont le droit', function (): void {
    $project = Project::factory()->create();

    $lignes = fn (bool $contribue): string => implode(' ', array_map(
        'strval',
        (new FamilyInvitationNotification($project, str_repeat('a', 43), $project->owner))
            ->toMail(FamilyMember::factory()->make([
                'display_name' => 'Marie',
                'email' => 'marie@example.test',
                'can_ask' => $contribue,
            ]))
            ->introLines,
    ));

    /*
     * Le droit de poser une question s'accorde personne par personne (R-1).
     * L'annoncer à tout le monde ferait une promesse que la page dément trois
     * secondes plus tard — et c'est précisément le genre de promesse qu'on ne
     * tient pas qui décide si une famille nous croit.
     */
    expect($lignes(true))->toContain('lui poser vos propres questions')
        ->and($lignes(false))->not->toContain('lui poser vos propres questions');
});
