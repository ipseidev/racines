<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\DeleteStoryAction;
use App\Enums\DeletionRequestedBy;
use App\Models\Story;
use App\States\Story\Trashed;
use Illuminate\Console\Command;

/**
 * Vide la corbeille passé le délai de rétractation.
 *
 * La promesse est de trente jours : au trente-et-unième, la suppression a
 * lieu, sans nouvelle demande et sans notification — le narrateur a déjà
 * décidé, le lui redemander serait le faire revivre son deuil.
 *
 * `deletion_requested_by` garde `narrator` : c'est bien lui qui l'a demandé,
 * la commande ne fait qu'honorer le délai.
 */
final class PurgeTrashedStories extends Command
{
    protected $signature = 'stories:purge-trashed';

    protected $description = 'Supprime les histoires en corbeille depuis plus de trente jours';

    public function handle(DeleteStoryAction $delete): int
    {
        $days = (int) config('product.stories.trash_retention_days');
        $cutoff = now()->subDays($days);
        $purged = 0;

        Story::query()
            ->where('state', (new Trashed(new Story))->getValue())
            ->whereNotNull('trashed_at')
            ->where('trashed_at', '<=', $cutoff)
            ->cursor()
            ->each(function (Story $story) use ($delete, &$purged): void {
                $delete->handle($story, $story->deletion_requested_by ?? DeletionRequestedBy::Narrator);
                $purged++;
            });

        $this->components->info(sprintf(
            '%d histoire(s) supprimée(s) après %d jours en corbeille.',
            $purged,
            $days,
        ));

        return self::SUCCESS;
    }
}
