<?php

declare(strict_types=1);

namespace App\Services\Images;

use App\Exceptions\Domain\UnsupportedImage;
use Illuminate\Http\UploadedFile;
use Imagick;
use ImagickException;
use Throwable;

/**
 * Ce qu'on fait d'une photo avant de la garder.
 *
 * Trois gestes, et le premier est le plus important : **retirer les
 * coordonnées GPS**. Une photo prise à l'instant depuis un téléphone porte
 * l'adresse du salon de la personne. Cette photo sera vue par des proches,
 * imprimée dans un livre, et conservée des années — personne n'a consenti à
 * publier son adresse, et personne n'y pense au moment d'envoyer.
 *
 * On retire **toutes** les métadonnées et non les seules coordonnées : tenir
 * une liste de ce qui est sensible est une liste qu'on oublie de mettre à
 * jour quand un format ajoute un champ.
 *
 * Ensuite l'orientation, appliquée puis effacée : une photo tournée dans un
 * livre imprimé est un défaut qu'on ne peut plus corriger, et un lecteur qui
 * ignore l'EXIF la montrerait de travers.
 *
 * Enfin le HEIC vers JPEG, parce que c'est ce que les iPhone produisent par
 * défaut et que rien d'autre ne le lit — ni les navigateurs anciens, ni
 * l'imprimeur.
 */
final class Sanitizer
{
    /**
     * Le petit côté minimal pour une impression correcte.
     *
     * En dessous, la photo reste lisible en ligne et pixelise sur le papier.
     * On le **dit** plutôt que de refuser : une photo de photo est peut-être
     * la seule qui existe de quelqu'un.
     */
    public const PRINT_READY_MIN_SIDE = 1200;

    /** Les formats qu'on accepte en entrée. */
    private const ACCEPTED = ['JPEG', 'JPG', 'PNG', 'WEBP', 'HEIC', 'HEIF'];

    public function process(UploadedFile $file): UploadedFile
    {
        $path = $file->getRealPath();

        if ($path === false) {
            throw UnsupportedImage::make();
        }

        try {
            $image = new Imagick($path);
        } catch (ImagickException) {
            throw UnsupportedImage::make();
        }

        try {
            $format = strtoupper($image->getImageFormat());

            if (! in_array($format, self::ACCEPTED, true)) {
                throw UnsupportedImage::make();
            }

            if (in_array($format, ['HEIC', 'HEIF'], true) && ! self::decodesHeic()) {
                throw UnsupportedImage::undecodableHeic();
            }

            // L'ordre compte : redresser **avant** d'effacer, sinon
            // l'information d'orientation est perdue et l'image reste
            // tournée.
            self::autoOrient($image);
            $image->stripImage();

            $image->setImageFormat('jpeg');
            $image->setImageCompressionQuality(88);

            $destination = tempnam(sys_get_temp_dir(), 'sanitized').'.jpg';
            $image->writeImage($destination);
        } catch (UnsupportedImage $exception) {
            throw $exception;
        } catch (Throwable) {
            throw UnsupportedImage::make();
        } finally {
            $image->clear();
        }

        return new UploadedFile(
            $destination,
            self::jpegName($file->getClientOriginalName()),
            'image/jpeg',
            null,
            true,
        );
    }

    /**
     * La photo tient-elle pour l'impression ?
     */
    public static function isPrintReady(UploadedFile $file): bool
    {
        $path = $file->getRealPath();

        if ($path === false) {
            return false;
        }

        try {
            $image = new Imagick($path);
            $shortest = min($image->getImageWidth(), $image->getImageHeight());
            $image->clear();
        } catch (Throwable) {
            return false;
        }

        return $shortest >= self::PRINT_READY_MIN_SIDE;
    }

    public static function decodesHeic(): bool
    {
        $formats = Imagick::queryFormats('HEI*');

        return $formats !== [];
    }

    /**
     * Applique l'orientation EXIF, puis la neutralise.
     *
     * `Imagick::autoOrient()` existe mais n'est pas disponible partout selon
     * la version d'ImageMagick liée ; les huit cas tiennent en un `match` et
     * ne dépendent de rien.
     *
     * Publique pour être éprouvée seule : la version d'ImageMagick de l'image
     * Sail ne **réécrit pas** l'orientation dans un JPEG, donc une fixture
     * fabriquée puis relue perd l'information et le test ne prouverait rien.
     * Les huit cas sont là où un bug vivrait — c'est eux qu'il faut tester,
     * et une image en mémoire suffit.
     */
    public static function autoOrient(Imagick $image): void
    {
        $orientation = $image->getImageOrientation();
        $white = new \ImagickPixel('white');

        match ($orientation) {
            Imagick::ORIENTATION_TOPRIGHT => $image->flopImage(),
            Imagick::ORIENTATION_BOTTOMRIGHT => $image->rotateImage($white, 180),
            Imagick::ORIENTATION_BOTTOMLEFT => (function () use ($image, $white): void {
                $image->rotateImage($white, 180);
                $image->flopImage();
            })(),
            Imagick::ORIENTATION_LEFTTOP => (function () use ($image, $white): void {
                $image->rotateImage($white, 90);
                $image->flopImage();
            })(),
            Imagick::ORIENTATION_RIGHTTOP => $image->rotateImage($white, 90),
            Imagick::ORIENTATION_RIGHTBOTTOM => (function () use ($image, $white): void {
                $image->rotateImage($white, -90);
                $image->flopImage();
            })(),
            Imagick::ORIENTATION_LEFTBOTTOM => $image->rotateImage($white, -90),
            default => null,
        };

        $image->setImageOrientation(Imagick::ORIENTATION_TOPLEFT);
    }

    private static function jpegName(string $original): string
    {
        $base = pathinfo($original, PATHINFO_FILENAME);

        return ($base === '' ? 'photo' : $base).'.jpg';
    }
}
