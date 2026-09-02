<?php

declare(strict_types=1);

use App\Actions\InviteFamilyMember;
use App\Actions\ReissueFamilyLink;
use App\Enums\OutboundMessageStatus;
use App\Enums\TokenType;
use App\Models\AccessToken;
use App\Models\FamilyMember;
use App\Models\OutboundMessage;
use App\Models\Project;
use App\Notifications\Channels\SmsChannel;
use App\Notifications\FamilyInvitationNotification;
use App\Services\Tokens\TokenService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

it('crée le proche, son lien de douze mois et son invitation', function (): void {
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
        // Douze mois : un lien d'écoute vit le temps du projet, pas le temps
        // d'une session. On le renouvelle, on ne le laisse pas mourir seul.
        ->and(now()->diffInDays($token->expires_at))->toBeGreaterThan(360)
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
