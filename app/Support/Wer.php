<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Taux d'erreur par mot (WER).
 *
 * La normalisation compte autant que le calcul. Comparer « c'était » à
 * « c’était » — apostrophe droite contre typographique — donnerait une erreur
 * là où il n'y en a pas, et gonflerait le WER de tous les fournisseurs
 * également, donc masquerait l'écart qu'on cherche à mesurer.
 *
 * On ne normalise **pas** les nombres : « quatre-vingts » et « 80 » ne se
 * lisent pas pareil dans un livre, et un fournisseur qui rend l'un pour
 * l'autre change le texte.
 */
final class Wer
{
    /**
     * @return list<string>
     */
    public static function normalize(string $text): array
    {
        $text = mb_strtolower(trim($text));

        // Apostrophes et guillemets typographiques ramenés à leur forme droite.
        $text = str_replace(['’', '‘', '“', '”', '«', '»', '–', '—'], ["'", "'", '"', '"', '"', '"', '-', '-'], $text);

        // Ponctuation retirée, tirets et apostrophes conservés : ils tiennent
        // les mots composés et les élisions, qui sont des mots.
        $text = (string) preg_replace('/[^\p{L}\p{N}\'\-\s]/u', ' ', $text);
        $text = (string) preg_replace('/\s+/u', ' ', $text);

        $words = explode(' ', trim($text));

        return array_values(array_filter($words, static fn (string $word): bool => $word !== ''));
    }

    /**
     * WER = (substitutions + insertions + suppressions) / mots de référence.
     */
    public static function compute(string $reference, string $hypothesis): float
    {
        $expected = self::normalize($reference);
        $actual = self::normalize($hypothesis);

        if ($expected === []) {
            return $actual === [] ? 0.0 : 1.0;
        }

        return self::distance($expected, $actual) / count($expected);
    }

    /**
     * Levenshtein sur les mots, en gardant une seule ligne en mémoire : un
     * corpus de deux minutes fait mille mots, et la matrice complète n'apporte
     * rien.
     *
     * @param  list<string>  $expected
     * @param  list<string>  $actual
     */
    public static function distance(array $expected, array $actual): int
    {
        $previous = range(0, count($actual));

        foreach ($expected as $i => $expectedWord) {
            $current = [$i + 1];

            foreach ($actual as $j => $actualWord) {
                $current[$j + 1] = min(
                    $previous[$j + 1] + 1,                                   // suppression
                    $current[$j] + 1,                                        // insertion
                    $previous[$j] + ($expectedWord === $actualWord ? 0 : 1), // substitution
                );
            }

            $previous = $current;
        }

        return (int) end($previous);
    }

    /**
     * @param  list<float>  $values
     */
    public static function median(array $values): ?float
    {
        if ($values === []) {
            return null;
        }

        sort($values);
        $count = count($values);
        $middle = intdiv($count, 2);

        return $count % 2 === 1
            ? $values[$middle]
            : ($values[$middle - 1] + $values[$middle]) / 2;
    }

    /**
     * @param  list<float>  $values
     */
    public static function percentile(array $values, float $percentile): ?float
    {
        if ($values === []) {
            return null;
        }

        sort($values);

        $index = (int) ceil($percentile / 100 * count($values)) - 1;

        return $values[max(0, min($index, count($values) - 1))];
    }
}
