<?php

declare(strict_types=1);

namespace App\Http\Controllers\Exports;

use App\Audit\AuditLog;
use App\Models\Export;
use App\Services\Storage\MediaStorage;
use App\Support\Brand;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

/**
 * Télécharger un export.
 *
 * Une **redirection** vers une URL temporaire du stockage, jamais un flux qui
 * traverse l'application : cinq gigaoctets qui passent par PHP occupent un
 * processus pendant vingt minutes, et trois familles simultanées suffiraient
 * à faire tomber le site. Le stockage sait faire cela mieux que nous.
 *
 * L'URL temporaire vaut **quinze minutes**. Assez pour commencer un
 * téléchargement, trop peu pour qu'elle traîne utilement dans un historique —
 * et le jeton d'export, lui, reste valable sept jours pour en redemander une.
 *
 * Chaque téléchargement est **journalisé**. Ce n'est pas de la surveillance :
 * c'est ce qui permet de répondre à « ai-je bien reçu mes données ? », et
 * c'est une obligation quand l'export répond à une demande de droit d'accès.
 */
final readonly class DownloadExportController
{
    public function __invoke(Request $request, string $token, MediaStorage $storage): RedirectResponse|Response
    {
        $export = $request->attributes->get('token_subject');

        if (! $export instanceof Export) {
            abort(404);
        }

        if (! $export->isReady()) {
            /*
             * Un export expiré n'est pas une erreur : c'est le
             * fonctionnement annoncé. La page le dit et rappelle qu'un
             * nouveau se demande gratuitement — sans quoi la personne
             * conclurait qu'elle a perdu ses données.
             */
            return inertia('exports/Expired', [
                'brandName' => Brand::nameSafe(),
                'status' => $export->status,
            ]);
        }

        $export->forceFill([
            'downloaded_at' => now(),
            'download_count' => $export->download_count + 1,
        ])->save();

        AuditLog::record('downloaded Export', $export, [
            'kind' => $export->kind->value,
            'bytes' => $export->bytes,
            'count' => $export->download_count,
        ], $export->project);

        return redirect()->away($storage->temporaryUrl((string) $export->object_path, 15));
    }
}
