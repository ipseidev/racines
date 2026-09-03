<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\ReactionType;
use App\Enums\TokenType;
use App\Enums\TranscriptKind;
use App\Models\AccessToken;
use App\Models\FamilyMember;
use App\Models\ListenEvent;
use App\Models\Story;
use App\Models\Transcript;
use App\Queries\VisibleStoriesForFamilyMember;
use App\Services\Storage\MediaStorage;
use Illuminate\Http\Request;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Ce que l'espace famille montre d'une histoire, et rien de plus.
 *
 * Un seul endroit compose ces props. La raison est la même que pour
 * `VisibleStoriesForFamilyMember` : un second endroit, écrit plus tard,
 * ajouterait un champ de trop — un identifiant de narrateur, une coordonnée,
 * un chemin d'objet — et personne ne s'en apercevrait avant que ça compte.
 *
 * Les prénoms des proches sont exposés ; leurs coordonnées, jamais. Un lien
 * d'écoute ne doit pas devenir un carnet d'adresses de la famille.
 */
final class FamilyPresenter
{
    /**
     * Le proche que ce jeton désigne.
     *
     * Un lien d'histoire porte l'histoire ; un lien de projet porte le
     * proche. Dans les deux cas on remonte au proche, parce que c'est lui qui
     * détermine ce qui est visible.
     */
    public static function memberFor(Request $request): FamilyMember
    {
        $subject = $request->attributes->get('token_subject');

        if ($subject instanceof FamilyMember) {
            return $subject;
        }

        $token = $request->attributes->get('access_token');

        abort_unless($token instanceof AccessToken, 404);
        abort_unless($subject instanceof Story, 404);

        // Un `listen_story` est émis pour un proche nommé : `issued_to`
        // porte la personne, le sujet porte l'histoire.
        $member = $token->issuedTo;

        abort_unless($member instanceof FamilyMember, 404);

        return $member;
    }

    /**
     * L'histoire que ce jeton autorise, quand il en désigne une seule.
     */
    public static function pinnedStory(Request $request): ?Story
    {
        $token = $request->attributes->get('access_token');
        $subject = $request->attributes->get('token_subject');

        if (! $token instanceof AccessToken || $token->type !== TokenType::ListenStory) {
            return null;
        }

        return $subject instanceof Story ? $subject : null;
    }

    /**
     * @return array<string, mixed>
     */
    public static function storyProps(Story $story, FamilyMember $member, MediaStorage $storage): array
    {
        $recording = $story->currentRecording()->first();
        $key = $recording === null
            ? null
            : $recording->derived_mp3_path ?? $recording->original_path;

        return [
            'id' => $story->id,
            'title' => $story->title,
            'question' => $story->questionText(),
            'sharedAt' => $story->shared_at?->toIso8601String(),
            'durationSeconds' => $recording?->duration_seconds === null
                ? null
                : (int) round((float) $recording->duration_seconds),
            // Régénérée à chaque chargement, valable une heure, et sans rien
            // de personnel dans son chemin (trois identifiants opaques).
            'audioUrl' => $key === null ? null : $storage->temporaryUrl($key, 60),
            'text' => Transcript::readableFor($story)?->text,
            'verbatim' => $story->transcripts()
                ->ofKind(TranscriptKind::Verbatim)->current()->first()?->text,
            'aiLabel' => __('family.story.ai_label', [
                'first_name' => $story->narrator->first_name,
            ]),
            'reactions' => self::reactions($story),
            'yourReactions' => self::reactionTypesOf($story, $member),
            'photos' => self::photos($story),
            // Le bouton d'ajout n'existe que pour qui peut contribuer : un
            // bouton grisé invite à demander pourquoi, un bouton absent non.
            'canContribute' => PhotoAccess::canAttach($story, $member),
        ];
    }

