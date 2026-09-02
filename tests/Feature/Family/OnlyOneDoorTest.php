<?php

declare(strict_types=1);

use App\Models\FamilyMember;
use App\Models\Story;
use App\Queries\VisibleStoriesForFamilyMember;
use Illuminate\Support\Facades\File;

/**
 * Le critère de sortie du bloc 08, éprouvé plutôt que relu.
 *
 * « `VisibleStoriesForFamilyMember` est la seule requête `stories` de
 * `app/Http/Controllers/Family`. » Une seconde requête, écrite un jour de
 * fatigue et oubliant la liste `restricted`, exposerait le souvenir de
 * quelqu'un — et le dossier appelle ça un bug bloquant.
 */
it('ne laisse aucun contrôleur famille interroger les histoires directement', function (): void {
    $offenders = [];

    foreach (File::allFiles(app_path('Http/Controllers/Family')) as $file) {
        $contents = (string) File::get($file->getPathname());
        $path = str_replace(base_path().'/', '', $file->getPathname());

        // `Story::query(`, `Story::find`, `Story::where`, `DB::table('stories'`
        // : toutes les portes dérobées possibles.
        if (preg_match('/Story::(query|find|where|first|all)\b/', $contents) === 1
            || preg_match("/table\\(\\s*['\"]stories['\"]/", $contents) === 1) {
            $offenders[] = $path;
        }
    }

    expect($offenders)->toBe([]);
});

it('ne laisse pas le présentateur non plus contourner la porte', function (): void {
    $contents = (string) File::get(app_path('Support/FamilyPresenter.php'));

    expect(preg_match('/Story::(query|find|where|all)\b/', $contents))->toBe(0)
        ->and($contents)->toContain('VisibleStoriesForFamilyMember');
});

it('exclut tout ce qui n’est ni partagé ni au livre, au niveau de la requête', function (): void {
    $member = FamilyMember::factory()->create();

    foreach (['proposed', 'recorded', 'transcribed', 'toReview', 'validated'] as $state) {
        Story::factory()->{$state}()->create(['project_id' => $member->project_id]);
    }

    $shared = Story::factory()->shared()->create(['project_id' => $member->project_id]);
    $inBook = Story::factory()->inBook()->create(['project_id' => $member->project_id]);

    // La requête elle-même filtre : rien n'est chargé puis écarté, parce
    // qu'un objet chargé finit par fuir dans des props.
    $ids = (new VisibleStoriesForFamilyMember($member))->query()->pluck('id')->all();

    expect($ids)->toHaveCount(2)
        ->and($ids)->toContain($shared->id, $inBook->id);
});
