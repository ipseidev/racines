<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AddressForm;
use App\Enums\QuestionTheme;
use App\Models\Question;
use App\Support\QuestionWording;
use Illuminate\Support\Collection;

/**
 * Les quatre questions que l'aperçu de fin de quiz montre.
 *
 * Le leader termine son tunnel par une maquette de couverture au prénom du
 * client. Nous avons mieux à montrer, et c'est gratuit : les vraies premières
 * questions du corpus, choisies dans les thèmes qu'il vient de cocher. Ce
 * n'est plus une promesse de produit, c'est le produit.
 *
 * Ce qui oblige à passer par une action plutôt que par une requête dans le
 * contrôleur : l'ouverture obéit à la règle 1 du corpus — toujours une
 * question de difficulté 1 — même quand aucun thème facile n'a été coché. Un
 * aperçu qui ouvrirait sur « qu'aimeriez-vous que l'on retienne de vous ? »
 * annoncerait un parcours que `PickNextQuestion` n'enverra jamais.
 */
final class BuildQuizPreview
{
    /** Ce qu'on montre après l'ouverture : de quoi voir un rythme, pas un sommaire. */
    public const NEXT_COUNT = 3;

    /** La difficulté d'une question d'ouverture (règle 1 de l'annexe A). */
    private const EASIEST = 1;

    /**
     * @param  list<QuestionTheme>  $themes  Dans l'ordre où ils ont été cochés.
     * @return array{first: string|null, next: list<string>}
     */
    public function handle(array $themes): array
    {
        $corpus = Question::query()
            ->sendableWithoutProfile()
            ->orderBy('order_hint')
            ->get(['id', 'text', 'theme', 'difficulty', 'order_hint']);

        $first = $corpus->firstWhere('difficulty', self::EASIEST) ?? $corpus->first();

        if (! $first instanceof Question) {
            return ['first' => null, 'next' => []];
        }

        $remaining = $corpus->reject(fn (Question $question): bool => $question->is($first));

        return [
            'first' => QuestionWording::for($first, AddressForm::Vous, null),
            'next' => $this->next($remaining, $themes),
        ];
    }

    /**
     * Une question par thème coché, dans l'ordre où ils l'ont été.
     *
     * Un thème par tour plutôt que les trois premières du premier thème : ce
     * qu'on veut faire sentir, c'est l'étendue de ce qui va être demandé, pas
     * la profondeur d'un seul sujet.
     *
     * @param  Collection<int, Question>  $corpus
     * @param  list<QuestionTheme>  $themes
     * @return list<string>
     */
    private function next(Collection $corpus, array $themes): array
    {
        /** @var Collection<int, Question> $picked */
        $picked = new Collection;

        foreach ($themes as $theme) {
            if ($picked->count() >= self::NEXT_COUNT) {
                break;
            }

            $question = $corpus
                ->reject(fn (Question $candidate): bool => $picked->contains($candidate))
                ->firstWhere('theme', $theme);

            if ($question instanceof Question) {
                $picked->push($question);
            }
        }

        // Des thèmes trop pauvres — un corpus jeune, des questions retirées —
        // ne doivent pas rendre un aperçu à une ligne : on complète dans
        // l'ordre du corpus, qui va du facile vers l'intime.
        foreach ($corpus as $question) {
            if ($picked->count() >= self::NEXT_COUNT) {
                break;
            }

            if (! $picked->contains($question)) {
                $picked->push($question);
            }
        }

        /** @var list<string> */
        // Vouvoiement et genre inconnu : le quiz ne sait encore rien de la
        // personne à qui l'on offre.
        return $picked
            ->map(fn (Question $question): string => QuestionWording::for($question, AddressForm::Vous, null))
            ->values()
            ->all();
    }
}
