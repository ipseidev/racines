<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Export;
use App\Services\Storage\MediaStorage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Effacer les archives d'export périmées.
 *
 * Le lien dure sept jours ; **l'objet ne doit pas durer davantage**. Une
 * archive contenant l'intégralité des récits d'une famille, oubliée sur un
 * stockage objet, est une fuite qui attend son heure — et une facture qui
 * grossit à chaque demande.
 *
 * La ligne reste, sans son objet : elle dit qu'un export a existé, qui l'a
 * demandé et quand il a été téléchargé. C'est ce qui permet de répondre à
 * « m'avez-vous bien envoyé mes données ? » six mois plus tard.
 */
final class ExpireExports extends Command
{
    protected $signature = 'exports:expire';

    protected $description = 'Supprime les archives d’export dont le lien a expiré';

    public function handle(MediaStorage $storage): int
    {
        $perimes = Export::query()
            ->whereNotNull('object_path')
            ->where('expires_at', '<', now())
            ->get();

        $effaces = 0;

        foreach ($perimes as $export) {
            try {
                $storage->delete((string) $export->object_path);
            } catch (Throwable $exception) {
                // Un objet déjà absent n'est pas une erreur : on passe la
                // ligne en expiré quand même, sinon la commande le retenterait
                // chaque jour jusqu'à la fin des temps.
                Log::info('export.delete_skipped', [
                    'export_id' => $export->getKey(),
                    'reason' => $exception->getMessage(),
                ]);
            }

            $export->forceFill(['status' => 'expired', 'object_path' => null])->save();
            $effaces++;
        }

        $this->components->info(sprintf('%d archive(s) d’export supprimée(s).', $effaces));

        return self::SUCCESS;
    }
}
