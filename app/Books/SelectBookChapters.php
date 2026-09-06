<?php

declare(strict_types=1);

namespace App\Books;

use App\Enums\TranscriptKind;
use App\Exceptions\Domain\StoryNotPrintable;
use App\Models\Book;
use App\Models\BookChapter;
use App\Models\Story;
use App\Models\Transcript;
use Illuminate\Support\Facades\DB;

/**
 * Les chapitres d'un livre : lesquels, dans quel ordre, avec quel texte.
 *
 * **Se rejoue.** Une histoire validée après la première génération du BAT
 * doit pouvoir rejoindre le livre, et une exclusion déjà décidée ne doit pas
 * être annulée au passage. La commande ajoute donc les manquants et ne touche
 * jamais aux lignes existantes : l'ordre et les exclusions appartiennent à la
 * famille, pas au calcul.
 *
 * **L'ordre par défaut est chronologique**, celui du souvenir. Pas celui de
 * la base, qui suivrait l'ordre d'enregistrement — une histoire d'enfance
 * racontée en dernier ouvrirait le livre par sa fin.
 */
final readonly class SelectBookChapters
{
    /** Le pas entre deux positions, pour laisser insérer sans tout renuméroter. */
    private const STEP = 10;

    public function handle(Book $book): void
    {
        $existing = $book->chapters()->pluck('story_id')->all();

        $stories = $book->project->stories()
            ->whereIn('state', ComputeBookReadiness::countableStates())
            ->whereNotIn('id', $existing)
            // `recorded_at` et non `validated_at` : c'est la date où le récit
            // a été dit. Une validation groupée un dimanche soir mettrait
            // sinon dix histoires dans un ordre arbitraire.
            ->orderBy('recorded_at')
            ->orderBy('created_at')
            ->get();

        if ($stories->isEmpty()) {
            return;
        }

        $position = (int) $book->chapters()->max('position');

        DB::transaction(function () use ($book, $stories, &$position): void {
            foreach ($stories as $story) {
                $position += self::STEP;

                /*
                 * Cochée seulement s'il y a quelque chose à imprimer.
                 *
                 * Une histoire validée dont la transcription a échoué, ou une
                 * réponse écrite restée vide, produit une page portant sa
                 * question, sa date, son QR — et pas une ligne de récit.
                 * Trouvé sur un rendu réel : une page blanche dans un livre
                 * imprimé (T-197). Elle reste dans la liste, décochée : la
                 * masquer ferait chercher pourquoi l'histoire n'y est pas.
                 *
                 * Une photo suffit : le récit n'est pas toujours du texte.
                 */
                $imprimable = trim(self::textOf($story)) !== ''
                    || $story->getMedia(Story::PHOTOS)->isNotEmpty();

                // `associate` et non un `story_id` rempli en masse : la
                // colonne n'est pas remplissable, et c'est voulu — un
                // chapitre ne change pas d'histoire, il se retire.
                $chapter = new BookChapter(['position' => $position, 'included' => $imprimable]);
                $chapter->story()->associate($story);
                $book->chapters()->save($chapter);
            }
        });
    }

    /**
     * Ajouter une histoire nommément, et vérifier deux fois.
     *
     * La liste par défaut ne prend que des histoires imprimables ; celle-ci
     * vient d'un clic, donc d'une URL, donc de n'importe où.
     */
    public function include(Book $book, Story $story): BookChapter
    {
        if ($story->project_id !== $book->project_id) {
            throw StoryNotPrintable::otherProject();
        }

        if (! in_array($story->state->getValue(), ComputeBookReadiness::countableStates(), true)) {
            throw StoryNotPrintable::notValidated();
        }

        $chapter = $book->chapters()->where('story_id', $story->getKey())->first();

        if (! $chapter instanceof BookChapter) {
            $chapter = new BookChapter(['position' => (int) $book->chapters()->max('position') + self::STEP]);
            $chapter->story()->associate($story);
            $chapter->book()->associate($book);
        }

        $chapter->included = true;
        $chapter->save();

        return $chapter;
    }

    /**
     * Le texte qui sera imprimé.
     *
     * La correction d'abord, la mise au propre ensuite, le mot à mot en
     * dernier recours. L'ordre dit une chose simple : on imprime la version la
     * plus travaillée dont on dispose, et **jamais rien qui n'existe pas** —
     * une histoire transcrite mais non mise au propre garde sa voix brute
     * plutôt que de sortir vide.
     */
    public static function textOf(Story $story): string
    {
        foreach ([TranscriptKind::Edited, TranscriptKind::Fluide, TranscriptKind::Verbatim] as $kind) {
            $transcript = $story->transcripts()
                ->ofKind($kind)
                ->current()
                ->orderByDesc('version')
                ->first();

            if ($transcript instanceof Transcript && trim($transcript->text) !== '') {
                return $transcript->text;
            }
        }

        return '';
    }
}
