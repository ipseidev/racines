<?php

declare(strict_types=1);

use App\Actions\AttachPhoto;
use App\Models\FamilyMember;
use App\Models\Story;
use App\Models\User;
use App\Support\PhotoAccess;
use App\Support\PhotoPresenter;
use Illuminate\Support\Facades\Storage;

/**
 * Qui peut déposer, qui peut retirer.
 *
 * Le cas qui compte le plus est le troisième : **un proche ne retire que ses
 * propres photos**. Autoriser le retrait des photos d'autrui ferait du cercle
 * d'écoute un lieu de conflit, et une famille en désaccord n'a pas besoin
 * d'un outil de plus pour se disputer.
 *
 * Le narrateur, lui, retire tout — y compris ce qu'un proche a déposé sur son
 * histoire. C'est son récit ; sa souveraineté ne s'arrête pas au texte.
 */
beforeEach(function (): void {
    Storage::fake('r2');
});

it('laisse le narrateur retirer n’importe quelle photo de son histoire', function (): void {
    $story = Story::factory()->create();
    $narrator = narratorOf($story);

    $contributeur = FamilyMember::factory()->create([
        'project_id' => $story->project_id,
        'can_ask' => true,
    ]);

    $photo = app(AttachPhoto::class)->handle($story, photoFile(), $contributeur, null);

    // Son récit, sa décision — même sur ce qu'un autre a déposé.
    expect(PhotoAccess::canRemove($story, $photo, $narrator))->toBeTrue();
});

it('laisse un contributeur retirer les siennes, et seulement les siennes', function (): void {
    $story = Story::factory()->create();

    $claire = FamilyMember::factory()->create([
        'project_id' => $story->project_id,
        'display_name' => 'Claire',
        'can_ask' => true,
    ]);
    $paul = FamilyMember::factory()->create([
        'project_id' => $story->project_id,
        'display_name' => 'Paul',
        'can_ask' => true,
    ]);

    $deClaire = app(AttachPhoto::class)->handle($story, photoFile(), $claire, null);

    expect(PhotoAccess::canRemove($story, $deClaire, $claire))->toBeTrue()
        // Le cas qui compte : Paul ne touche pas à la photo de Claire.
        ->and(PhotoAccess::canRemove($story, $deClaire, $paul))->toBeFalse();
});

it('refuse le dépôt à un proche sans droit de contribuer', function (): void {
    $story = Story::factory()->create();

    $spectateur = FamilyMember::factory()->create([
        'project_id' => $story->project_id,
        'can_ask' => false,
    ]);

    // Le droit de contribuer est explicite et accordé personne par personne.
    expect(PhotoAccess::canAttach($story, $spectateur))->toBeFalse();
});

it('refuse le dépôt à un proche retiré du cercle', function (): void {
    $story = Story::factory()->create();

    $ancien = FamilyMember::factory()->create([
        'project_id' => $story->project_id,
        'can_ask' => true,
        'removed_at' => now(),
    ]);

    // Un accès retiré est un accès retiré, même si la colonne du droit de
    // contribuer est restée à vrai.
    expect(PhotoAccess::canAttach($story, $ancien))->toBeFalse();
});

it('refuse le dépôt à un proche d’une autre famille', function (): void {
    $story = Story::factory()->create();
    $autre = Story::factory()->create();

    $intrus = FamilyMember::factory()->create([
        'project_id' => $autre->project_id,
        'can_ask' => true,
    ]);

    expect(PhotoAccess::canAttach($story, $intrus))->toBeFalse();
});

it('laisse l’Initiateur·rice déposer et retirer', function (): void {
    $story = Story::factory()->create();
    $owner = $story->project->owner;
    $narrator = narratorOf($story);

    $photo = app(AttachPhoto::class)->handle($story, photoFile(), $narrator, null);

    expect(PhotoAccess::canAttach($story, $owner))->toBeTrue()
        ->and(PhotoAccess::canRemove($story, $photo, $owner))->toBeTrue();
});

it('refuse tout à un autre compte', function (): void {
    $story = Story::factory()->create();
    $narrator = narratorOf($story);
    $etranger = User::factory()->create();

    $photo = app(AttachPhoto::class)->handle($story, photoFile(), $narrator, null);

    expect(PhotoAccess::canAttach($story, $etranger))->toBeFalse()
        ->and(PhotoAccess::canRemove($story, $photo, $etranger))->toBeFalse();
});

/*
 * Un proche ne touche qu'à **ses** photos (dossier v3.1).
 *
 * La légende d'un cousin s'ouvrait autrefois à tout contributeur : corriger
 * l'orthographe d'un nom de village était un service, pas une intrusion. La
 * règle tombe avec le dépôt lui-même — un proche ne dépose plus sur une
 * histoire, il joint ses photos à la question qu'il pose, et celles-là sont
 * les siennes. Rien n'autorise plus à écrire sous l'image d'un autre.
 */
it('ne laisse un proche toucher qu’à ses propres photos', function (): void {
    $story = Story::factory()->create();

    $claire = FamilyMember::factory()->create([
        'project_id' => $story->project_id,
        'can_ask' => true,
    ]);
    $paul = FamilyMember::factory()->create([
        'project_id' => $story->project_id,
        'can_ask' => true,
    ]);

    $deClaire = app(AttachPhoto::class)->handle($story, photoFile(), $claire, null);

    expect(PhotoAccess::canEditCaption($story, $deClaire, $claire))->toBeTrue()
        ->and(PhotoAccess::canRemove($story, $deClaire, $claire))->toBeTrue()
        ->and(PhotoAccess::canEditCaption($story, $deClaire, $paul))->toBeFalse()
        ->and(PhotoAccess::canRemove($story, $deClaire, $paul))->toBeFalse();
});

it('ne montre à l’Initiateur·rice que ses propres photos sur une histoire non partagée', function (): void {
    $story = Story::factory()->create();
    $owner = $story->project->owner;
    $narrator = narratorOf($story);

    app(AttachPhoto::class)->handle($story, photoFile(), $narrator, 'La photo du narrateur');
    app(AttachPhoto::class)->handle($story, photoFile(), $owner, 'La mienne');

    /*
     * L'invariant du bloc 08, appliqué aux photos : une photo est du
     * **contenu**, comme le texte et la voix. Il aurait été facile de le
     * perdre ici — le tableau de bord est « son » espace, et rien n'y
     * rappelle qu'une photo jointe par le narrateur à un récit non partagé ne
     * lui appartient pas encore.
     */
    $visible = PhotoPresenter::forInitiator($story->refresh(), $owner);

    expect($visible)->toHaveCount(1)
        ->and($visible[0]['caption'])->toBe('La mienne');
});

it('lui montre tout dès que l’histoire est partagée', function (): void {
    $story = Story::factory()->shared()->create();
    $owner = $story->project->owner;
    $narrator = narratorOf($story);

    app(AttachPhoto::class)->handle($story, photoFile(), $narrator, 'La photo du narrateur');
    app(AttachPhoto::class)->handle($story, photoFile(), $owner, 'La mienne');

    // Partagée veut dire partagée : le narrateur a décidé.
    expect(PhotoPresenter::forInitiator($story->refresh(), $owner))
        ->toHaveCount(2);
});
