<?php

declare(strict_types=1);

use App\Exceptions\Domain\UnsupportedImage;
use App\Services\Images\Sanitizer;
use Illuminate\Http\UploadedFile;

/**
 * L'assainissement d'une photo déposée.
 *
 * Trois choses à faire, et la première est la plus importante : **retirer les
 * coordonnées GPS**. Une photo prise à l'instant depuis un iPhone porte
 * l'adresse du salon de la personne, et cette photo sera vue par des proches,
 * imprimée dans un livre, et conservée des années. Personne n'a consenti à
 * publier son adresse.
 *
 * Ensuite : redresser l'orientation, parce qu'une photo tournée dans un livre
 * imprimé est un défaut qu'on ne peut plus corriger. Et convertir le HEIC en
 * JPEG, parce que c'est ce que les iPhone produisent par défaut et que rien
 * d'autre ne le lit.
 */
function imageWithGps(int $width = 1600, int $height = 1200): UploadedFile
{
    $image = new Imagick;
    $image->newImage($width, $height, new ImagickPixel('#8B7355'));
    $image->setImageFormat('jpeg');

    // Des métadonnées comme en produirait un téléphone : la position, et le
    // modèle de l'appareil.
    $image->setImageProperty('exif:GPSLatitude', '48/1, 51/1, 24/1');
    $image->setImageProperty('exif:GPSLongitude', '2/1, 21/1, 3/1');
    $image->setImageProperty('exif:Model', 'iPhone 15');
    // Comme le fait un appareil : l'orientation est un **attribut**, et
    // `getImageOrientation()` ne lit pas la propriete `exif:Orientation`.
    $image->setImageOrientation(Imagick::ORIENTATION_RIGHTTOP);

    $path = tempnam(sys_get_temp_dir(), 'photo').'.jpg';
    $image->writeImage($path);
    $image->clear();

    return new UploadedFile($path, 'photo.jpg', 'image/jpeg', null, true);
}

it('retire les coordonnées GPS', function (): void {
    $sanitized = app(Sanitizer::class)->process(imageWithGps());

    $image = new Imagick($sanitized->getRealPath());
    $properties = $image->getImageProperties('exif:*');
    $image->clear();

    // La ligne la plus importante de ce fichier.
    expect(array_keys($properties))->not->toContain('exif:GPSLatitude')
        ->and(array_keys($properties))->not->toContain('exif:GPSLongitude');
});

it('retire aussi le modèle de l’appareil', function (): void {
    $sanitized = app(Sanitizer::class)->process(imageWithGps());

    $image = new Imagick($sanitized->getRealPath());
    $properties = $image->getImageProperties('exif:*');
    $image->clear();

    // Pas parce que c'est sensible en soi, mais parce qu'on retire **tout**
    // plutôt que de tenir une liste de ce qui est dangereux — une liste qu'on
    // oublie de mettre à jour quand un format ajoute un champ.
    expect($properties)->toBe([]);
});

it('garde l’image lisible, et en JPEG', function (): void {
    $sanitized = app(Sanitizer::class)->process(imageWithGps(1600, 1200));

    $image = new Imagick($sanitized->getRealPath());

    expect($image->getImageFormat())->toBe('JPEG')
        ->and($image->getImageWidth())->toBeGreaterThan(0);

    $image->clear();
});

