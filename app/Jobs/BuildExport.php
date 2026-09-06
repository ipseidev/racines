<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\TokenType;
use App\Exports\ExportBuilder;
use App\Models\Export;
use App\Notifications\ExportReadyNotification;
use App\Services\Storage\MediaStorage;
use App\Services\Tokens\TokenService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Fabriquer l'archive d'un export, la ranger, et prévenir.
 *
 * En file, avec une heure de délai : cinq gigaoctets d'audio se téléchargent,
 * se compressent et se reversent, et le dossier promet moins de trente
 * minutes pour cette taille (doc 04 §7). Le faire dans la requête ferait
 * expirer la page au moment où la famille attend le plus.
 *
 * L'ordre est celui de toutes les chaînes de ce dépôt : **on ne prévient
 * qu'une fois le fichier en place**. Un courriel « vos données sont prêtes »
 * qui mène à un lien mort est la manière la plus sûre de faire douter
 * quelqu'un de tout le reste.
 */
final class BuildExport implements ShouldQueue
{
    use Queueable;

    public int $timeout = 3600;

    public int $tries = 2;

    public function __construct(private readonly Export $export)
    {
        $this->onQueue('exports');
    }

    public function handle(ExportBuilder $builder, MediaStorage $storage, TokenService $tokens): void
    {
        $export = $this->export->fresh();

        if ($export === null || $export->status === 'ready') {
            return;
        }

        $export->forceFill(['status' => 'building'])->save();

        try {
            ['path' => $path, 'manifest' => $manifest] = $builder->build($export);

            $cle = sprintf('exports/%s/export.zip', $export->getKey());
            $storage->putFile($cle, $path, 'application/zip');
            $bytes = (int) filesize($path);

            $export->forceFill([
                'object_path' => $cle,
                'bytes' => $bytes,
                'manifest' => $manifest,
                'status' => 'ready',
                'built_at' => now(),
                'expires_at' => now()->addDays(Export::DAYS),
            ])->save();
        } catch (Throwable $exception) {
            $export->forceFill([
                'status' => 'failed',
                'failure_reason' => mb_substr($exception->getMessage(), 0, 500),
            ])->save();

            Log::error('export.failed', [
                'export_id' => $export->getKey(),
                'reason' => $exception->getMessage(),
            ]);

            throw $exception;
        } finally {
            // Le fichier temporaire pèse le poids de l'archive : le laisser
            // traîner remplirait le disque au troisième export.
            File::delete($path ?? '');
        }

        $this->tell($export, $tokens);

        Log::info('export.ready', [
            'export_id' => $export->getKey(),
            'bytes' => $bytes,
        ]);
    }

    /**
     * Le lien, émis maintenant et jamais relu.
     *
     * Le jeton en clair n'existe qu'entre son émission et son envoi : la base
     * n'en garde que l'empreinte (invariant du bloc 03). C'est aussi pourquoi
     * un export ne se « re-notifie » pas — on en refabrique un.
     */
    private function tell(Export $export, TokenService $tokens): void
    {
        $demandeur = $export->requestedBy;

        // Un export proactif n'a pas de demandeur : il s'adresse à
        // l'Initiateur·rice, qui a acheté et qui recevra le livre.
        $destinataire = $demandeur instanceof Model && method_exists($demandeur, 'notify')
            ? $demandeur
            : $export->project->owner;

        $issued = $tokens->issue(
            TokenType::Export,
            $export,
            ['download'],
            $export->expires_at,
            issuedTo: $destinataire,
        );

        $destinataire->notify(new ExportReadyNotification($export, $issued->plain));
    }
}
