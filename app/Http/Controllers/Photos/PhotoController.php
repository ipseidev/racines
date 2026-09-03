<?php

declare(strict_types=1);

namespace App\Http\Controllers\Photos;

use App\Actions\AttachPhoto;
use App\Actions\RemovePhoto;
use App\Actions\UpdatePhotoCaption;
use App\Exceptions\Domain\InfectedUpload;
use App\Exceptions\Domain\UnsupportedImage;
use App\Models\FamilyMember;
use App\Models\Narrator;
use App\Models\Story;
use App\Support\PhotoAccess;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Déposer, légender et retirer une photo.
 *
 * **Un seul contrôleur pour les quatre espaces** — narrateur par jeton
 * d'enregistrement, narrateur par jeton d'espace, proche contributeur,
 * Initiateur·rice connectée. Ce qui change d'un espace à l'autre est
 * l'identité de l'acteur, et elle se lit du jeton résolu ou de la session.
 * Quatre contrôleurs auraient recopié quatre fois le même contrôle de droit,
 * et le quatrième aurait oublié un cas.
 *
 * Les erreurs remontent en messages **affichables** : un fichier refusé par
 * l'antivirus ou un HEIC illisible sont des situations ordinaires pour une
 * personne de quatre-vingts ans, pas des exceptions techniques.
 */
final readonly class PhotoController
{
    public function __construct(
        private AttachPhoto $attach,
        private RemovePhoto $remove,
        private UpdatePhotoCaption $captions,
    ) {}

    public function store(Request $request, ?string $story = null): RedirectResponse
    {
        $target = self::storyFor($request, $story);
        $actor = self::actorFor($request);

        if (! PhotoAccess::canAttach($target, $actor)) {
            throw new AccessDeniedHttpException;
        }

        $validated = $request->validate([
            'photo' => [
                'required',
                'file',
                'max:'.AttachPhoto::MAX_KILOBYTES,
                // Les types du téléphone. `Sanitizer` convertit tout en JPEG
                // ensuite ; cette liste est la porte d'entrée.
                'mimetypes:image/jpeg,image/png,image/heic,image/heif,image/webp',
            ],
            'caption' => ['nullable', 'string', 'max:'.AttachPhoto::MAX_CAPTION],
        ]);

        try {
            $media = $this->attach->handle(
                $target,
                $request->file('photo'),
                $actor,
                $validated['caption'] ?? null,
            );
        } catch (InfectedUpload|UnsupportedImage $exception) {
            // Le message est écrit pour être lu, et il dit quoi faire.
            return back()->withErrors(['photo' => $exception->getMessage()]);
        }

        return back()->with('status', $media->getCustomProperty('print_ready') === true
            ? __('common.photos.added')
            : __('common.photos.added_small'));
    }

    public function updateCaption(Request $request, string $photo, ?string $story = null): RedirectResponse
    {
        $target = self::storyFor($request, $story);
        $found = self::photoOf($target, $photo);

        $validated = $request->validate([
            'caption' => ['nullable', 'string', 'max:'.AttachPhoto::MAX_CAPTION],
        ]);

        $this->captions->handle($target, $found, self::actorFor($request), $validated['caption'] ?? null);

        return back()->with('status', __('common.photos.caption_saved'));
    }

    public function destroy(Request $request, string $photo, ?string $story = null): RedirectResponse
    {
        $target = self::storyFor($request, $story);

        $this->remove->handle($target, self::photoOf($target, $photo), self::actorFor($request));

        return back()->with('status', __('common.photos.removed'));
    }

    /**
     * L'histoire visée.
     *
     * Depuis un jeton d'enregistrement, elle **est** le sujet du jeton : il
     * n'y en a qu'une, et le périmètre du lien s'arrête là. Dans les autres
     * espaces, elle est nommée dans l'URL, et on vérifie alors qu'elle
     * appartient bien au projet du porteur.
     */
    private static function storyFor(Request $request, ?string $story): Story
    {
        $subject = $request->attributes->get('token_subject');

        if ($subject instanceof Story) {
            return $subject;
        }

        abort_if($story === null, 404);

        $found = Story::query()->whereKey($story)->first();

        abort_unless($found instanceof Story, 404);

        // Le porteur et l'histoire doivent partager le projet : sans cette
        // ligne, un jeton d'écoute valide ouvrirait l'histoire d'une autre
        // famille.
        abort_unless(self::belongsToActor($found, $request), 404);

        return $found;
    }

    private static function belongsToActor(Story $story, Request $request): bool
    {
        $actor = self::actorFor($request);

        return match (true) {
            $actor instanceof Narrator => $actor->project_id === $story->project_id,
            $actor instanceof FamilyMember => $actor->project_id === $story->project_id,
            default => $story->project->owner_user_id === $actor->getKey(),
        };
    }

    /**
     * Qui agit : le sujet du jeton, ou l'utilisateur connecté.
     */
    private static function actorFor(Request $request): Model
    {
        $subject = $request->attributes->get('token_subject');

        if ($subject instanceof Story) {
            // Un jeton d'enregistrement porte l'histoire ; l'acteur est son
            // narrateur.
            return $subject->narrator;
        }

        if ($subject instanceof Model) {
            return $subject;
        }

        $user = $request->user();

        abort_if($user === null, 403);

        return $user;
    }

    private static function photoOf(Story $story, string $photo): Media
    {
        $found = $story->getMedia(Story::PHOTOS)
            ->first(fn (Media $media): bool => (string) $media->id === $photo);

        abort_unless($found instanceof Media, 404);

        return $found;
    }
}
