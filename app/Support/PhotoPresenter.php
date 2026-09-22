<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\FamilyMember;
use App\Models\Story;
use App\Models\User;
use App\Services\Storage\MediaStorage;
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
                // Posée au dépôt : elle illustre la question, pas la réponse.
                'isPrompt' => $photo->getCustomProperty('is_prompt') === true,
                'thumbUrl' => self::url($photo, 'thumb'),
                'url' => self::url($photo, 'web'),
                'alt' => $photo->getCustomProperty('caption')
                    ?? __('family.story.photo_alt', [
                        'first_name' => self::depositorName($story, $photo),
                    ]),
                // Le prénom du déposant, ou rien quand on ne le connaît pas :
                // l'écran choisit alors « votre famille » plutôt que de
                // nommer « quelqu'un », qui sonne comme un inconnu.
                'from' => self::depositorFirstName($story, $photo),
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

    /**
     * Les photos qui **posent** la question, pour la page d'enregistrement.
     *
     * Un proche ou l'Initiateur·rice joint une image à une question pas
     * encore racontée — « raconte-nous cette photo » —, et la narratrice ne
     * la voyait nulle part : le dépôt fonctionnait, l'affichage n'existait
     * pas, et l'image attendait en base d'être vue après l'enregistrement,
     * c'est-à-dire trop tard.
     *
     * Rien de ce que la narratrice a elle-même joint à sa réponse n'apparaît
     * ici : `is_prompt` est posé au dépôt, d'après l'état de l'histoire à ce
     * moment-là.
     *
     * Ce que cette page montre de plus que les autres, et qui mérite d'être
     * dit : une photo de famille sur une page ouverte par un **lien
     * porteur**. Le contrôleur pose que cette page ne porte « aucune donnée
     * d'un tiers » ; une image en est une, et le prénom de qui l'a déposée
     * aussi. L'arbitrage est assumé — sans l'image, la question n'a plus de
     * sens — et il se révoque en supprimant cet appel.
     *
     * @return list<array<string, mixed>>
     */
    public static function promptsForStory(Story $story): array
    {
        return array_values(array_filter(
            self::forStory($story),
            fn (array $photo): bool => $photo['isPrompt'] === true,
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
     * encore sa version web. Servir l'original en attendant coûte de la bande
     * passante et évite une image cassée — ce qui, sur la page de quelqu'un
     * qui vient de déposer sa photo, vaut mieux.
     *
     * Elle passe par le **port de stockage** et non par la médiathèque.
     * `Media::getTemporaryUrl()` signe sur l'endpoint que voit le serveur, et
     * le navigateur ne le résout pas : hors production, où les deux adresses
     * coïncident, aucune photo ne s'affichait — le carré restait vide (T-192).
     * C'est mot pour mot la leçon T-56, apprise pour l'audio de la page
     * famille, et `MediaStorage::temporaryUrl()` existe pour ça.
     */
    private static function url(Media $photo, string $conversion): string
    {
        $key = $photo->hasGeneratedConversion($conversion)
            ? self::relative($photo->getPath($conversion))
            : self::relative($photo->getPath());

        return app(MediaStorage::class)->temporaryUrl($key, 60);
    }

    /**
     * Le chemin du fichier tel que le stockage le connaît.
     *
     * `getPath()` rend un chemin absolu pour un disque local et une clé pour
     * un disque objet ; le port, lui, ne connaît que des clés. Le préfixe du
     * disque local est donc retiré, sans quoi la clé porterait le chemin du
     * conteneur — et la signature vaudrait pour un objet qui n'existe pas.
     */
    private static function relative(string $path): string
    {
        $root = (string) config('filesystems.disks.r2.root', '');

        if ($root !== '' && str_starts_with($path, $root)) {
            return ltrim(mb_substr($path, mb_strlen($root)), '/');
        }

        return ltrim($path, '/');
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
        return self::depositorFirstName($story, $photo)
            ?? __('family.story.someone');
    }

    /**
     * Le même prénom, ou **rien** quand on ne le connaît pas.
     *
     * Deux besoins distincts derrière la même donnée : un texte alternatif ne
     * peut pas être vide et se contente de « quelqu'un », alors qu'une phrase
     * lue à l'écran — « Envoyée par Claire » — doit pouvoir se replier sur
     * « votre famille » plutôt que d'annoncer un inconnu à quelqu'un qui
     * s'apprête à raconter un souvenir.
     *
     * Le cas `user` manquait, et c'est le plus fréquent : les photos jointes
     * à une question viennent du tableau de bord de l'Initiateur·rice, qui
     * est un `User`. Toutes tombaient donc sur « quelqu'un ».
     */
    private static function depositorFirstName(Story $story, Media $photo): ?string
    {
        $type = $photo->getCustomProperty('depositor_type');
        $id = $photo->getCustomProperty('depositor_id');

        if ($type === 'narrator') {
            return $story->narrator->first_name;
        }

        if ($type === 'family_member' && is_string($id)) {
            $nom = FamilyMember::query()->whereKey($id)->value('display_name');

            return is_string($nom) ? self::firstWord($nom) : null;
        }

        if ($type === 'user' && is_string($id)) {
            $nom = User::query()->whereKey($id)->value('name');

            return is_string($nom) ? self::firstWord($nom) : null;
        }

        return null;
    }

    /**
     * Le prénom d'un nom complet.
     *
     * Les comptes portent « Claire Dubois » ; la page d'enregistrement dit
     * « Envoyée par Claire ». Un nom de famille sur cette page en ferait une
     * fiche, et l'on s'adresse à quelqu'un qui connaît sa propre famille par
     * son prénom.
     */
    private static function firstWord(string $nom): ?string
    {
        $premier = preg_split('/\s+/u', mb_trim($nom))[0] ?? '';

        return $premier === '' ? null : $premier;
    }
}
