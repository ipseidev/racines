<?php

declare(strict_types=1);

use App\Actions\AttachPhoto;
use App\Models\Narrator;
use App\Models\Story;
use App\Support\PhotoPresenter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Imagick;
use ImagickPixel;

uses(RefreshDatabase::class);

/**
 * L'URL d'une photo est signée sur l'adresse que verra le **navigateur**.
 *
 * C'est la leçon T-56, apprise pour l'audio de la page famille : en local, le
 * stockage répond sur un hôte depuis le conteneur et sur un autre depuis la
 * machine — et une URL signée sur le premier ne s'ouvre nulle part ailleurs.
 * `MediaStorage::temporaryUrl()` existe précisément pour ça ; le présentateur
 * de photos appelait `getTemporaryUrl()` de la médiathèque et la contournait.
 *
 * Conséquence : **aucune photo ne s'est jamais affichée hors production**, où
 * les deux adresses coïncident. Trouvé au point 1 du checkpoint du bloc 12 —
 * la photo arrivait, le carré restait vide (T-192).
 */
beforeEach(function (): void {
    Storage::fake('r2');
});

function photoPourUrl(): UploadedFile
{
    $image = new Imagick;
    $image->newImage(1600, 1200, new ImagickPixel('#8B7355'));
    $image->setImageFormat('jpeg');

    $path = tempnam(sys_get_temp_dir(), 'url').'.jpg';
    $image->writeImage($path);
    $image->clear();

    return new UploadedFile($path, 'souvenir.jpg', 'image/jpeg', null, true);
}

it('passe par le port de stockage, pas par la médiathèque', function (): void {
    $story = Story::factory()->create();
    $narrator = $story->project->primaryNarrator ?? Narrator::factory()->create([
        'project_id' => $story->project_id,
        'is_primary' => true,
    ]);

    app(AttachPhoto::class)->handle($story, photoPourUrl(), $narrator, null);

    $photos = PhotoPresenter::forStory($story->refresh());

    // Le double de stockage rend une URL reconnaissable : ce que le test
    // vérifie, c'est **qui** l'a fabriquée, pas sa forme.
    expect($photos)->toHaveCount(1)
        ->and($photos[0]['url'])->toStartWith('https://fake-storage.test/')
        ->and($photos[0]['thumbUrl'])->toStartWith('https://fake-storage.test/');
});
