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
    /**
     * Une histoire lue depuis un QR imprimé, sans personne derrière.
     *
     * Le lecteur d'un QR n'est pas un proche identifié : le livre circule, il
     * se prête, il se lit chez quelqu'un d'autre. On ne sait donc **pas qui
     * lit**, et c'est voulu — exiger un compte pour écouter la voix de sa
     * grand-mère dans un livre qu'on tient entre les mains serait absurde.
     *
     * Ce que cela retire : les réactions (elles ont un auteur), le dépôt de
     * photos (il en a un aussi) et la liste des autres histoires (le QR ouvre
     * un chapitre, pas une bibliothèque). Ce qui reste est ce que la page
     * imprimée promet : la voix, et le texte.
     *
     * @return array<string, mixed>
     */
    public static function qrStoryProps(Story $story, MediaStorage $storage): array
    {
        $props = self::storyProps($story, null, $storage);

        return [
            ...$props,
            'yourReactions' => [],
            'canContribute' => false,
            'mode' => 'qr',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function storyProps(Story $story, ?FamilyMember $member, MediaStorage $storage): array
    {
        $recording = $story->currentRecording()->first();
        $key = $recording === null
            ? null
            : $recording->derived_mp3_path ?? $recording->original_path;

        // La vidéo quand il y en a une, et l'audio dans tous les cas : un
        // proche dans le train préfère écouter, et le MP3 est le seul format
        // que le QR du livre sait servir.
        $video = $recording?->playableVideoPath();

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
            'videoUrl' => $video === null ? null : $storage->temporaryUrl($video, 60),
            'text' => Transcript::readableFor($story)?->text,
            'verbatim' => $story->transcripts()
                ->ofKind(TranscriptKind::Verbatim)->current()->first()?->text,
            'aiLabel' => __('family.story.ai_label', [
                'first_name' => $story->narrator->first_name,
            ]),
            'reactions' => self::reactions($story),
            'yourReactions' => $member === null ? [] : self::reactionTypesOf($story, $member),
            // Mise en forme une seule fois, dans `PhotoPresenter` : les
            // quatre espaces la partagent, et une seconde version
            // oublierait le texte alternatif ou servirait une URL
            // permanente là où elle doit être temporaire.
            'photos' => PhotoPresenter::forStory($story),
            'mode' => 'family',
            // Le bouton d'ajout n'existe que pour qui peut contribuer : un
            // bouton grisé invite à demander pourquoi, un bouton absent non.
            'canContribute' => $member !== null && PhotoAccess::canAttach($story, $member),
        ];
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
