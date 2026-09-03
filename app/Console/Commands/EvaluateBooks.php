<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Books\ComputeBookReadiness;
use App\Books\ProposeBookFormat;
use App\Enums\ProjectStatus;
use App\Models\Book;
use App\Models\Project;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * La maturité des livres, et la sortie honorable, tous les jours.
 *
 * Trois choses, dans cet ordre d'importance.
 *
 * Le livre **naît** à la première histoire validée. Pas à un seuil : une
 * famille doit pouvoir voir sa jauge dès qu'elle a raconté quelque chose, et
 * un écran vide jusqu'à la vingt-cinquième histoire ferait croire qu'il ne se
 * passe rien.
 *
 * À M+12 sans bon à tirer, le format adapté est proposé et **une**
 * prolongation de trois mois est posée — une seule, incluse, la suivante se
 * vend (PRD §10). La donner en silence chaque jour reviendrait à ne jamais
 * finir.
 *
 * À M+15, le projet devient dormant avec un crédit d'impression de
 * vingt-quatre mois. Ce n'est pas un abandon : c'est une porte laissée
 * ouverte, et c'est ce qu'on a vendu.
 *
 * Ce que la commande ne fait jamais : toucher à un projet gelé par un deuil.
 * Un gel arrête **tout**, y compris les échéances qui produiraient un message
 * à une famille en deuil.
 */
final class EvaluateBooks extends Command
{
    protected $signature = 'books:evaluate';

    protected $description = 'Met à jour la maturité des livres et applique la sortie honorable';

    public function handle(ComputeBookReadiness $readiness): int
    {
        $projects = Project::query()
            // Ni annulés, ni achevés, ni gelés : le calendrier de la sortie
            // honorable ne concerne que les projets en cours.
            ->whereIn('status', [
                ProjectStatus::Active->value,
                ProjectStatus::Paused->value,
                ProjectStatus::Dormant->value,
            ])
            ->get();

        $touched = 0;

        foreach ($projects as $project) {
            $measured = $readiness->handle($project);

            if ($measured->chapters === 0) {
                // Rien de validé : pas de livre, et pas d'échéance.
                continue;
            }

            $book = Book::query()->firstOrNew(['project_id' => $project->id]);
            $book->project()->associate($project);

            $book->proposed_format = ProposeBookFormat::for($measured);
            $book->page_count_estimate = $measured->estimatedPages;

            if ($measured->isReady() && $book->book_ready_at === null) {
                // Un fait, pas un état : le repousser chaque jour rendrait
                // impossible de dire quand la famille est devenue prête.
                $book->book_ready_at = now();
            }

            if ($book->isEditable()) {
                // Le format retenu suit la proposition **tant que la
                // sélection est ouverte**. Après l'accord, la famille a vu un
                // format précis : le changer sous elle serait pire qu'inutile.
                $book->format = $book->proposed_format;
            }

            $this->applyHonourableExit($project, $book);

            $book->save();
            $touched++;
        }

        $this->components->info(sprintf('%d livre(s) évalué(s).', $touched));

        return self::SUCCESS;
    }

    /**
     * Les échéances M+12 et M+15 du PRD §10.
     *
     * Elles ne s'appliquent qu'aux projets qui n'ont **pas** abouti : rendre
     * dormant un projet dont le livre est parti à l'impression serait absurde.
     */
    private function applyHonourableExit(Project $project, Book $book): void
    {
        if ($book->status->isLocked()) {
            return;
        }

        $started = $project->collection_started_at;

        if ($started === null) {
            return;
        }

        $months = (int) config('product.offer.core_months');
        $dormantAfter = (int) config('product.offer.dormant_after_months');

        if ($started->addMonths($dormantAfter)->isPast()) {
            // Le crédit d'impression : vingt-quatre mois pour revenir.
            $book->print_credit_expires_at ??= now()->addMonths(24);

            if ($project->status !== ProjectStatus::Dormant) {
                $project->status = ProjectStatus::Dormant;
                $project->next_prompt_at = null;
                $project->save();

                Log::info('book.project_dormant', [
                    'project_id' => $project->id,
                    // Non nul : la ligne au-dessus vient de le poser.
                    'print_credit_expires_at' => $book->print_credit_expires_at->toIso8601String(),
                ]);
            }

            return;
        }

        if ($started->addMonths($months)->isPast() && $book->extension_granted_at === null) {
            $book->extension_granted_at = now();

            Log::info('book.extension_granted', [
                'project_id' => $project->id,
                'proposed_format' => $book->proposed_format?->value,
            ]);
        }
    }
}
