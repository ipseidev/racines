<?php

declare(strict_types=1);

use App\Enums\TokenType;
use App\Models\AccessToken;
use App\Models\Narrator;
use App\Models\Project;
use App\Services\Tokens\TokenService;
use Illuminate\Console\Scheduling\Schedule;

/**
 * Les coordonnées de quelqu'un qui n'a jamais dit oui.
 *
 * Trente jours, puis elles partent. C'est le garde-fou du doc 04 §2, et il est
 * automatique **par nécessité** : quelqu'un a confié à un tiers le numéro de
 * son parent, ce parent n'a rien demandé, et personne au support ne pensera à
 * faire le ménage un mardi de mars.
 *
 * La ligne `narrators` reste, avec son prénom : elle porte l'histoire du
 * projet, et l'Initiateur·rice doit pouvoir comprendre ce qui s'est passé. Ce
 * qui part, c'est ce qui permettrait de recontacter la personne.
 */
it('efface les coordonnées échues et révoque les liens', function (): void {
    $project = Project::factory()->create();

    $narrator = Narrator::factory()->create([
        'project_id' => $project->id,
        'is_primary' => true,
        'first_name' => 'Jeanne',
        'email' => 'jeanne@exemple.test',
        'phone_e164' => '+33612345678',
        'opted_in_at' => null,
        'contact_deletion_due_at' => now()->subDay(),
    ]);

    $space = app(TokenService::class)->issue(TokenType::NarratorSpace, $narrator, ['space']);
    $invitation = app(TokenService::class)->issue(TokenType::Invitation, $project, ['opt_in']);

    $this->artisan('narrators:delete-unaccepted-contacts')->assertSuccessful();

    $narrator->refresh();

    expect($narrator->email)->toBeNull()
        ->and($narrator->phone_e164)->toBeNull()
        ->and($narrator->contact_deletion_due_at)->toBeNull()
        // L'effacement est daté : c'est ce qu'une demande RGPD demande, et un
        // enregistrement de journal ne survit pas à une rotation.
        ->and($narrator->contact_deleted_at)->not->toBeNull()
        // Le prénom reste : il porte l'histoire du projet.
        ->and($narrator->first_name)->toBe('Jeanne');

    // Un jeton qui survit à la suppression du contact serait une porte
    // laissée ouverte.
    expect(AccessToken::query()->whereKey($space->token->id)->firstOrFail()->revoked_at)
        ->not->toBeNull()
        ->and(AccessToken::query()->whereKey($invitation->token->id)->firstOrFail()->revoked_at)
        ->not->toBeNull();
});

it('attend l’échéance', function (): void {
    $narrator = Narrator::factory()->create([
        'email' => 'jeanne@exemple.test',
        'opted_in_at' => null,
        'contact_deletion_due_at' => now()->addDays(3),
    ]);

    $this->artisan('narrators:delete-unaccepted-contacts')->assertSuccessful();

    expect($narrator->refresh()->email)->toBe('jeanne@exemple.test');
});

it('ne touche jamais aux coordonnées de quelqu’un qui a accepté', function (): void {
    // Le cas dangereux : une échéance posée par un refus, puis une
    // acceptation. `AcceptInvitation` remet l'échéance à nul, mais la commande
    // se garde elle-même — un effacement de trop n'est pas rattrapable.
    $narrator = Narrator::factory()->create([
        'email' => 'jeanne@exemple.test',
        'phone_e164' => '+33612345678',
        'opted_in_at' => now(),
        'contact_deletion_due_at' => now()->subDay(),
    ]);

    $this->artisan('narrators:delete-unaccepted-contacts')->assertSuccessful();

    expect($narrator->refresh()->email)->toBe('jeanne@exemple.test')
        ->and($narrator->phone_e164)->toBe('+33612345678');
});

it('ne fait rien quand personne n’est échu', function (): void {
    Narrator::factory()->count(3)->create(['contact_deletion_due_at' => null]);

    $this->artisan('narrators:delete-unaccepted-contacts')
        ->expectsOutputToContain('0 coordonnée(s) effacée(s).')
        ->assertSuccessful();
});

it('est planifiée quotidiennement', function (): void {
    // Une purge qui dépend d'un humain n'est pas une purge.
    $events = collect(app(Schedule::class)->events())
        ->map(fn (object $event): string => (string) $event->command);

    expect($events->contains(fn (string $command): bool => str_contains(
        $command,
        'narrators:delete-unaccepted-contacts',
    )))->toBeTrue();
});
