<?php

declare(strict_types=1);

use App\Actions\AttachPhoto;
use App\Enums\ConsentKind;
use App\Exceptions\Domain\InfectedUpload;
use App\Exceptions\Domain\UnsupportedImage;
use App\Models\Consent;
use App\Models\FamilyMember;
use App\Models\Narrator;
use App\Models\Story;
use App\Services\Images\Sanitizer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Joindre une photo à une histoire.
 *
 * « La photo, l'histoire et la voix sur une même page » est le cœur du produit
 * imprimé. Ce qui arrive du téléphone d'une personne de quatre-vingts ans est
 * souvent une photo **de** photo : mal cadrée, un peu petite, tournée. On
 * accepte, on assainit, et on **prévient** quand la qualité ne suffira pas
 * pour le papier — refuser serait perdre la seule image qui existe de
 * quelqu'un.
 */
function photoFile(int $width = 1600, int $height = 1200): UploadedFile
{
    $image = new Imagick;
    $image->newImage($width, $height, new ImagickPixel('#8B7355'));
    $image->setImageFormat('jpeg');
    $image->setImageProperty('exif:GPSLatitude', '48/1, 51/1, 24/1');

    $path = tempnam(sys_get_temp_dir(), 'depot').'.jpg';
    $image->writeImage($path);
    $image->clear();

    return new UploadedFile($path, 'souvenir.jpg', 'image/jpeg', null, true);
}

/**
 * Le narrateur principal du projet de l'histoire.
 *
 * `Story::factory()` en crée déjà un, et un index unique partiel n'en permet
 * qu'un par projet : en fabriquer un second échoue. Le décor doit ressembler
 * au produit, et le produit n'a qu'un narrateur principal.
 */
function narratorOf(Story $story): Narrator
{
    return $story->project->primaryNarrator
        ?? Narrator::factory()->create([
            'project_id' => $story->project_id,
            'is_primary' => true,
        ]);
}

beforeEach(function (): void {
    Storage::fake('r2');
});

it('attache une photo assainie à l’histoire', function (): void {
    $story = Story::factory()->create();
    $narrator = narratorOf($story);

    $media = app(AttachPhoto::class)->handle($story, photoFile(), $narrator, 'Le mariage de ma sœur');

    expect($story->refresh()->getMedia(Story::PHOTOS))->toHaveCount(1)
        ->and($media->getCustomProperty('caption'))->toBe('Le mariage de ma sœur')
        // Le fichier stocké est le JPEG assaini, pas ce qui est arrivé.
        ->and($media->mime_type)->toBe('image/jpeg');
});

it('retient qui a déposé la photo', function (): void {
    $story = Story::factory()->create();
    $member = FamilyMember::factory()->create([
        'project_id' => $story->project_id,
        'display_name' => 'Claire',
        'can_contribute' => true,
    ]);

    $media = app(AttachPhoto::class)->handle($story, photoFile(), $member, null);

    /*
     * Le déposant sert à deux choses : le texte alternatif de la galerie
     * (« Photo jointe par Claire ») et le droit de retirer sa propre photo.
     *
     * Stocké sous l'alias de la carte polymorphe et non sous le nom de
     * classe : renommer une classe ne doit pas casser l'attribution d'une
     * photo déposée il y a deux ans.
     */
    expect($media->getCustomProperty('depositor_type'))->toBe('family_member')
        ->and($media->getCustomProperty('depositor_id'))->toBe($member->id);
});

it('dit si la photo tient pour l’impression', function (): void {
    $story = Story::factory()->create();
    $narrator = narratorOf($story);

    $grande = app(AttachPhoto::class)->handle($story, photoFile(2000, 1400), $narrator, null);
    $petite = app(AttachPhoto::class)->handle($story, photoFile(1000, 700), $narrator, null);

    // On le dit, on ne refuse pas : la photo de photo est peut-être la seule
    // qui existe.
    expect($grande->getCustomProperty('print_ready'))->toBeTrue()
        ->and($petite->getCustomProperty('print_ready'))->toBeFalse();
});

