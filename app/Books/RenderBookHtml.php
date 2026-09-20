<?php

declare(strict_types=1);

namespace App\Books;

use App\Enums\BookCover;
use App\Models\Book;
use App\Models\BookChapter;
use App\Models\Story;
use App\Services\Qr\QrImage;
use App\Settings\PilotSettings;
use App\Support\Brand;
use App\Support\Links;
use Illuminate\Support\Facades\View;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Le HTML du BAT, tout incrusté.
 *
 * Séparé du job qui le transforme en PDF, pour une raison qui se paie
 * autrement : le HTML est ce qu'on inspecte quand la pagination est fausse,
 * et il doit pouvoir s'obtenir sans lancer Chromium ni écrire un fichier.
 * C'est aussi sur lui que portent les assertions du bloc — un QR par
 * chapitre, la mention D-8, aucune ressource distante.
 *
 * **Aucune ressource distante**, donc : le CSS est lu sur le disque, les
 * polices deviennent des URI de données, Paged.js est incrusté depuis
 * `node_modules`, les QR sont des SVG en clair et les photos des URI de
 * données. Le coût est un HTML de plusieurs mégaoctets ; le bénéfice est un
 * livre identique à chaque rendu, y compris sur une machine hors ligne.
 */
final readonly class RenderBookHtml
{
    /**
     * Les polices du livre, et le nom sous lequel le CSS les appelle.
     *
     * Les fichiers sont ceux du site — déjà auto-hébergés (T-40, T-132) — et
     * non un second jeu téléchargé pour l'occasion : deux copies d'une police
     * finissent par diverger, et le livre n'aurait plus la typographie de la
     * marque.
     */
    private const FONTS = [
        'BookDisplay' => 'fonts/fraunces-var-roman.woff2',
        'BookBody' => 'fonts/inter-400.woff2',
    ];

    public function handle(Book $book): string
    {
        $book->loadMissing(['project.primaryNarrator', 'chapters.story.question', 'chapters.story.transcripts']);

        $firstName = $book->project->primaryNarrator?->first_name ?: '';

        $chapters = [];
        $number = 0;

        foreach ($book->chapters()->where('included', true)->orderBy('position')->get() as $chapter) {
            $story = $chapter->story;
            $number++;
            $chapters[] = $this->chapter($chapter, $story, $number, $firstName);
        }

        return View::make('book.classic.layout', [
            'title' => $this->title($book),
            'subtitle' => $this->subtitle($book),
            'brandName' => Brand::nameSafe(),
            'css' => $this->css($book->project->book_cover),
            'pagedJs' => $this->pagedJs(),
            'foreword' => $this->paragraphs((string) $book->foreword) ?: null,
            'chapters' => $chapters,
            'colophon' => $this->colophon($book, $firstName),
        ])->render();
    }

    /**
     * @return array<string, mixed>
     */
    private function chapter(BookChapter $chapter, Story $story, int $number, string $firstName): array
    {
        $token = $chapter->qrToken;

        return [
            'number' => $number,
            'title' => $story->title ?? __('book.untitled'),
            'question' => $story->questionText(),
            'date' => $story->recorded_at?->translatedFormat('F Y'),
            'paragraphs' => $this->paragraphs(SelectBookChapters::textOf($story)),
            'photos' => $this->photos($story),
            // Pas de QR tant que le jeton n'est pas émis : un carré qui mène
            // à une page morte est pire qu'un chapitre sans QR.
            // Le code se **redérive** ; la base n'en garde que l'empreinte,
            // et il doit être le même à chaque regénération (IssueQrToken).
            'qr' => $token === null ? null : QrImage::svg(Links::qr(IssueQrToken::plainFor($chapter))),
            'qrLegend' => __('book.qr_legend', ['first_name' => $firstName]),
        ];
    }

    /**
     * Le texte, découpé en paragraphes.
     *
     * Une transcription arrive en un bloc ou avec des retours simples selon
     * le rendu ; imprimer un pavé de six mille signes serait illisible.
     *
     * @return list<string>
     */
    private function paragraphs(string $text): array
    {
        $parts = preg_split('/\R{1,}/u', trim($text)) ?: [];

        return array_values(array_filter(
            array_map(static fn (string $line): string => trim($line), $parts),
            static fn (string $line): bool => $line !== '',
        ));
    }

    /**
     * Les photos, en URI de données.
     *
     * L'**original** et non une conversion : c'est lui qui part à
     * l'imprimeur, et une image web réduite à 1600 pixels ressortirait floue
     * sur du papier.
     *
     * @return list<array{data: string, alt: string, caption: string|null}>
     */
    private function photos(Story $story): array
    {
        $photos = [];

        foreach ($story->getMedia(Story::PHOTOS) as $photo) {
            $bytes = $this->bytesOf($photo);

            if ($bytes === null) {
                continue;
            }

            $photos[] = [
                'data' => 'data:'.$photo->mime_type.';base64,'.base64_encode($bytes),
                'alt' => (string) ($photo->getCustomProperty('caption') ?? ''),
                'caption' => $photo->getCustomProperty('caption'),
            ];
        }

        return $photos;
    }

    private function bytesOf(Media $photo): ?string
    {
        try {
            $stream = $photo->stream();
            $bytes = stream_get_contents($stream);

            return $bytes === false ? null : $bytes;
        } catch (\Throwable) {
            // Une photo illisible ne fait pas échouer un livre de soixante
            // pages : le chapitre sort sans elle, et le BAT est justement là
            // pour qu'un humain s'en aperçoive.
            return null;
        }
    }

    /**
     * Le colophon : ce que le livre promet, et pour combien de temps.
     *
     * @return list<string>
     */
    private function colophon(Book $book, string $firstName): array
    {
        $years = app(PilotSettings::class)->qr_commitment_years;
        $start = $book->project->collection_started_at ?? $book->created_at;

        return [
            __('book.colophon.rendering', ['first_name' => $firstName]),
            __('book.colophon.qr_commitment', [
                'date' => $start?->copy()->addYears($years)->translatedFormat('j F Y') ?? '—',
            ]),
            __('book.colophon.brand', [
                'brand' => Brand::nameSafe(),
                'domain' => Brand::linksDomain(),
            ]),
        ];
    }

    /**
     * Le titre de couverture.
     *
     * Le prénom du narrateur, seul, tant que la famille n'en a pas choisi un
     * autre : « Les histoires de Marcel » serait un titre que nous aurions
     * écrit à leur place, et un livre de famille n'appartient pas à son
     * éditeur. Le nom de marque n'est le titre que faute de mieux.
     */
    /**
     * Le titre de la couverture, composé depuis la formule choisie à l'achat.
     *
     * Composé ici et pas enregistré au tunnel : le prénom peut avoir été
     * corrigé entre-temps — une coquille trouvée le premier mois —, et un
     * titre figé garderait la coquille jusqu'à l'impression.
     *
     * Sans prénom, la marque : une couverture doit porter quelque chose.
     */
    private function title(Book $book): string
    {
        $project = $book->project;
        // Sans `??` : l'analyse statique tient la relation pour non nulle —
        // un projet a un narrateur principal — et signalerait le `?->` comme
        // inutile. Il ne l'est pas : un projet peut n'en avoir aucun le temps
        // d'un test ou d'une reprise, et le rendu ne doit pas s'y arrêter.
        $firstName = trim((string) $project->primaryNarrator?->first_name);

        if ($firstName === '') {
            return Brand::nameSafe();
        }

        return $project->book_title->compose(
            $firstName,
            $project->book_title_custom,
            $project->locale->language(),
        );
    }

    /**
     * Le sous-titre : la période de collecte, pas le prénom.
     *
     * Il portait le prénom, comme le titre — la couverture disait donc
     * « Marie » deux fois, ce qu'un rendu réel a montré et qu'aucun test
     * n'aurait vu (T-197). La période, elle, est ce qu'on cherche sur un
     * livre de famille trente ans plus tard.
     */
    private function subtitle(Book $book): ?string
    {
        $start = $book->project->collection_started_at;

        if ($start === null) {
            return null;
        }

        return __('book.collected', ['year' => $start->format('Y')]);
    }

    /** Le CSS du gabarit, polices incrustées et couverture teintée. */
    private function css(BookCover $cover): string
    {
        $css = (string) file_get_contents(resource_path('css/book-classic.css'));
        $faces = '';

        foreach (self::FONTS as $family => $relative) {
            $path = public_path($relative);

            if (! is_file($path)) {
                continue;
            }

            $faces .= sprintf(
                "@font-face{font-family:'%s';src:url(data:font/woff2;base64,%s) format('woff2');font-weight:100 900;font-style:normal;font-display:block}\n",
                $family,
                base64_encode((string) file_get_contents($path)),
            );
        }

        /*
         * La teinte choisie à l'achat, des mois plus tôt (T-241). Posée en
         * variables plutôt qu'en remplaçant les règles : la feuille reste
         * lisible et modifiable par un typographe, et le jour où une seconde
         * maquette arrivera, elle lira les mêmes trois variables.
         */
        $tint = sprintf(
            ":root{--cover-background:%s;--cover-ink:%s;--cover-muted-ink:%s}\n",
            $cover->background(),
            $cover->ink(),
            $cover->mutedInk(),
        );

        // Les `@font-face` en tête : une police déclarée après son usage
        // n'est pas appliquée au premier rendu, et Paged.py pagine sur ce
        // premier rendu — le livre sortirait composé dans la police de repli.
        return $faces.$tint.$css;
    }

    /** Paged.js, lu dans `node_modules` plutôt que servi par une URL. */
    private function pagedJs(): string
    {
        $path = base_path('node_modules/pagedjs/dist/paged.polyfill.js');

        return is_file($path) ? (string) file_get_contents($path) : '';
    }
}
