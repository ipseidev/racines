<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\FamilyMember;
use App\Models\Story;
use App\States\Story\InBook;
use App\States\Story\Shared;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Les photos d'une histoire, prêtes pour l'écran.
 *
 * Un seul endroit qui les met en forme, appelé par les quatre espaces. La
 * raison est celle de tous les présentateurs de ce dépôt : une seconde mise
 * en forme écrite ailleurs oublierait le texte alternatif, ou pire, servirait
 * une URL permanente là où elle doit être temporaire.
 *
 * Les URL sont **régénérées à chaque chargement** et valables une heure : une
 * URL de photo de famille ne traîne pas dans un historique de navigation.
 */
final class PhotoPresenter
{
    /**
     * Toutes les photos de l'histoire.
     *
     * À n'appeler que là où l'histoire est déjà visible de la personne : la
     * page famille (passée par `VisibleStoriesForFamilyMember`) et l'espace
     * du narrateur, qui est chez lui.
     *
     * @return list<array<string, mixed>>
     */
    public static function forStory(Story $story, ?Model $viewer = null): array
    {
        return array_values($story->getMedia(Story::PHOTOS)
            ->map(fn (Media $photo): array => [
                'id' => $photo->id,
                'caption' => $photo->getCustomProperty('caption'),
                'printReady' => $photo->getCustomProperty('print_ready') === true,
                'thumbUrl' => self::url($photo, 'thumb'),
                'url' => self::url($photo, 'web'),
                'alt' => $photo->getCustomProperty('caption')
                    ?? __('family.story.photo_alt', [
                        'first_name' => self::depositorName($story, $photo),
                    ]),
                'mine' => $viewer !== null && self::depositedBy($photo, $viewer),
            ])
            ->all());
    }

    /**
     * Les photos qu'une Initiateur·rice a le droit de voir sur cette histoire.
     *
     * Une photo est du **contenu**, comme le texte et la voix. Sur une
     * histoire que le narrateur n'a pas partagée, l'Initiateur·rice ne voit
     * donc que **ses propres** dépôts — ni ceux du narrateur, ni ceux d'un
     * proche.
     *
     * C'est l'invariant du bloc 08 appliqué aux photos, et il aurait été
     * facile de le perdre : le tableau de bord est « son » espace, et rien
     * n'y rappelle qu'une photo jointe par quelqu'un d'autre à un récit non
     * partagé ne lui appartient pas encore.
     *
     * @return list<array<string, mixed>>
     */
    public static function forInitiator(Story $story, Model $owner): array
    {
        $shared = $story->state instanceof Shared || $story->state instanceof InBook;

        return array_values(array_filter(
            self::forStory($story, $owner),
            fn (array $photo): bool => $shared || $photo['mine'] === true,
        ));
    }

    private static function depositedBy(Media $photo, Model $viewer): bool
    {
        return $photo->getCustomProperty('depositor_type') === $viewer->getMorphClass()
            && $photo->getCustomProperty('depositor_id') === (string) $viewer->getKey();
    }

    /**
     * L'URL d'une conversion, ou de l'original si elle n'est pas prête.
     *
     * Les conversions partent en file : une photo tout juste déposée n'a pas
     * encore sa miniature. Servir l'original en attendant coûte de la bande
     * passante et évite une image cassée — ce qui, sur la page de quelqu'un
     * qui vient de déposer sa photo, vaut mieux.
     */
    private static function url(Media $photo, string $conversion): string
    {
        return $photo->hasGeneratedConversion($conversion)
            ? $photo->getTemporaryUrl(now()->addHour(), $conversion)
            : $photo->getTemporaryUrl(now()->addHour());
    }

    /**
     * Le prénom du déposant, pour le texte alternatif.
     *
     * « Photo jointe par Claire » plutôt que « Photo » : un lecteur d'écran
     * qui annonce dix fois « Photo » ne dit rien, et le prénom situe l'image
     * dans la famille.
     */
    private static function depositorName(Story $story, Media $photo): string
    {
        $type = $photo->getCustomProperty('depositor_type');
        $id = $photo->getCustomProperty('depositor_id');

        if ($type === 'narrator') {
            return $story->narrator->first_name;
        }

        if ($type === 'family_member' && is_string($id)) {
            return FamilyMember::query()->whereKey($id)->value('display_name')
                ?? __('family.story.someone');
        }

        return __('family.story.someone');
    }
}
