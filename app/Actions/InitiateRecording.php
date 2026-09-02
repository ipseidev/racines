<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Recording;
use App\Models\Story;
use App\Support\ObjectKeys;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Ouvre un enregistrement et son premier segment.
 *
 * Un narrateur qui recommence n'écrase rien : le précédent enregistrement
 * cesse d'être courant et reste en base, confirmé, avec son objet. « L'audio
 * source est sacré » vaut aussi contre le narrateur lui-même — c'est le
 * partage qui se retire, pas l'enregistrement qui s'efface.
 */
final readonly class InitiateRecording
{
    public function __construct(private OpenRecordingSegment $openSegment) {}

    /**
     * @param  array<string, mixed>  $deviceInfo
     */
    public function handle(Story $story, string $mime, array $deviceInfo = []): Recording
    {
        return DB::transaction(function () use ($story, $mime, $deviceInfo): Recording {
            $story->recordings()->current()->update(['is_current' => false]);

            $recording = new Recording([
                'original_mime' => $mime,
                'device_info' => $deviceInfo === [] ? null : $deviceInfo,
                'segments' => [],
            ]);

            // L'identifiant est tiré avant l'insertion : les clés d'objets en
            // dépendent, et on ne veut pas d'un aller-retour supplémentaire.
            $recording->id = (string) Str::uuid7();
            $recording->story()->associate($story);
            $recording->save();

            $this->openSegment->handle($recording);

            return $recording->refresh();
        });
    }

    public function extensionFor(string $mime): string
    {
        return ObjectKeys::extensionForMime($mime);
    }
}
