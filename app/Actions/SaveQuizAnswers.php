<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\BookCover;
use App\Enums\BookTitle;
use App\Enums\Channel;
use App\Enums\QuestionTheme;
use App\Enums\QuizAgeBand;
use App\Enums\QuizDistance;
use App\Enums\QuizOccasion;
use App\Enums\QuizRelationship;
use App\Enums\QuizStorytellerStyle;
use App\Enums\TechComfort;
use App\Models\CheckoutDraft;
use App\Settings\PilotSettings;
use App\Support\Options;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

/**
 * Les réponses du quiz, rangées dans le brouillon de commande.
 *
 * C'est ici que se joue la différence avec le tunnel de découverte du
 * leader, dont les réponses ne servent qu'à vendre : l'archétype affiché à
 * la fin est décoratif, la couleur de couverture choisie ne suit même pas
 * dans la commande. Sept de nos neuf réponses sont des **champs du tunnel
 * d'achat** — le lien de parenté, le prénom, l'aisance avec un téléphone, le
 * canal, la date d'envoi — et les deux autres décriront le projet.
 *
 * La couverture du livre en fait partie, et c'est l'écart le plus net avec
 * le tunnel qu'on imite : la couleur qu'il fait choisir à la fin ne suit même
 * pas dans la commande. Ici, `projects.book_cover` la garde, et le BAT
 * l'imprime.
 *
 * Ce qu'on n'écrit **pas** : le mot personnel. Il est obligatoire à l'étape
 * du cadeau, et le préremplir ferait sauter cette étape — personne
 * n'écrirait plus le seul texte du tunnel que le narrateur lira vraiment.
 * Le laisser vide est un choix, pas un oubli.
 */
final class SaveQuizAnswers
{
    /**
     * Trois thèmes au moins.
     *
     * Le nombre n'est pas rond par hasard : trois cases à cocher au lieu
     * d'une transforment un écran de plus en un engagement, et trois thèmes
     * suffisent à composer un aperçu qui montre une étendue.
     */
    public const MIN_THEMES = 3;

    /** L'horizon d'un cadeau programmé, comme à l'étape 3 du tunnel. */
    private const MAX_DAYS_AHEAD = 90;

    /** L'étape du tunnel qui reste à remplir : les coordonnées du narrateur. */
    public const RESUME_STEP = 2;

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            // Soi-même ne passe pas par ici : l'écran l'envoie au tunnel.
            'relationship' => ['required', Rule::in(array_column(QuizRelationship::forOthers(), 'value'))],
            'age_band' => ['required', new Enum(QuizAgeBand::class)],
            'distance' => ['required', new Enum(QuizDistance::class)],
            'themes' => ['required', 'array', 'min:'.self::MIN_THEMES],
            'themes.*' => [new Enum(QuestionTheme::class), 'distinct'],
            'storyteller' => ['required', new Enum(QuizStorytellerStyle::class)],
            'tech_comfort' => ['required', new Enum(TechComfort::class)],
            'channel' => ['required', Rule::in(Channel::narratorPreferences())],
            'first_name' => ['required', 'string', 'max:80'],
            'nickname' => ['nullable', 'string', 'max:40'],
            'book_cover' => ['required', new Enum(BookCover::class)],
            'book_title' => ['required', new Enum(BookTitle::class)],
            // Le titre libre n'est exigé que s'il a été demandé, et il est
            // borné à ce qu'une couverture sait porter : au-delà, la ligne
            // se compose en trois corps trop petits pour être lus.
            'book_title_custom' => [
                'nullable', 'string', 'max:80',
                'required_if:book_title,'.BookTitle::Custom->value,
            ],
            'occasion' => ['required', new Enum(QuizOccasion::class)],
            'send_at' => [
                'required', 'date', 'after_or_equal:today',
                'before_or_equal:'.now()->addDays(self::MAX_DAYS_AHEAD)->toDateString(),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $answers  Déjà validées par `rules()`.
     */
    public function handle(CheckoutDraft $draft, array $answers): CheckoutDraft
    {
        $relationship = QuizRelationship::from((string) $answers['relationship']);

        /** @var list<string> $themes */
        $themes = array_values((array) $answers['themes']);

        return $draft->merge([
            // Les champs que le tunnel d'achat lira tels quels.
            'for' => 'relative',
            'relationship' => Options::label($relationship),
            'narrator_first_name' => trim((string) $answers['first_name']),
            'narrator_tech_comfort' => (string) $answers['tech_comfort'],
            'preferred_channel' => (string) $answers['channel'],
            // La couverture, comme le canal : un champ de la commande, pas
            // une réponse décorative. `FulfillOrder` la pose sur le projet et
            // le BAT l'imprime des mois plus tard.
            'book_cover' => (string) $answers['book_cover'],
            'book_title' => (string) $answers['book_title'],
            'book_title_custom' => self::custom($answers),
            'gift_send_at' => (string) $answers['send_at'],
            // L'heure n'est pas demandée : neuf écrans valent mieux que dix,
            // et le réglage du pilote donne déjà la bonne. Elle reste
            // modifiable à l'étape du cadeau.
            'gift_send_time' => sprintf('%02d:00', app(PilotSettings::class)->gift_send_hour),

            // Ce qui n'appartient qu'au quiz, sous sa propre clé : le tunnel
            // n'a pas à savoir ce qu'il y avait avant lui, et l'aperçu doit
            // pouvoir être effacé sans emporter la commande.
            'quiz' => [
                'relationship' => $relationship->value,
                'age_band' => (string) $answers['age_band'],
                'distance' => (string) $answers['distance'],
                'themes' => $themes,
                'storyteller' => (string) $answers['storyteller'],
                'nickname' => self::nickname($answers),
                'occasion' => (string) $answers['occasion'],
                'completed_at' => now()->toIso8601String(),
            ],
        ], self::RESUME_STEP);
    }

    /** Efface les réponses, et rien d'autre : le brouillon peut déjà porter une commande. */
    public function forget(CheckoutDraft $draft): CheckoutDraft
    {
        $payload = $draft->payload;
        unset($payload['quiz']);

        $draft->payload = $payload;
        $draft->save();

        return $draft;
    }

    /**
     * Le titre libre, ou rien.
     *
     * Vidé quand la formule n'est pas « un titre à moi » : sinon un texte
     * saisi puis abandonné resterait en base, et referait surface le jour où
     * quelqu'un rebasculerait sur le titre libre depuis l'espace.
     *
     * @param  array<string, mixed>  $answers
     */
    private static function custom(array $answers): ?string
    {
        if ((string) $answers['book_title'] !== BookTitle::Custom->value) {
            return null;
        }

        $custom = trim((string) ($answers['book_title_custom'] ?? ''));

        return $custom === '' ? null : $custom;
    }

    /**
     * Le petit nom, ou rien.
     *
     * « Mamie » plutôt que « Jeanne » dans le message qu'elle recevra : c'est
     * le mot par lequel elle est appelée depuis quarante ans, et il vaut tous
     * les efforts de rédaction du monde.
     *
     * @param  array<string, mixed>  $answers
     */
    private static function nickname(array $answers): ?string
    {
        $nickname = trim((string) ($answers['nickname'] ?? ''));

        return $nickname === '' ? null : $nickname;
    }
}
