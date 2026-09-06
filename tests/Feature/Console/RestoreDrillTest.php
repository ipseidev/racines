<?php

declare(strict_types=1);

use App\Models\Project;
use App\Models\Story;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

uses(RefreshDatabase::class);

/**
 * L'exercice de restauration.
 *
 * Le dossier promet un RTO de 72 heures et un drill trimestriel (doc 04 §11).
 * Une promesse de restauration qu'on n'a jamais exécutée n'est pas une
 * promesse : c'est un espoir. Et la manière dont une restauration échoue est
 * toujours la même — l'archive existe, elle se télécharge, et il manque une
 * extension, un rôle, ou la moitié des tables.
 *
 * **Ce test restaure un vrai dump dans une vraie base**, avec `pg_dump` et
 * `psql`. Le doubler n'aurait aucun sens : ce qu'on veut savoir, c'est
 * précisément si les outils font ce qu'on croit.
 */
beforeEach(function (): void {
    File::deleteDirectory(storage_path('app/drills'));

    // Les rapports d'exercice sont versionnés dans `docs/runbooks/drills/` :
    // la suite n'a rien à y déposer.
    $this->rapports = storage_path('app/drills/rapports');
});

// Et on nettoie **après** aussi : un `beforeEach` seul laisse le dernier
// rapport derrière lui, où le formateur du front le ramasse et fait échouer
// la porte de qualité sur un fichier que personne n'a écrit à la main.
afterEach(function (): void {
    File::deleteDirectory(storage_path('app/drills'));
});

it('mène l’exercice de bout en bout et rend un rapport', function (): void {
    Project::factory()->count(2)->create();
    Story::factory()->count(3)->create();

    $this->artisan('restore:drill', ['--rapports' => $this->rapports])->assertSuccessful();

    $rapports = File::files($this->rapports);

    expect($rapports)->not->toBeEmpty();

    // Le dernier écrit, et non le dernier de la liste : `File::files` ne
    // garantit pas l'ordre, et un rapport d'un autre test serait relu.
    usort($rapports, fn ($a, $b): int => $a->getMTime() <=> $b->getMTime());
    $rapport = File::get(end($rapports)->getPathname());

    expect($rapport)->toContain('projects')
        ->toContain('stories')
        // La durée mesurée, pas une cible recopiée : c'est elle qu'on
        // comparera au RTO de 72 heures.
        ->toContain('Durée mesurée')
        ->toContain('Chaîne d’audit');
});

it('ne laisse pas la base d’exercice derrière lui', function (): void {
    Project::factory()->create();

    $this->artisan('restore:drill', ['--rapports' => $this->rapports])->assertSuccessful();

    $restantes = DB::select("select datname from pg_database where datname like 'drill\\_%'");

    // Une base d'exercice oubliée grossit à chaque trimestre et finit par
    // remplir le disque du serveur qu'elle devait protéger.
    expect($restantes)->toBeEmpty();
});

it('ne touche jamais la base de l’application', function (): void {
    Project::factory()->count(2)->create();

    $this->artisan('restore:drill', ['--rapports' => $this->rapports])->assertSuccessful();

    // L'exercice lit, restaure ailleurs, compare. S'il pouvait écrire dans
    // la base courante, il serait lui-même l'incident.
    expect(Project::query()->count())->toBe(2);
});

it('échoue quand la restauration rend moins que la sauvegarde', function (): void {
    // Le cas qui compte : l'archive existe, elle se restaure sans erreur, et
    // il manque des lignes. C'est l'échec silencieux qu'un exercice doit
    // attraper — un dump amputé le reproduit fidèlement.
    $dump = storage_path('app/drills/ampute.sql');
    File::ensureDirectoryExists(dirname($dump));
    File::put($dump, 'CREATE TABLE projects (id text); CREATE TABLE stories (id text);');

    $this->artisan('restore:drill', ['--dump' => $dump, '--rapports' => $this->rapports])->assertFailed();
});
