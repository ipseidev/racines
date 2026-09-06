<?php

declare(strict_types=1);

use App\Actions\RequestExport;
use App\Enums\ExportScope;
use App\Jobs\BuildExport;
use App\Models\Export;
use App\Models\Narrator;
use App\Models\Project;
use App\Models\Story;
use App\Models\Transcript;
use App\Notifications\ExportReadyNotification;
use App\Services\Storage\MediaStorage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

/**
 * L'export : ce que la famille emporte.
 *
 * Deux exigences dominent, et la première est un critère de sortie du bloc :
 * **un export demandé par l'Initiateur·rice ne contient jamais une histoire
 * non validée.** Livrer les brouillons par un bouton « télécharger mes
 * données » contournerait toute la souveraineté du bloc 07 — par la porte de
 * derrière, et sans que personne s'en aperçoive.
 *
 * La seconde : **le ZIP se lit sans le service.** Des noms en français, un
 * mot d'accueil qui explique, un manifeste avec les empreintes. Dans dix ans,
 * sur une machine qui n'aura jamais entendu parler de nous.
 *
 * @return array{Project, Story, Story}
 */
function projetExportable(): array
{
    $project = Project::factory()->create(['collection_started_at' => '2026-01-15']);
    Narrator::factory()->create(['project_id' => $project->id, 'first_name' => 'Odette', 'is_primary' => true]);

    $validee = Story::factory()->shared()->create([
        'project_id' => $project->id,
        'title' => 'Le fournil',
        'recorded_at' => '2026-02-01',
    ]);
    Transcript::factory()->for($validee)->create(['text' => 'Alors euh, le fournil.']);
    Transcript::factory()->for($validee)->fluide()->create(['text' => 'Le fournil de mon grand-père.']);

    $brouillon = Story::factory()->toReview()->create([
        'project_id' => $project->id,
        'title' => 'Pas encore relue',
        'recorded_at' => '2026-03-01',
    ]);
    Transcript::factory()->for($brouillon)->fluide()->create(['text' => 'Un texte que personne n’a validé.']);

    return [$project, $validee, $brouillon];
}

/** @return array<string, string> */
function contenuDeLArchive(Export $export): array
{
    $chemin = tempnam(sys_get_temp_dir(), 'zip').'.zip';
    file_put_contents($chemin, app(MediaStorage::class)->get((string) $export->object_path));

    $zip = new ZipArchive;
    $zip->open($chemin);

    $entrees = [];

    for ($i = 0; $i < $zip->numFiles; $i++) {
        $nom = (string) $zip->getNameIndex($i);
        $entrees[$nom] = (string) $zip->getFromIndex($i);
    }

    $zip->close();
    @unlink($chemin);

    return $entrees;
}

function construire(Export $export): Export
{
    app()->call([new BuildExport($export), 'handle']);

    return $export->refresh();
}

beforeEach(function (): void {
    Notification::fake();
});

it('n’emporte que les histoires validées quand l’Initiateur·rice demande', function (): void {
    [$project, $validee] = projetExportable();

    $export = construire(app(RequestExport::class)->handle($project, ExportScope::Initiator));
    $noms = implode("\n", array_keys(contenuDeLArchive($export)));

    // Le critère de sortie du bloc, et la porte de derrière du bloc 07.
    expect($noms)->toContain('le-fournil')
        ->and($noms)->not->toContain('pas-encore-relue');

    expect($export->manifest['stories'])->toHaveCount(1)
        ->and($export->manifest['stories'][0]['id'])->toBe($validee->getKey());
});

it('emporte tout sauf la corbeille quand le narrateur demande', function (): void {
    [$project] = projetExportable();

    $export = construire(app(RequestExport::class)->handle($project, ExportScope::Narrator));

    // Son récit non relu reste le sien.
    expect($export->manifest['stories'])->toHaveCount(2);
});

it('range un dossier par histoire, lisible sans nous', function (): void {
    [$project] = projetExportable();

    $entrees = contenuDeLArchive(construire(
        app(RequestExport::class)->handle($project, ExportScope::Initiator),
    ));

    expect($entrees)->toHaveKey('LISEZ-MOI.txt')
        ->toHaveKey('manifest.json')
        ->toHaveKey('histoires/01-le-fournil/mot-a-mot.txt')
        ->toHaveKey('histoires/01-le-fournil/texte.txt')
        ->toHaveKey('histoires/01-le-fournil/histoire.json');

    // Le mot à mot **et** le texte mis au propre : le dossier interdit de
    // supprimer le premier au profit du second.
    expect($entrees['histoires/01-le-fournil/mot-a-mot.txt'])->toContain('Alors euh')
        ->and($entrees['histoires/01-le-fournil/texte.txt'])->toContain('grand-père');
});

it('écrit des empreintes qui correspondent vraiment aux fichiers', function (): void {
    [$project] = projetExportable();

    $export = construire(app(RequestExport::class)->handle($project, ExportScope::Initiator));
    $entrees = contenuDeLArchive($export);

    expect($export->manifest['checksums'])->not->toBeEmpty();

    // C'est ce que la famille vérifiera avec `shasum -a 256`. Une empreinte
    // calculée ailleurs que sur le contenu écrit ne prouverait rien.
    foreach ($export->manifest['checksums'] as $nom => $empreinte) {
        expect(hash('sha256', $entrees[$nom]))->toBe($empreinte, "empreinte fausse pour {$nom}");
    }
});

it('dit dans son mot d’accueil que les fichiers se lisent sans nous', function (): void {
    [$project] = projetExportable();

    $readme = contenuDeLArchive(construire(
        app(RequestExport::class)->handle($project, ExportScope::Initiator),
    ))['LISEZ-MOI.txt'];

    expect($readme)->toContain('besoin d’internet ni de notre site')
        ->toContain('shasum')
        // R-11 : ces formules sont interdites partout, y compris ici.
        ->not->toContain('pour toujours')
        ->not->toContain('appartiennent à la famille');
});

it('émet un lien de sept jours et prévient le demandeur', function (): void {
    [$project] = projetExportable();

    $export = construire(app(RequestExport::class)->handle($project, ExportScope::Initiator));

    expect($export->status)->toBe('ready')
        ->and($export->expires_at)->not->toBeNull()
        ->and($export->bytes)->toBeGreaterThan(0);

    Notification::assertSentTo($project->owner, ExportReadyNotification::class);
});

it('ne refabrique pas un export déjà prêt', function (): void {
    [$project] = projetExportable();

    $premier = construire(app(RequestExport::class)->handle($project, ExportScope::Initiator));
    $second = app(RequestExport::class)->handle($project, ExportScope::Initiator);

    // Reconstruire cinq gigaoctets parce que quelqu'un a cliqué deux fois
    // coûte une demi-heure de file pour un fichier identique.
    expect($second->getKey())->toBe($premier->getKey())
        ->and(Export::query()->count())->toBe(1);
});

it('supprime l’archive quand le lien expire, en gardant la trace', function (): void {
    [$project] = projetExportable();

    $export = construire(app(RequestExport::class)->handle($project, ExportScope::Initiator));
    $export->forceFill(['expires_at' => now()->subDay()])->save();

    $this->artisan('exports:expire')->assertSuccessful();
    $export->refresh();

    expect($export->status)->toBe('expired')
        ->and($export->object_path)->toBeNull()
        // La ligne reste : elle prouve qu'un export a été remis.
        ->and(Export::query()->count())->toBe(1);
});