it('redresse les huit orientations, puis les neutralise', function (int $orientation, bool $swaps): void {
    /*
     * Testé sur une image **en mémoire**, et pas sur un fichier relu :
     * l'ImageMagick de cette image ne réécrit pas l'orientation dans un
     * JPEG, donc une fixture fabriquée puis relue perdrait l'information et
     * le test ne prouverait rien. Les huit cas sont là où un bug vivrait.
     */
    $image = new Imagick;
    $image->newImage(1600, 1200, new ImagickPixel('#8B7355'));
    $image->setImageOrientation($orientation);

    Sanitizer::autoOrient($image);

    // Les quatre orientations « de côté » échangent les dimensions.
    expect($image->getImageWidth())->toBe($swaps ? 1200 : 1600)
        ->and($image->getImageHeight())->toBe($swaps ? 1600 : 1200)
        // Neutralisée : un lecteur qui ignore l'EXIF voit la photo droite.
        ->and($image->getImageOrientation())->toBe(Imagick::ORIENTATION_TOPLEFT);

    $image->clear();
})->with([
    'droite' => [Imagick::ORIENTATION_TOPLEFT, false],
    'miroir' => [Imagick::ORIENTATION_TOPRIGHT, false],
    'demi-tour' => [Imagick::ORIENTATION_BOTTOMRIGHT, false],
    'miroir vertical' => [Imagick::ORIENTATION_BOTTOMLEFT, false],
    'quart de tour, miroir' => [Imagick::ORIENTATION_LEFTTOP, true],
    'quart de tour' => [Imagick::ORIENTATION_RIGHTTOP, true],
    'trois quarts, miroir' => [Imagick::ORIENTATION_RIGHTBOTTOM, true],
    'trois quarts' => [Imagick::ORIENTATION_LEFTBOTTOM, true],
]);

it('sait décoder le HEIC dans cet environnement', function (): void {
    /*
     * Une vérification de **capacité**, et elle a sa raison d'être : la règle
     * §9 du bloc prévoit le cas où le décodeur manque, et ce test le dira le
     * jour où une image système le perd — silencieusement, sinon.
     *
     * La conversion HEIC vers JPEG de bout en bout n'est pas éprouvée ici :
     * ImageMagick de cette image sait **lire** le HEIC mais pas l'écrire, et
     * ni ffmpeg ni libheif-examples n'y sont. Fabriquer la fixture
     * demanderait de committer un binaire, et l'éprouver pour de vrai demande
     * une photo d'iPhone — ce qu'est exactement le point 1 du checkpoint
     * (écart T-119).
     */
    expect(Sanitizer::decodesHeic())->toBeTrue();
});

it('rend toujours du JPEG, quel que soit le format d’entrée', function (string $format): void {
    $image = new Imagick;
    $image->newImage(1400, 1400, new ImagickPixel('#4a5d3a'));
    $image->setImageFormat($format);
    $path = tempnam(sys_get_temp_dir(), 'photo').'.'.$format;
    $image->writeImage($path);
    $image->clear();

    $sanitized = app(Sanitizer::class)->process(
        new UploadedFile($path, 'photo.'.$format, 'image/'.$format, null, true),
    );

    $written = new Imagick($sanitized->getRealPath());

    // Le JPEG est le seul format que lisent à la fois les vieux navigateurs
    // et l'imprimeur. C'est le chemin que suivra aussi un HEIC d'iPhone.
    expect($written->getImageFormat())->toBe('JPEG')
        ->and($sanitized->getClientOriginalName())->toBe('photo.jpg');

    $written->clear();
})->with(['png', 'webp']);

it('explique quoi faire quand le HEIC est illisible', function (): void {
    // Le message est la décision produit : on refuse avec une consigne
    // suivable plutôt que d'ajouter un service de conversion (règle §9).
    $message = UnsupportedImage::undecodableHeic()->getMessage();

    expect($message)->toContain('Le plus compatible')
        ->and($message)->toContain('galerie');
});

it('refuse ce qui n’est pas une image, sans deviner', function (): void {
    $file = UploadedFile::fake()->createWithContent('faux.jpg', 'ceci n’est pas une image');

    expect(fn () => app(Sanitizer::class)->process($file))
        ->toThrow(UnsupportedImage::class);
});

it('dit si la photo tient pour l’impression', function (): void {
    // Mille deux cents pixels sur le petit côté : en dessous, ça reste lisible
    // en ligne et ça pixelise sur le papier. On le dit à la personne plutôt
    // que de refuser — sa photo de photo est peut-être la seule qui existe.
    expect(Sanitizer::isPrintReady(imageWithGps(2000, 1300)))->toBeTrue()
        ->and(Sanitizer::isPrintReady(imageWithGps(1600, 800)))->toBeFalse();
});