    /**
     * Les photos jointes, avec leurs URL temporaires.
     *
     * Cette méthode n'est appelée que depuis `storyProps`, qui n'est appelée
     * que pour une histoire déjà passée par `VisibleStoriesForFamilyMember`.
     * C'est la seule porte, et c'est ce qui garantit qu'une photo d'histoire
     * non partagée n'est jamais servie — critère de sortie du bloc 12.
     *
     * @return list<array<string, mixed>>
     */
    private static function photos(Story $story): array
    {
        return array_values($story->getMedia(Story::PHOTOS)
            ->map(fn (Media $photo): array => [
                'id' => $photo->id,
                'caption' => $photo->getCustomProperty('caption'),
                // Régénérées à chaque chargement : une URL de photo de
                // famille ne traîne pas dans un historique de navigation.
                'thumbUrl' => $photo->hasGeneratedConversion('thumb')
                    ? $photo->getTemporaryUrl(now()->addHour(), 'thumb')
                    : $photo->getTemporaryUrl(now()->addHour()),
                'url' => $photo->hasGeneratedConversion('web')
                    ? $photo->getTemporaryUrl(now()->addHour(), 'web')
                    : $photo->getTemporaryUrl(now()->addHour()),
                'alt' => $photo->getCustomProperty('caption')
                    ?? __('family.story.photo_alt', [
                        'first_name' => self::depositorName($story, $photo),
                    ]),
            ])
            ->all());
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

    /**
     * Les réactions déjà envoyées par ce proche.
     *
     * @return list<string>
     */
    private static function reactionTypesOf(Story $story, FamilyMember $member): array
    {
        return array_values($story->reactions()
            ->where('family_member_id', $member->id)
            ->pluck('type')
            ->map(fn (mixed $type): string => $type instanceof ReactionType ? $type->value : (string) $type)
            ->all());
    }

    /**
     * « Ont réagi : Marie, Paul. » Des prénoms, jamais des coordonnées.
     *
     * @return list<array<string, mixed>>
     */
    public static function reactions(Story $story): array
    {
        return array_values($story->reactions()
            ->with('familyMember')
            ->orderBy('created_at')
            ->get()
            ->map(fn ($reaction): array => [
                'name' => $reaction->familyMember->display_name,
                'type' => $reaction->type->value,
                'comment' => $reaction->comment,
            ])
            ->all());
    }

    /**
     * La liste des histoires écoutables, pour la page d'accueil.
     *
     * @return list<array<string, mixed>>
     */
    public static function cards(FamilyMember $member): array
    {
        $listened = ListenEvent::query()
            ->where('family_member_id', $member->id)
            ->pluck('reached_30s', 'story_id');

        return array_values((new VisibleStoriesForFamilyMember($member))->list()
            ->map(function (Story $story) use ($member, $listened): array {
                $recording = $story->currentRecording()->first();

                return [
                    'id' => $story->id,
                    'title' => $story->title,
                    'question' => $story->questionText(),
                    'sharedAt' => $story->shared_at?->toIso8601String(),
                    'durationSeconds' => $recording?->duration_seconds === null
                        ? null
                        : (int) round((float) $recording->duration_seconds),
                    // « Nouvelle » veut dire « pas encore écoutée par vous » :
                    // une page ouverte trois secondes n'est pas une écoute.
                    'isNew' => ($listened[$story->id] ?? false) === false,
                    'yourReactions' => self::reactionTypesOf($story, $member),
                ];
            })
            ->all());
    }

    /**
     * L'histoire précédente et la suivante, dans l'ordre de la liste.
     *
     * @return array<string, string|null>
     */
    public static function siblings(FamilyMember $member, Story $story): array
    {
        $ids = array_values((new VisibleStoriesForFamilyMember($member))->query()
            ->orderByDesc('shared_at')
            ->orderByDesc('sequence')
            ->pluck('id')
            ->map(fn (mixed $id): string => (string) $id)
            ->all());

        $position = array_search($story->id, $ids, true);

        if ($position === false) {
            return ['previous' => null, 'next' => null];
        }

        return [
            'previous' => $ids[$position - 1] ?? null,
            'next' => $ids[$position + 1] ?? null,
        ];
    }
}
