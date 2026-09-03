<?php

declare(strict_types=1);

use App\Audit\AuditLog;
use App\Enums\ActorContext;
use App\Filament\Resources\Stories\Pages\ViewStory;
use App\Models\Recording;
use App\Models\Story;
use App\Models\Transcript;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Finder\Finder;

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

it('inscrit l’ouverture réelle d’une fiche dans le panneau', function (): void {
    $support = User::factory()->support()->withAppAuthentication()->create();
    $story = Story::factory()->create();

    // Le vrai test de l'exigence : on ouvre la page, on ne rappelle pas la
    // fonction. C'est la page qui doit journaliser, sans qu'on y pense.
    $this->actingAs($support)
        ->get(ViewStory::getUrl(['record' => $story->id]))
        ->assertOk();

    $row = DB::table('audit_logs')->first();

    expect($row)->not->toBeNull()
        ->and($row->action)->toBe('viewed Story')
        ->and($row->subject_id)->toBe($story->id)
        ->and($row->actor_user_id)->toBe($support->id)
        ->and($row->actor_context)->toBe(ActorContext::Filament->value)
        ->and($row->project_id)->toBe($story->project_id);
});

it('n’écrit qu’une ligne par visite', function (): void {
    $support = User::factory()->support()->withAppAuthentication()->create();
    $story = Story::factory()->create();

    $this->actingAs($support)->get(ViewStory::getUrl(['record' => $story->id]));

    // On journalise l'accès à la donnée, pas chaque battement de l'interface.
    // Un journal qui grossit à chaque tri de colonne devient illisible, et un
    // journal illisible ne sert à personne.
    expect(DB::table('audit_logs')->count())->toBe(1);
});

it('impose le trait de journalisation à toute page de consultation', function (): void {
    // Critère de sortie du bloc : une page ajoutée plus tard sans ce trait
    // serait un trou dans la seule chose qui rend ce back-office acceptable.
    $offenders = [];

    $pages = Finder::create()
        ->files()
        ->in(base_path('app/Filament'))
        ->name(['View*.php', 'Edit*.php']);

    foreach ($pages as $file) {
        if (! str_contains($file->getContents(), 'use LogsViews;')) {
            $offenders[] = $file->getRelativePathname();
        }
    }

    expect($offenders)->toBe([], 'Pages sans journalisation des lectures : '.implode(', ', $offenders));
});

it('n’offre au personnel aucune validation ni aucun partage', function (): void {
    /*
     * Critère de sortie du bloc, et invariant du produit : la validation
     * appartient au narrateur, et à lui seul. Un bouton « Partager » dans le
     * back-office serait la porte par laquelle un support bien intentionné
     * trahirait la promesse.
     *
     * On cherche les transitions et les actions dans le code du panneau, pas
     * les mots dans les libellés : « valider » apparaît légitimement dans une
     * phrase d'explication.
     */
    $forbidden = ['ValidateStoryAction', 'transitionTo(Validated', 'transitionTo(Shared'];
    $offenders = [];

    foreach (Finder::create()->files()->in(base_path('app/Filament'))->name('*.php') as $file) {
        foreach ($forbidden as $needle) {
            if (str_contains($file->getContents(), $needle)) {
                $offenders[] = $file->getRelativePathname().' : '.$needle;
            }
        }
    }

    expect($offenders)->toBe([], 'Le back-office ne valide ni ne partage : '.implode(', ', $offenders));
});
