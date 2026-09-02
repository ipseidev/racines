<?php

declare(strict_types=1);

use App\Actions\OpenSupportTicket;
use App\Enums\SupportTicketKind;
use App\Enums\SupportTicketStatus;
use App\Models\Project;
use App\Models\Story;
use App\Models\SupportTicket;

it('ouvre un ticket avec son contexte', function (): void {
    $project = Project::factory()->create();
    $story = Story::factory()->forProject($project)->proposed()->create();

    $ticket = app(OpenSupportTicket::class)->handle(
        $project,
        SupportTicketKind::MicDeniedTwice,
        $story,
        ['denials' => 2],
    );

    expect($ticket->kind)->toBe(SupportTicketKind::MicDeniedTwice)
        ->and($ticket->status)->toBe(SupportTicketStatus::Open)
        ->and($ticket->story_id)->toBe($story->id)
        ->and($ticket->payload['denials'])->toBe(2)
        ->and($ticket->opened_at)->not->toBeNull();
});

it('n’ouvre pas deux fois le même ticket', function (): void {
    $project = Project::factory()->create();
    $story = Story::factory()->forProject($project)->proposed()->create();

    $first = app(OpenSupportTicket::class)->handle($project, SupportTicketKind::MicDeniedTwice, $story);
    $second = app(OpenSupportTicket::class)->handle($project, SupportTicketKind::MicDeniedTwice, $story);

    // Un support noyé sous les doublons ne traite plus rien, et le deuxième
    // ticket n'apporte rien que le premier n'ait déjà.
    expect($second->id)->toBe($first->id)
        ->and(SupportTicket::query()->count())->toBe(1);
});

it('rouvre un ticket après la fermeture du précédent', function (): void {
    $project = Project::factory()->create();
    $story = Story::factory()->forProject($project)->proposed()->create();

    $first = app(OpenSupportTicket::class)->handle($project, SupportTicketKind::MicDeniedTwice, $story);
    $first->forceFill(['status' => SupportTicketStatus::Closed, 'closed_at' => now()])->save();

    $second = app(OpenSupportTicket::class)->handle($project, SupportTicketKind::MicDeniedTwice, $story);

    // Le problème est revenu après qu'on l'a cru réglé : c'est une nouvelle
    // information, et elle mérite un nouveau ticket.
    expect($second->id)->not->toBe($first->id)
        ->and(SupportTicket::query()->count())->toBe(2);
});

it('distingue deux histoires du même projet', function (): void {
    $project = Project::factory()->create();
    $first = Story::factory()->forProject($project)->proposed()->create();
    $second = Story::factory()->forProject($project)->proposed()->create();

    app(OpenSupportTicket::class)->handle($project, SupportTicketKind::MicDeniedTwice, $first);
    app(OpenSupportTicket::class)->handle($project, SupportTicketKind::MicDeniedTwice, $second);

    expect(SupportTicket::query()->count())->toBe(2);
});

it('ne met aucune donnée personnelle dans le contexte', function (): void {
    $project = Project::factory()->create();

    $ticket = app(OpenSupportTicket::class)->handle(
        $project,
        SupportTicketKind::PhoneOptionRequested,
        null,
        ['entry' => 'rescue'],
    );

    // Des identifiants et des compteurs : le support lit ces tickets, et ils
    // ne doivent pas devenir une fiche de renseignement.
    $encoded = json_encode($ticket->payload);

    expect($encoded)->not->toContain('@')
        ->and($encoded)->not->toContain('+33');
});
