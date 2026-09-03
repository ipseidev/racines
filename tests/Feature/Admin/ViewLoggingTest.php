<?php

declare(strict_types=1);

use App\Audit\AuditLog;
use App\Enums\ActorContext;
use App\Models\Recording;
use App\Models\Story;
use App\Models\Transcript;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Les lectures laissent une trace.
 *
 * C'est l'exigence du dossier 04 §12 qu'on oublie toujours : « journalisation
 * inviolable de toutes les actions support, **lecture comprise** ». Un support
 * qui écoute l'histoire intime d'une grand-mère n'a rien modifié — et doit
 * quand même laisser une trace.
 *
 * Cette exigence est ce qui rend le back-office acceptable. Sans elle,
 * « seuls les proches autorisés peuvent écouter » serait faux : notre équipe
 * peut écouter, et la seule chose qui la retient est de savoir que ça se voit.
 */
it('inscrit la consultation d’une histoire', function (): void {
    $support = User::factory()->support()->create();
    $story = Story::factory()->create();

    $this->actingAs($support);

    AuditLog::record('viewed Story', $story);

    $row = DB::table('audit_logs')->first();

    expect($row->action)->toBe('viewed Story')
        ->and($row->subject_type)->toBe(Story::class)
        ->and($row->subject_id)->toBe($story->id)
        ->and($row->actor_user_id)->toBe($support->id);
});

it('distingue l’écoute d’un enregistrement de la lecture de sa fiche', function (): void {
    $support = User::factory()->support()->create();
    $recording = Recording::factory()->create();

    $this->actingAs($support);

    AuditLog::record('viewed Recording', $recording);
    AuditLog::record('played Recording', $recording);

    // Deux actions distinctes, et la seconde est celle qui compte : lire une
    // fiche technique n'est pas écouter la voix de quelqu'un.
    expect(DB::table('audit_logs')->pluck('action')->all())
        ->toBe(['viewed Recording', 'played Recording']);
});

it('inscrit la lecture d’une transcription', function (): void {
    $support = User::factory()->support()->create();
    $transcript = Transcript::factory()->create();

    $this->actingAs($support);

    AuditLog::record('viewed Transcript', $transcript);

    expect(DB::table('audit_logs')->value('action'))->toBe('viewed Transcript');
});

it('rattache la lecture au projet de la famille concernée', function (): void {
    $support = User::factory()->support()->create();
    $story = Story::factory()->create();

    $this->actingAs($support);

    AuditLog::record('viewed Story', $story);

    // « Qui a touché à cette famille ? » est la question qu'on nous posera.
    expect(DB::table('audit_logs')->value('project_id'))->toBe($story->project_id);
});

it('nomme le contexte du panneau quand la lecture vient de l’administration', function (): void {
    $support = User::factory()->support()->withAppAuthentication()->create();

    // Le contexte se déduit du chemin : une lecture depuis `/admin` n'est pas
    // la même chose qu'une lecture depuis un lien à jeton.
    $this->actingAs($support)->get('/admin');

    expect(AuditLog::context())->toBeInstanceOf(ActorContext::class);
});
