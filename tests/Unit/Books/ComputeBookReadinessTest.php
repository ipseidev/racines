<?php

declare(strict_types=1);

use App\Books\ComputeBookReadiness;
use App\Enums\ShareDecision;
use App\Enums\TranscriptKind;
use App\Models\Project;
use App\States\Story\Archived;
use App\States\Story\Hidden;
use App\States\Story\Shared;
use App\States\Story\Trashed;

/**
 * « Book-ready » : des critères de production, **jamais un compte
 * d'histoires** (R-6).
 *
 * C'est l'interdit central de ce bloc, et il a une raison produit forte : une
 * famille qui a raconté huit longues histoires a plus de matière qu'une
 * famille qui en a raconté vingt-cinq de deux minutes. Un seuil au nombre
 * d'histoires ferait attendre la première et déclencherait trop tôt pour la
 * seconde. « ~25 histoires » reste un repère marketing, jamais un critère.
 *
 * Deuxième principe : seules les histoires **validées** comptent. Une
 * histoire masquée, archivée ou à la corbeille ne peut pas entrer dans un
 * livre, donc elle ne peut pas non plus servir à décider qu'un livre est
 * possible.
 */
it('ne compte jamais les histoires, mais la matière', function (): void {
    $project = Project::factory()->create();

    // Huit histoires longues : la matière est là.
    foreach (range(1, 8) as $n) {
        storyWithWords($project, 1_600, 'childhood', 12);
    }

    $readiness = app(ComputeBookReadiness::class)->handle($project->refresh());

    expect($readiness->words)->toBe(12_800)
        // Le nombre d'histoires est une information, pas un critère.
        ->and($readiness->chapters)->toBe(8);
});

it('atteint le seuil par les mots **ou** par la durée', function (): void {
    $parMots = Project::factory()->create();
    storyWithWords($parMots, 12_000, 'childhood');

    $parDuree = Project::factory()->create();
    storyWithWords($parDuree, 500, 'childhood', 95);

    // Un « ou » et non un « et » : quelqu'un qui parle beaucoup et dont la
    // transcription est courte a autant de matière que l'inverse.
    expect(app(ComputeBookReadiness::class)->handle($parMots->refresh())->meetsVolume())->toBeTrue()
        ->and(app(ComputeBookReadiness::class)->handle($parDuree->refresh())->meetsVolume())->toBeTrue();
});

it('estime les pages à partir des mots, des photos et des chapitres', function (): void {
    $project = Project::factory()->create();

    foreach (range(1, 4) as $n) {
        storyWithWords($project, 1_400, 'childhood');
    }

    $readiness = app(ComputeBookReadiness::class)->handle($project->refresh());

    // 5 600 mots / 280 = 20 pages, plus 0,5 page par chapitre = 22.
    expect($readiness->words)->toBe(5_600)
        ->and($readiness->estimatedPages)->toBe(22);
});

it('compte les thèmes distincts, et non les questions', function (): void {
    $project = Project::factory()->create();

    storyWithWords($project, 300, 'childhood');
    storyWithWords($project, 300, 'childhood');
    storyWithWords($project, 300, 'work');
    storyWithWords($project, 300, 'love');

    // Quatre histoires, trois thèmes : un livre qui ne parle que d'enfance
    // n'est pas un livre de vie.
    expect(app(ComputeBookReadiness::class)->handle($project->refresh())->themes)->toBe(3);
});

it('ignore les histoires masquées, archivées ou à la corbeille', function (string $state): void {
    $project = Project::factory()->create();

    storyWithWords($project, 2_000, 'childhood');
    $retiree = storyWithWords($project, 10_000, 'work');
    $retiree->forceFill(['state' => $state])->save();

    // Une histoire qui ne peut pas entrer dans un livre ne peut pas servir à
    // décider qu'un livre est possible.
    expect(app(ComputeBookReadiness::class)->handle($project->refresh())->words)->toBe(2_000);
})->with([
    'masquée' => Hidden::class,
    'archivée' => Archived::class,
    'corbeille' => Trashed::class,
]);

it('compte les histoires partagées comme les validées', function (): void {
    $project = Project::factory()->create();

    $partagee = storyWithWords($project, 4_000, 'childhood');
    $partagee->forceFill(['state' => Shared::class, 'shared_at' => now()])->save();

    // Partagée est un état **après** validée : la matière est acquise.
    expect(app(ComputeBookReadiness::class)->handle($project->refresh())->words)->toBe(4_000);
});

it('n’est prêt que si les critères sont tous réunis', function (): void {
    $project = Project::factory()->create();

    // De la matière en quantité, mais un seul thème.
    foreach (range(1, 10) as $n) {
        storyWithWords($project, 1_800, 'childhood', 10);
    }

    $readiness = app(ComputeBookReadiness::class)->handle($project->refresh());

    expect($readiness->meetsVolume())->toBeTrue()
        ->and($readiness->meetsPages())->toBeTrue()
        ->and($readiness->meetsThemes())->toBeFalse()
        // Un « et » sur les critères, contrairement au volume : un livre d'un
        // seul thème n'est pas le livre qu'on a promis.
        ->and($readiness->isReady())->toBeFalse();
});

