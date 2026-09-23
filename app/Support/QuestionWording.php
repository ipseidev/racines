<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\AddressForm;
use App\Enums\GrammaticalGender;
use App\Models\Project;
use App\Models\Question;

/**
 * Ce que dit une question du corpus à une narratrice précise.
 *
 * Deux problèmes, deux réponses. Le tutoiement change toute la phrase — « où
 * êtes-vous née » devient « où es-tu née », les possessifs suivent — et une
 * conversion automatique ferait des fautes : on l'écrit donc à la main, dans
 * `text_tu`. Le genre ne touche qu'un mot ou deux : un marqueur suffit.
 *
 * Le marqueur s'écrit `{masculin|féminin}` ou `{masculin|féminin|neutre}` :
 *
 * - `né{|e}` donne « né », « née », ou « né·e » quand le genre est inconnu ;
 * - `{fier|fière|fier·ère}` a besoin de sa troisième forme, parce que le
 *   féminin ne prolonge pas le masculin et qu'aucune règle ne devine le point
 *   médian qu'un humain écrirait.
 *
 * Le point médian ne survit donc qu'au genre inconnu, et jamais en dur dans
 * un gabarit : `problems()` le refuse.
 *
 * Les **accolades doubles** parlent de l'acheteur, la personne qui a offert le
 * livre : `{{prénom}}` donne son prénom, `{{|e}}` et `{{il|elle|il ou elle}}`
 * s'accordent à son genre, avec la même règle de forme neutre. Une phrase peut
 * mêler les deux : « ce qui t'a frappé{|e} chez {{lui|elle|lui ou elle}} ».
 * Sans prénom connu, `{{prénom}}` reste tel quel — une telle question ne part
 * pas (`Question::scopeSendableWithoutProfile`).
 */
final class QuestionWording
{
    private const MARKER = '/\{([^{}]*)\}/u';

    private const BUYER_MARKER = '/\{\{([^{}]*)\}\}/u';

    /** Les variables connues des accolades doubles. */
    private const BUYER_NAME = 'prénom';

    public static function for(
        Question $question,
        AddressForm $form,
        ?GrammaticalGender $gender,
        ?string $buyerName = null,
        ?GrammaticalGender $buyerGender = null,
    ): string {
        $template = $form === AddressForm::Tu && $question->text_tu !== null
            ? $question->text_tu
            : $question->text;

        return self::resolve($template, $gender, $buyerName, $buyerGender);
    }

    /**
     * La question telle qu'elle partira dans ce projet : son tutoiement, le
     * genre de la narratrice, et l'acheteur quand la famille a demandé qu'on
     * parle de lui.
     */
    public static function forProject(Question $question, Project $project, ?GrammaticalGender $gender): string
    {
        $profile = QuestionProfile::of($project);

        return self::for($question, $project->address_form, $gender, $profile->buyerName(), $profile->buyerGender());
    }

    public static function resolve(
        string $template,
        ?GrammaticalGender $gender,
        ?string $buyerName = null,
        ?GrammaticalGender $buyerGender = null,
    ): string {
        // L'acheteur d'abord : `{{|e}}` contient `{|e}`, que la passe de la
        // narratrice prendrait pour le sien.
        $placeholders = [];

        $withBuyer = (string) preg_replace_callback(self::BUYER_MARKER, static function (array $match) use ($buyerName, $buyerGender, &$placeholders): string {
            if ($match[1] === self::BUYER_NAME) {
                if ($buyerName === null) {
                    // Mis de côté le temps de la seconde passe, qui le
                    // prendrait pour un marqueur de la narratrice.
                    $placeholders[] = $match[0];

                    return "\u{E000}".(count($placeholders) - 1)."\u{E001}";
                }

                return $buyerName;
            }

            return self::agree(explode('|', $match[1]), $buyerGender) ?? $match[0];
        }, $template);

        $resolved = (string) preg_replace_callback(
            self::MARKER,
            static fn (array $match): string => self::agree(explode('|', $match[1]), $gender) ?? $match[0],
            $withBuyer,
        );

        return (string) preg_replace_callback(
            "/\u{E000}(\d+)\u{E001}/u",
            static fn (array $match): string => $placeholders[(int) $match[1]],
            $resolved,
        );
    }

    /** Le gabarit nomme-t-il l'acheteur ? Sans son prénom, il ne peut pas partir. */
    public static function needsBuyerName(string $template): bool
    {
        return str_contains($template, '{{'.self::BUYER_NAME.'}}');
    }

    /**
     * La forme d'un marqueur pour un genre, ou null si ce n'est pas un marqueur.
     *
     * @param  list<string>  $forms
     */
    private static function agree(array $forms, ?GrammaticalGender $gender): ?string
    {
        if (count($forms) < 2) {
            return null;
        }

        return match ($gender) {
            GrammaticalGender::Masculine => $forms[0],
            GrammaticalGender::Feminine => $forms[1],
            null => $forms[2] ?? self::neutral($forms[0], $forms[1]),
        };
    }

    /**
     * Ce qui ne va pas dans un gabarit, en français, pour le rapport de
     * `corpus:sync`. Vide quand tout va bien.
     *
     * @return list<string>
     */
    public static function problems(string $template): array
    {
        $problems = [];

        preg_match_all(self::BUYER_MARKER, $template, $buyer);

        foreach ($buyer[1] as $inside) {
            if (! str_contains($inside, '|')) {
                if ($inside !== self::BUYER_NAME) {
                    $problems[] = "{{{$inside}}} : variable inconnue (seule {{".self::BUYER_NAME.'}} existe), ou marqueur à deux ou trois formes attendu';
                }

                continue;
            }

            $problems = [...$problems, ...self::formProblems($inside, '{{', '}}')];
        }

        $template = (string) preg_replace(self::BUYER_MARKER, '', $template);
        $outside = (string) preg_replace(self::MARKER, '', $template);

        if (str_contains($outside, '{') || str_contains($outside, '}')) {
            $problems[] = 'accolade non appariée';
        }

        if (str_contains($outside, '·')) {
            $problems[] = 'point médian en dur : écrire un marqueur, par exemple né{|e}';
        }

        preg_match_all(self::MARKER, $template, $matches);

        foreach ($matches[1] as $inside) {
            $problems = [...$problems, ...self::formProblems($inside, '{', '}')];
        }

        return $problems;
    }

    /** @return list<string> */
    private static function formProblems(string $inside, string $open, string $close): array
    {
        $forms = explode('|', $inside);
        $marker = $open.$inside.$close;

        if (count($forms) < 2 || count($forms) > 3) {
            return ["marqueur {$marker} : deux ou trois formes attendues"];
        }

        if (count($forms) === 2 && ! str_starts_with($forms[1], $forms[0])) {
            return ["marqueur {$marker} : le féminin ne prolonge pas le masculin, écrire la forme neutre en troisième"];
        }

        return [];
    }

    /** « né » et « née » donnent « né·e » ; « seul » et « seule », « seul·e ». */
    private static function neutral(string $masculine, string $feminine): string
    {
        return $masculine.'·'.mb_substr($feminine, mb_strlen($masculine));
    }
}
