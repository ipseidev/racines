<?php

declare(strict_types=1);

namespace App\Actions;

use App\Audit\AuditLog;
use App\Enums\ConsentChannel;
use App\Enums\ConsentKind;
use App\Exceptions\Domain\InfectedUpload;
use App\Models\Consent;
use App\Models\Story;
use App\Services\Antivirus\Scanner;
use App\Services\Images\Sanitizer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Joindre une photo à une histoire.
 *
 * L'ordre des quatre gestes n'est pas négociable, et chacun protège de
 * quelque chose de différent :
 *
 *  1. **Scanner d'abord.** Le fichier arrive du téléphone d'un cousin dont
 *     l'appareil est peut-être infecté, et ce qu'on stocke sera servi aux
 *     autres. Scanner après avoir écrit reviendrait à avoir stocké.
 *  2. **Assainir ensuite.** Les coordonnées GPS partent, l'orientation est
 *     redressée, le format devient du JPEG. Une photo prise à l'instant porte
 *     l'adresse du salon de la personne, et personne n'y pense au moment
 *     d'envoyer.
 *  3. **Le consentement, une fois par déposant et par projet.** Le déposant
 *     garantit ses droits (doc 04 §3). Le redemander à chaque photo
 *     transformerait un engagement en formalité qu'on clique sans lire.
 *  4. **Stocker enfin**, avec de quoi répondre plus tard : qui a déposé, la
 *     légende, et si la photo tiendra à l'impression.
 *
 * `print_ready` est une **information**, pas un refus : une photo de photo est
 * peut-être la seule qui existe de quelqu'un, et la refuser pour six cents
 * pixels manquants serait perdre un souvenir pour un défaut d'impression.
 */
final readonly class AttachPhoto
{
    /**
     * Vingt mégaoctets. Au-delà, c'est un fichier brut d'appareil photo et
     * non une photo de téléphone : la conversion coûterait plus que le
     * service n'y gagne, et l'envoi échouerait en 4G de toute façon.
     */
    public const MAX_KILOBYTES = 20_480;

    /** Deux cents caractères : au-delà, la légende ne tient pas sous l'image. */
    public const MAX_CAPTION = 200;

    public function __construct(
        private Scanner $scanner,
        private Sanitizer $sanitizer,
        private RecordConsent $consents,
    ) {}

    public function handle(
        Story $story,
        UploadedFile $file,
        Model $depositor,
        ?string $caption,
    ): Media {
        if (! $this->scanner->isClean($file)) {
            throw InfectedUpload::make();
        }

        // Peut lever `UnsupportedImage` : on laisse remonter, le message est
        // fait pour être montré.
        $sanitized = $this->sanitizer->process($file);

        $this->ensureConsent($story, $depositor);

        $media = $story
            ->addMedia($sanitized->getRealPath())
            ->usingFileName($sanitized->getClientOriginalName())
            ->withCustomProperties([
                'caption' => self::trimCaption($caption),
                'print_ready' => Sanitizer::isPrintReady($sanitized),
                // L'alias de la carte polymorphe, jamais le nom de classe :
                // renommer une classe ne doit pas casser l'attribution d'une
                // photo déposée il y a deux ans (même règle que la base).
                'depositor_type' => $depositor->getMorphClass(),
                'depositor_id' => (string) $depositor->getKey(),
            ])
            ->toMediaCollection(Story::PHOTOS);

        AuditLog::record('attached Photo', $story, [
            'media_id' => $media->id,
            'print_ready' => $media->getCustomProperty('print_ready'),
            'depositor_type' => class_basename($depositor),
        ], $story->project);

        return $media;
    }

    /**
     * Le consentement aux droits sur la photo, une fois par déposant.
     */
    private function ensureConsent(Story $story, Model $depositor): void
    {
        // `getMorphClass()` et non `::class` : la base stocke l'alias court
        // de la carte polymorphe, et comparer au nom de classe ne trouve
        // jamais rien — donc redemanderait le consentement à chaque photo.
        $already = Consent::query()
            ->where('project_id', $story->project_id)
            ->where('subject_type', $depositor->getMorphClass())
            ->where('subject_id', (string) $depositor->getKey())
            ->where('kind', ConsentKind::PhotoRights->value)
            ->exists();

        if ($already) {
            return;
        }

        $this->consents->handle(
            $depositor,
            $story->project,
            ConsentKind::PhotoRights,
            ConsentChannel::Web,
        );
    }

    public static function trimCaption(?string $caption): ?string
    {
        if ($caption === null || trim($caption) === '') {
            return null;
        }

        // Coupée plutôt que refusée : une légende trop longue n'est pas une
        // erreur de la personne, c'est une contrainte de mise en page.
        return mb_substr(trim($caption), 0, self::MAX_CAPTION);
    }
}