it('accepte un sujet sensible dont la visibilité est tranchée', function (): void {
    $project = Project::factory()->create();

    foreach (['childhood', 'work', 'love', 'places', 'beliefs_values'] as $theme) {
        foreach (range(1, 2) as $n) {
            storyWithWords($project, 1_800, $theme, 10);
        }
    }

    $sensible = storyWithWords($project, 1_000, 'hardships');
    $sensible->transcripts()->where('kind', TranscriptKind::Fluide)->first()
        ?->forceFill(['metadata' => ['sensitive_flags' => ['health']]])->save();

    // « Garder pour moi » est une décision aussi explicite que « partager ».
    // Ce qui bloque, c'est l'absence de décision, pas le sujet.
    $sensible->forceFill(['share_decision' => ShareDecision::KeepPrivate])->save();

    expect(app(ComputeBookReadiness::class)->handle($project->refresh())->meetsSensitiveReviewed())
        ->toBeTrue();
});

it('est prêt quand tout est réuni', function (): void {
    $project = Project::factory()->create();

    foreach (['childhood', 'work', 'love', 'places', 'beliefs_values'] as $theme) {
        foreach (range(1, 2) as $n) {
            storyWithWords($project, 1_800, $theme, 10);
        }
    }

    expect(app(ComputeBookReadiness::class)->handle($project->refresh())->isReady())->toBeTrue();
});

it('dit ce qui manque, en langage utilisable', function (): void {
    $project = Project::factory()->create();
    storyWithWords($project, 500, 'childhood');

    $readiness = app(ComputeBookReadiness::class)->handle($project->refresh());

    // La jauge de l'espace affiche ces manques : « il manque des thèmes » se
    // comprend, « critère 3 non satisfait » non.
    expect($readiness->missing())->toContain('volume')
        ->and($readiness->missing())->toContain('pages')
        ->and($readiness->missing())->toContain('themes');
});

it('n’est pas prêt tant qu’un sujet sensible n’a pas été tranché', function (): void {
    $project = Project::factory()->create();

    foreach (['childhood', 'work', 'love', 'places', 'beliefs_values'] as $theme) {
        foreach (range(1, 2) as $n) {
            storyWithWords($project, 1_800, $theme, 10);
        }
    }

    /*
     * Une histoire portant un sujet sensible dont le narrateur n'a pas
     * tranché la visibilité bloque le livre. C'est le critère R-6 qu'on
     * oublie : imprimer un récit de santé ou de conviction sans que la
     * personne ait dit qui peut le lire serait exactement la faute que tout
     * ce produit cherche à éviter.
     *
     * Les marqueurs vivent dans les **métadonnées du rendu Fluide**, où le
     * bloc 06 les écrit — pas sur l'histoire. Et « tranché » veut dire une
     * décision de partage explicite : `decide_later` n'en est pas une, c'est
     * justement le contraire.
     */
    $sensible = storyWithWords($project, 1_000, 'hardships');
    $sensible->transcripts()->where('kind', TranscriptKind::Fluide)->first()
        ?->forceFill(['metadata' => ['sensitive_flags' => ['health']]])->save();
    $sensible->forceFill(['share_decision' => ShareDecision::DecideLater])->save();

    $readiness = app(ComputeBookReadiness::class)->handle($project->refresh());

    expect($readiness->meetsSensitiveReviewed())->toBeFalse()
        ->and($readiness->isReady())->toBeFalse()
        ->and($readiness->missing())->toContain('sensitive');
});

it('a les pages pour vrai verrou, et non les mots', function (): void {
    $project = Project::factory()->create();

    // Douze mille mots pile, le seuil de volume, répartis en dix chapitres.
    foreach (range(1, 10) as $n) {
        storyWithWords($project, 1_200, 'childhood');
    }

    $readiness = app(ComputeBookReadiness::class)->handle($project->refresh());

    /*
     * Un fait qui vaut d'être écrit : à 280 mots par page, douze mille mots
     * font quarante-huit pages — bien en dessous des soixante exigées. Le
     * critère de **pages** est donc le verrou réel, pas celui des mots. Il
     * faut environ quinze mille quatre cents mots pour un livre de dix
     * chapitres, ou des photos, qui pèsent une demi-page chacune.
     *
     * Conséquence pour la jauge : afficher un pourcentage de mots ferait
     * croire à une famille qu'elle y est presque alors qu'il lui manque un
     * quart de la matière.
     */
    expect($readiness->meetsVolume())->toBeTrue()
        ->and($readiness->estimatedPages)->toBe(47)
        ->and($readiness->meetsPages())->toBeFalse();
});
