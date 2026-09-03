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
 * Retirer une photo d'une histoire.
 *
 * Vraiment supprimée, contrairement à une histoire — qui passe par la
 * corbeille et reste récupérable trente jours. La différence a une raison :
 * une histoire est un récit qu'on peut regretter d'avoir retiré, une photo
 * qu'on retire est presque toujours une photo qu'on n'aurait pas dû déposer.
 * Garder trente jours la photo qu'un proche vient de retirer serait garder
 * exactement ce qu'il a voulu faire disparaître.
 *
 * Le droit passe par `PhotoAccess`, une seule porte : un contrôle recopié
 * ici oublierait le cas du proche qui retire la photo d'un autre.
 */
final class RemovePhoto
{
    public function handle(Story $story, Media $photo, Model $actor): void
    {
        if (! PhotoAccess::canRemove($story, $photo, $actor)) {
            throw new AccessDeniedHttpException;
        }

        // Le journal **avant** la suppression : après, le média n'a plus
        // d'identifiant à inscrire.
        AuditLog::record('removed Photo', $story, [
            'media_id' => $photo->id,
            'removed_by' => class_basename($actor),
        ], $story->project);

        $photo->delete();
    }
}
