<?php

declare(strict_types=1);

use App\Books\SelectBookChapters;
use App\Exceptions\Domain\StoryNotPrintable;
use App\Models\Book;
use App\Models\Project;
use App\Models\Story;
use App\Models\Transcript;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Imagick;
use ImagickPixel;

uses(RefreshDatabase::class);

/**
 * Les chapitres d'un livre, et la seule règle qui ne se négocie pas.
 *
 * **Aucune histoire non validée n'entre dans un livre.** C'est un critère de
 * sortie du bloc, et ce n'est pas une précaution technique : l'imprimé est
 * définitif, et une histoire que le narrateur n'a pas validée imprimée à
 * trente exemplaires ne se retire plus. La liste par défaut est donc bâtie
 * depuis les états imprimables, et l'inclusion explicite les revérifie.
 *
 * L'ordre par défaut est chronologique — celui du souvenir, pas celui de la
 * base — et se réarrange ensuite à la main.
 */
function projetAvecHistoires(): Project
{
    return Project::factory()->create();
}

function histoireValidee(Project $project, string $quand, string $texte = 'Le texte.'): Story
{
    $story = Story::factory()->validated()->create([
        'project_id' => $project->id,
        'validated_at' => $quand,
        'recorded_at' => $quand,
    ]);

    Transcript::factory()->for($story)->fluide()->create(['text' => $texte]);

    return $story;
}

it('retient par défaut toutes les histoires imprimables, dans l’ordre du souvenir', function (): void {
    $project = projetAvecHistoires();
    $recente = histoireValidee($project, '2026-03-01');
    $ancienne = histoireValidee($project, '2026-01-01');
    $milieu = histoireValidee($project, '2026-02-01');

    // Une histoire masquée n'est pas imprimable, et ne doit pas apparaître.
    Story::factory()->hidden()->create(['project_id' => $project->id]);

    $book = Book::factory()->for($project)->create();
    app(SelectBookChapters::class)->handle($book);

    expect($book->chapters()->pluck('story_id')->all())
        ->toBe([$ancienne->id, $milieu->id, $recente->id])
        ->and($book->chapters()->where('included', true)->count())->toBe(3);
});

it('se rejoue sans perdre les exclusions ni l’ordre choisi', function (): void {
    $project = projetAvecHistoires();
    $une = histoireValidee($project, '2026-01-01');
    histoireValidee($project, '2026-02-01');

    $book = Book::factory()->for($project)->create();
    $selection = app(SelectBookChapters::class);
    $selection->handle($book);

    $book->chapters()->where('story_id', $une->id)->update(['included' => false, 'position' => 99]);

    // Une histoire validée depuis : elle s'ajoute, le reste ne bouge pas.
    $nouvelle = histoireValidee($project, '2026-03-01');
    $selection->handle($book->refresh());

    $chapitre = $book->chapters()->where('story_id', $une->id)->firstOrFail();

    expect($chapitre->included)->toBeFalse()
        ->and($chapitre->position)->toBe(99)
        ->and($book->chapters()->where('story_id', $nouvelle->id)->exists())->toBeTrue();
});

it('refuse d’inclure une histoire que le narrateur n’a pas validée', function (): void {
    $project = projetAvecHistoires();
    $brouillon = Story::factory()->toReview()->create(['project_id' => $project->id]);
    $book = Book::factory()->for($project)->create();

    expect(fn () => app(SelectBookChapters::class)->include($book, $brouillon))
        ->toThrow(StoryNotPrintable::class);

    expect($book->chapters()->count())->toBe(0);
});

it('refuse une histoire d’une autre famille', function (): void {
    $book = Book::factory()->for(projetAvecHistoires())->create();
    $ailleurs = histoireValidee(projetAvecHistoires(), '2026-01-01');

    expect(fn () => app(SelectBookChapters::class)->include($book, $ailleurs))
        ->toThrow(StoryNotPrintable::class);
});

it('prend la correction, sinon la mise au propre, sinon le mot à mot', function (): void {
    $project = projetAvecHistoires();
    $story = Story::factory()->validated()->create(['project_id' => $project->id]);

    Transcript::factory()->for($story)->create(['text' => 'Alors euh le mot à mot.']);
    expect(SelectBookChapters::textOf($story))->toBe('Alors euh le mot à mot.');

    Transcript::factory()->for($story)->fluide()->create(['text' => 'La mise au propre.']);
    expect(SelectBookChapters::textOf($story->refresh()))->toBe('La mise au propre.');

    Transcript::factory()->for($story)->edited()->create(['text' => 'La correction.', 'version' => 2]);
    expect(SelectBookChapters::textOf($story->refresh()))->toBe('La correction.');
});

/*
 * Une histoire sans texte ne s'imprime pas d'elle-même.
 *
 * Trouvé sur un **rendu réel** au checkpoint du bloc 13 : le bon à tirer
 * portait un chapitre « Sans titre » avec sa question, sa date, son QR, et
 * pas une ligne de récit — une page blanche dans un livre imprimé (T-197).
 * Le cas existe : une histoire validée dont la transcription a échoué, ou
 * une réponse écrite restée vide.
 *
 * Elle reste **dans la liste**, décochée : la masquer laisserait la famille
 * chercher pourquoi son histoire n'est pas là. Ce qui change, c'est qu'il
 * faut un geste pour l'imprimer.
 */
it('ne coche pas d’office une histoire sans texte ni photo', function (): void {
    $project = projetAvecHistoires();
    $muette = Story::factory()->validated()->create([
        'project_id' => $project->id,
        'recorded_at' => '2026-01-01',
    ]);
    $bavarde = histoireValidee($project, '2026-02-01', 'Le texte du fournil.');

    $book = Book::factory()->for($project)->create();
    app(SelectBookChapters::class)->handle($book);

    expect($book->chapters()->where('story_id', $muette->id)->value('included'))->toBeFalse()
        ->and($book->chapters()->where('story_id', $bavarde->id)->value('included'))->toBeTrue()
        // Elle est là, visible, décochée : la masquer ferait chercher.
        ->and($book->chapters()->count())->toBe(2);
});

it('coche une histoire sans texte mais avec une photo', function (): void {
    Storage::fake('r2');

    $project = projetAvecHistoires();
    $story = Story::factory()->validated()->create([
        'project_id' => $project->id,
        'recorded_at' => '2026-01-01',
    ]);

    $image = new Imagick;
    $image->newImage(1600, 1200, new ImagickPixel('#8B7355'));
    $image->setImageFormat('jpeg');
    $chemin = tempnam(sys_get_temp_dir(), 'ch').'.jpg';
    $image->writeImage($chemin);
    $image->clear();

    $story->addMedia($chemin)->toMediaCollection(Story::PHOTOS);

    $book = Book::factory()->for($project)->create();
    app(SelectBookChapters::class)->handle($book);

    // Une page de photo est une page : le récit n'est pas toujours du texte.
    expect($book->chapters()->where('story_id', $story->id)->value('included'))->toBeTrue();
});
