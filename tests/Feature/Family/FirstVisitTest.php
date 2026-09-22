<?php

declare(strict_types=1);

use App\Actions\InviteFamilyMember;
use App\Enums\TokenType;
use App\Models\Project;
use App\Models\Story;
use App\Services\Tokens\TokenService;
use App\States\Story\Shared;
use Illuminate\Support\Facades\Notification;

/**
 * La première ouverture d'un lien d'écoute.
 *
 * `family_members.first_seen_at` existait depuis le bloc 02, l'espace
 * l'affichait en pastille et l'administration en faisait une colonne — et
 * **rien ne l'écrivait**. La pastille disait « n'a jamais ouvert son lien »
 * pour l'éternité, y compris à quelqu'un qui écoutait chaque semaine.
 *
 * C'est le premier maillon de H2 : invitation délivrée → **ouverte** →
 * écoutée 30 s → réaction. Un maillon qu'on ne mesure pas est un maillon
 * qu'on ne saura pas réparer.
 */
function invitedMember(Project $project): array
{
    Notification::fake();

    $member = app(InviteFamilyMember::class)->handle($project, $project->owner, [
        'display_name' => 'Marie',
        'email' => 'marie@example.test',
    ]);

    $issued = app(TokenService::class)->issue(
        TokenType::ListenProject,
        $member,
        ['listen', 'react'],
    );

    return [$member, $issued->plain];
}

it('note la première ouverture du lien d’un proche', function (): void {
    $project = Project::factory()->create();
    [$member, $plain] = invitedMember($project);

    expect($member->first_seen_at)->toBeNull();

    $this->get("/l/{$plain}")->assertOk();

    expect($member->refresh()->first_seen_at)->not->toBeNull();
});

it('garde la date de la première ouverture, jamais celle de la dernière', function (): void {
    $project = Project::factory()->create();
    [$member, $plain] = invitedMember($project);

    $this->get("/l/{$plain}")->assertOk();
    $premiere = $member->refresh()->first_seen_at;

    $this->travel(3)->days();
    $this->get("/l/{$plain}")->assertOk();

    /*
     * Savoir qu'une personne est venue une fois est ce que
     * l'Initiateur·rice cherche. La suivre semaine après semaine serait autre
     * chose, et personne ne l'a demandé.
     */
    expect($member->refresh()->first_seen_at?->toIso8601String())
        ->toBe($premiere?->toIso8601String());
});

it('note aussi l’ouverture quand le proche arrive par une histoire', function (): void {
    $project = Project::factory()->create();
    [$member] = invitedMember($project);

    $story = Story::factory()->create([
        'project_id' => $project->id,
        'state' => Shared::class,
        'shared_at' => now(),
    ]);

    // Un lien d'histoire porte l'histoire en sujet et le proche dans
    // `issued_to` : deux portes, une seule personne à marquer.
    $issued = app(TokenService::class)->issue(
        TokenType::ListenStory,
        $story,
        ['listen'],
        issuedTo: $member,
    );

    $this->get("/l/{$issued->plain}/stories/{$story->id}")->assertOk();

    expect($member->refresh()->first_seen_at)->not->toBeNull();
});

it('ne compte pas une ouverture quand le lien est refusé', function (): void {
    $project = Project::factory()->create();
    [$member, $plain] = invitedMember($project);

    $token = app(TokenService::class)->resolve($plain, TokenType::ListenProject);
    app(TokenService::class)->revoke($token, 'test');

    // 410 : le lien a existé, il ne vaut plus. C'est ce que dit un jeton
    // révoqué, et ce n'est ni un succès ni une redirection.
    $this->get("/l/{$plain}")->assertGone();

    // Compter le jeton d'un lien révoqué ferait dire à la pastille le
    // contraire de ce qui s'est passé.
    expect($member->refresh()->first_seen_at)->toBeNull();
});