it('retire les coordonnées GPS avant de stocker', function (): void {
    $story = Story::factory()->create();
    $narrator = narratorOf($story);

    $media = app(AttachPhoto::class)->handle($story, photoFile(), $narrator, null);

    $stored = Storage::disk('r2')->path($media->id.'/'.$media->file_name);
    $image = new Imagick($stored);
    $properties = $image->getImageProperties('exif:*');
    $image->clear();

    // L'invariant du bloc : personne n'a consenti à publier l'adresse de son
    // salon.
    expect($properties)->toBe([]);
});

it('recueille le consentement aux droits sur la photo, une fois par déposant', function (): void {
    $story = Story::factory()->create();
    $narrator = narratorOf($story);

    app(AttachPhoto::class)->handle($story, photoFile(), $narrator, null);
    app(AttachPhoto::class)->handle($story, photoFile(), $narrator, null);

    // Le déposant garantit ses droits (doc 04 §3). Une fois par personne et
    // par projet : le redemander à chaque photo transformerait un engagement
    // en formalité qu'on clique sans lire.
    expect(Consent::query()
        ->where('project_id', $story->project_id)
        ->where('subject_id', $narrator->id)
        ->where('kind', ConsentKind::PhotoRights)
        ->count())->toBe(1);
});

it('refuse un fichier qui n’est pas une image', function (): void {
    $story = Story::factory()->create();
    $narrator = narratorOf($story);
    $file = UploadedFile::fake()->createWithContent('faux.jpg', 'pas une image');

    expect(fn () => app(AttachPhoto::class)->handle($story, $file, $narrator, null))
        ->toThrow(UnsupportedImage::class);

    expect($story->refresh()->getMedia(Story::PHOTOS))->toHaveCount(0);
});

it('refuse un fichier vérolé, et ne stocke rien', function (): void {
    $story = Story::factory()->create();
    $narrator = narratorOf($story);

    $file = UploadedFile::fake()->createWithContent(
        'piege.jpg',
        'X5O!P%@AP[4\PZX54(P^)7CC)7}$EICAR-STANDARD-ANTIVIRUS-TEST-FILE!$H+H*',
    );

    expect(fn () => app(AttachPhoto::class)->handle($story, $file, $narrator, null))
        ->toThrow(InfectedUpload::class);

    // Rien dans le stockage : le scan précède l'écriture, jamais l'inverse.
    expect($story->refresh()->getMedia(Story::PHOTOS))->toHaveCount(0);
});

it('borne la légende à deux cents caractères', function (): void {
    $story = Story::factory()->create();
    $narrator = narratorOf($story);

    $media = app(AttachPhoto::class)->handle(
        $story,
        photoFile(),
        $narrator,
        str_repeat('a', 400),
    );

    // Coupée plutôt que refusée : une légende trop longue n'est pas une
    // erreur de la personne, c'est une contrainte de mise en page.
    expect(mb_strlen((string) $media->getCustomProperty('caption')))->toBe(200);
});

it('inscrit le dépôt au journal d’audit', function (): void {
    $story = Story::factory()->create();
    $narrator = narratorOf($story);

    app(AttachPhoto::class)->handle($story, photoFile(), $narrator, null);

    $row = DB::table('audit_logs')
        ->where('action', 'attached Photo')
        ->first();

    expect($row)->not->toBeNull()
        ->and($row->project_id)->toBe($story->project_id);
});

it('garde l’original et dérive les conversions', function (): void {
    $story = Story::factory()->create();
    $narrator = narratorOf($story);

    $media = app(AttachPhoto::class)->handle($story, photoFile(), $narrator, null);

    // Critère de sortie du bloc : l'original part à l'imprimeur, et une
    // conversion ne remonte jamais en qualité.
    expect(Storage::disk('r2')->exists($media->id.'/'.$media->file_name))->toBeTrue()
        ->and($media->hasGeneratedConversion('thumb'))->toBeTrue();
});

it('borne la taille acceptée', function (): void {
    // Vingt mégaoctets : au-delà, c'est un fichier brut d'appareil photo, pas
    // une photo de téléphone, et la conversion coûterait plus que le service
    // n'y gagne.
    expect(AttachPhoto::MAX_KILOBYTES)->toBe(20_480)
        ->and(Sanitizer::PRINT_READY_MIN_SIDE)->toBe(1_200);
});
