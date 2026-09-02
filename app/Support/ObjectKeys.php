<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Recording;

/**
 * Chemins des objets stockés.
 *
 * Un chemin est une donnée qui circule — dans les journaux, dans les URL
 * présignées, dans les consoles du fournisseur. Il ne contient donc que des
 * identifiants opaques : ni nom, ni numéro d'ordre, ni coordonnée
 * (doc 04 §12).
 *
 * Les segments sont numérotés sur deux chiffres pour que l'ordre alphabétique
 * des clés soit l'ordre chronologique : c'est ce que lit `ffmpeg` au moment de
 * les concaténer.
 */
final class ObjectKeys
{
    public static function recordingPrefix(Recording $recording): string
    {
        $story = $recording->story;

        return "projects/{$story->project_id}/stories/{$story->id}/recordings/{$recording->id}";
    }

    public static function recordingSegment(Recording $recording, int $number, string $extension): string
    {
        $padded = str_pad((string) $number, 2, '0', STR_PAD_LEFT);

        return self::recordingPrefix($recording)."/segment-{$padded}.{$extension}";
    }

    public static function recordingOriginal(Recording $recording, string $extension): string
    {
        return self::recordingPrefix($recording)."/original.{$extension}";
    }

    public static function recordingDerivative(Recording $recording, string $extension): string
    {
        return self::recordingPrefix($recording)."/derived.{$extension}";
    }

    /**
     * Extension déduite du type MIME déclaré par le navigateur.
     *
     * On ne fait pas confiance au nom de fichier — il n'y en a pas — et on ne
     * devine rien : un type inconnu tombe sur `bin`, que le bloc 06 refusera
     * de transcoder plutôt que de traiter à l'aveugle.
     */
    public static function extensionForMime(string $mime): string
    {
        $base = mb_strtolower(trim(explode(';', $mime)[0]));

        return match ($base) {
            'audio/webm' => 'webm',
            'audio/mp4', 'audio/x-m4a' => 'm4a',
            'audio/ogg' => 'ogg',
            'audio/mpeg' => 'mp3',
            'audio/wav', 'audio/x-wav' => 'wav',
            default => 'bin',
        };
    }
}
