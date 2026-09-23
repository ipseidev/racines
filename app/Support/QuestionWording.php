<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\AddressForm;
use App\Enums\GrammaticalGender;
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
 */
final class QuestionWording
{
    private const MARKER = '/\{([^{}]*)\}/u';

    public static function for(Question $question, AddressForm $form, ?GrammaticalGender $gender): string
    {
        $template = $form === AddressForm::Tu && $question->text_tu !== null
            ? $question->text_tu
            : $question->text;

        return self::resolve($template, $gender);
    }

    public static function resolve(string $template, ?GrammaticalGender $gender): string
    {
        return (string) preg_replace_callback(self::MARKER, static function (array $match) use ($gender): string {
            $forms = explode('|', $match[1]);

            if (count($forms) < 2) {
                return $match[0];
            }

            return match ($gender) {
                GrammaticalGender::Masculine => $forms[0],
                GrammaticalGender::Feminine => $forms[1],
                null => $forms[2] ?? self::neutral($forms[0], $forms[1]),
            };
        }, $template);
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
        $outside = (string) preg_replace(self::MARKER, '', $template);

        if (str_contains($outside, '{') || str_contains($outside, '}')) {
            $problems[] = 'accolade non appariée';
        }

        if (str_contains($outside, '·')) {
            $problems[] = 'point médian en dur : écrire un marqueur, par exemple né{|e}';
        }

        preg_match_all(self::MARKER, $template, $matches);

        foreach ($matches[1] as $inside) {
            $forms = explode('|', $inside);

            if (count($forms) < 2 || count($forms) > 3) {
                $problems[] = "marqueur {{$inside}} : deux ou trois formes attendues";

                continue;
            }

            if (count($forms) === 2 && ! str_starts_with($forms[1], $forms[0])) {
                $problems[] = "marqueur {{$inside}} : le féminin ne prolonge pas le masculin, écrire la forme neutre en troisième";
            }
        }

        return $problems;
    }

    /** « né » et « née » donnent « né·e » ; « seul » et « seule », « seul·e ». */
    private static function neutral(string $masculine, string $feminine): string
    {
        return $masculine.'·'.mb_substr($feminine, mb_strlen($masculine));
    }
}
