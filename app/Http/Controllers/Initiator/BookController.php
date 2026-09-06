<?php

declare(strict_types=1);

namespace App\Http\Controllers\Initiator;

use App\Books\ApproveBookProof;
use App\Books\BookLexiconCheck;
use App\Books\ComputeBookReadiness;
use App\Books\OrderExtraCopies;
use App\Books\SelectBookChapters;
use App\Books\SelectedChaptersPresenter;
use App\Enums\BookStatus;
use App\Exceptions\Domain\ProofNotApprovable;
use App\Jobs\RenderBookPdf;
use App\Models\Book;
use App\Models\Project;
use App\Services\Storage\MediaStorage;
use App\Support\Brand;
use App\Support\InitiatorProject;
use App\Support\Options;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Le livre, vu par l'Initiateur·rice.
 *
 * Ce que cette page doit faire comprendre, avant tout bouton : **le livre se
 * déclenche quand la matière suffit, pas à un nombre d'histoires** (R-6). La
 * jauge montre donc quatre mesures et non un pourcentage unique — et le seuil
 * qui manque le plus souvent n'est pas celui des mots.
 *
 * L'éditeur désigné a les mêmes droits qu'elle sur cette page : c'est souvent
 * lui qui relit les noms propres, et lui refuser la génération du BAT
 * obligerait à se passer le mot de passe.
 */
final readonly class BookController
{
    public function __construct(
        private ComputeBookReadiness $readiness,
        private SelectBookChapters $chapters,
        private BookLexiconCheck $lexicon,
        private ApproveBookProof $approval,
    ) {}

    public function index(Request $request, MediaStorage $storage): Response
    {
        $project = $this->project($request);
        $book = $this->book($project);

        $this->chapters->handle($book);

        $measured = $this->readiness->handle($project);

        return inertia('initiator/Book', [
            'narratorFirstName' => $project->primaryNarrator?->first_name ?: '',
            'gauge' => [
                'words' => $measured->words,
                'minWords' => (int) config('product.book_ready.min_words'),
                'audioMinutes' => (int) round($measured->audioMinutes),
                'minAudioMinutes' => (int) config('product.book_ready.min_audio_minutes'),
                'pages' => $measured->estimatedPages,
                'minPages' => (int) config('product.book_ready.min_pages'),
                'themes' => $measured->themes,
                'minThemes' => (int) config('product.book_ready.min_themes'),
                'ready' => $measured->isReady(),
            ],
            'book' => [
                'id' => $book->getKey(),
                'status' => $book->status->value,
                'statusLabel' => Options::label($book->status),
                'format' => $book->format->value,
                'formatLabel' => Options::label($book->format),
                'proposedFormat' => $book->proposed_format?->value,
                'foreword' => $book->foreword,
                'pageCount' => $book->page_count_estimate,
                'proofVersion' => $book->proof_version,
                'proofGeneratedAt' => $book->proof_generated_at?->toIso8601String(),
                'proofUrl' => $book->proof_pdf_path === null
                    ? null
                    : $storage->temporaryUrl($book->proof_pdf_path, 60),
                'approvedAt' => $book->proof_approved_at?->toIso8601String(),
                'orderedAt' => $book->ordered_at?->toIso8601String(),
                'printedAt' => $book->printed_at?->toIso8601String(),
                'deliveredAt' => $book->delivered_at?->toIso8601String(),
                'editable' => $book->isEditable(),
            ],
            'chapters' => SelectedChaptersPresenter::forBook($book),
            // Les noms que la transcription a peut-être écorchés. Ce n'est pas
            // une correction automatique : c'est la famille qui décide de la
            // graphie de ses noms.
            'lexicon' => $this->lexicon->handle($book),
            'extraCopyPriceCents' => (int) config('product.pilot.extra_copy_price_cents'),
            // Le signalement d'un défaut passe par le support : la
            // réimpression est gratuite et sans condition (doc 04 §10), et
            // un formulaire de plus ferait croire à une instruction de
            // dossier là où il n'y a qu'un livre abîmé à remplacer.
            'supportEmail' => Brand::supportEmail(),
        ]);
    }

    /** Inclure ou exclure un chapitre, et réordonner. */
    public function update(Request $request): RedirectResponse
    {
        $book = $this->book($this->project($request));

        abort_unless($book->isEditable(), 403);

        $validated = $request->validate([
            'chapters' => ['required', 'array'],
            'chapters.*.id' => ['required', 'integer'],
            'chapters.*.included' => ['required', 'boolean'],
            'foreword' => ['nullable', 'string', 'max:1500'],
        ]);

        foreach ($validated['chapters'] as $position => $row) {
            $book->chapters()
                ->whereKey($row['id'])
                ->update([
                    'included' => (bool) $row['included'],
                    // La position vient du **rang dans la liste envoyée** :
                    // le glisser-déposer réordonne un tableau, il ne calcule
                    // pas des numéros.
                    'position' => ($position + 1) * 10,
                ]);
        }

        $book->forceFill(['foreword' => $validated['foreword'] ?? null])->save();

        return back()->with('status', __('initiator.book.saved'));
    }

    /** Lancer la fabrication du bon à tirer. */
    public function render(Request $request): RedirectResponse
    {
        $book = $this->book($this->project($request));

        abort_unless($book->isEditable(), 403);

        if ($book->chapters()->where('included', true)->doesntExist()) {
            return back()->withErrors(['chapters' => __('initiator.book.no_chapter')]);
        }

        RenderBookPdf::dispatch($book);

        return back()->with('status', __('initiator.book.rendering'));
    }

    /** Approuver et commander. */
    public function approve(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_if($user === null, 403);

        $book = $this->book($this->project($request));

        $validated = $request->validate([
            'final_print' => ['accepted'],
            'lexicon_reviewed' => ['accepted'],
        ]);

        try {
            $this->approval->handle($book, $user, true, true);
        } catch (ProofNotApprovable $exception) {
            return back()->withErrors(['final_print' => $exception->getMessage()]);
        }

        return back()->with('status', __('initiator.book.ordered'));
    }

    /**
     * Commander des exemplaires supplémentaires.
     *
     * `Inertia::location` et non une redirection ordinaire : Inertia ne sait
     * pas suivre un `302` vers un domaine externe, et le bouton paraîtrait
     * mort. C'est T-168, appliquée avant de la réapprendre.
     */
    public function extraCopies(Request $request, OrderExtraCopies $copies): SymfonyResponse|RedirectResponse
    {
        $book = $this->book($this->project($request));

        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:'.OrderExtraCopies::MAX_AT_ONCE],
        ]);

        try {
            $session = $copies->handle($book, (int) $validated['quantity']);
        } catch (RuntimeException $exception) {
            return back()->withErrors(['quantity' => $exception->getMessage()]);
        }

        return Inertia::location($session->url);
    }

    private function project(Request $request): Project
    {
        $user = $request->user();
        abort_if($user === null, 403);

        return InitiatorProject::forOrFail($user);
    }

    /**
     * Le livre du projet, créé au besoin.
     *
     * `books.project_id` est unique : une famille n'a pas deux livres en
     * cours, et une réimpression est un état du même livre.
     */
    private function book(Project $project): Book
    {
        $book = Book::query()->firstOrNew(['project_id' => $project->getKey()]);

        if (! $book->exists) {
            $book->project()->associate($project);
            $book->status = BookStatus::Draft;
            $book->save();
        }

        return $book;
    }
}
