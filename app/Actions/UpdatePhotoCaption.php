<?php

declare(strict_types=1);

namespace App\Actions;

use App\Audit\AuditLog;
use App\Models\Story;
use App\Support\PhotoAccess;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Corriger la légende d'une photo.
 *
 * Le droit est plus large que celui de retirer, et volontairement : corriger
 * l'orthographe d'un nom de village sur la photo d'un cousin est un service,
 * pas une intrusion. Retirer sa photo en serait une.
 *
 * La légende entre dans le livre imprimé, sous l'image. C'est pourquoi elle
 * est bornée, et pourquoi on la coupe plutôt que de la refuser : une légende
 * trop longue n'est pas une erreur de la personne, c'est une contrainte de
 * mise en page.
 */
final class UpdatePhotoCaption
{
    public function handle(Story $story, Media $photo, Model $actor, ?string $caption): Media
    {
        if (! PhotoAccess::canEditCaption($story, $photo, $actor)) {
            throw new AccessDeniedHttpException;
        }

        $before = $photo->getCustomProperty('caption');
        $after = AttachPhoto::trimCaption($caption);

        $photo->setCustomProperty('caption', $after);
        $photo->save();

        AuditLog::record('edited PhotoCaption', $story, [
            'media_id' => $photo->id,
            // La taille et non les textes : une légende peut nommer des
            // personnes, et une ligne d'audit ne se modifie plus après coup.
            'characters_before' => $before === null ? 0 : mb_strlen((string) $before),
            'characters_after' => $after === null ? 0 : mb_strlen($after),
        ], $story->project);

        return $photo;
    }
}
