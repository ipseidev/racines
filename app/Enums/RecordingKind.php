<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasTranslatedLabel;

/**
 * Ce que le narrateur a capté : sa voix seule, ou sa voix et son visage.
 *
 * Le choix se fait avant la demande d'autorisation, parce que ce ne sont pas
 * les mêmes permissions et que faire surgir la caméra sur quelqu'un qui
 * pensait parler serait une trahison.
 *
 * La vidéo est **additive** : sa piste sonore produit le même dérivé MP3 que
 * la voix seule, et c'est lui qui sert la transcription, le QR du livre et
 * l'écoute d'un proche qui n'a pas envie de regarder.
 */
enum RecordingKind: string
{
    use HasTranslatedLabel;

    case Audio = 'audio';
    case Video = 'video';

    /**
     * Déduit du type MIME annoncé par le navigateur.
     *
     * C'est le serveur qui décide, jamais le client : le navigateur envoie
     * son conteneur, on en tire la nature. Un client qui annoncerait
     * « audio » avec un conteneur vidéo ne gagnerait rien.
     */
    public static function fromMime(string $mime): self
    {
        $base = mb_strtolower(trim(explode(';', $mime)[0]));

        return str_starts_with($base, 'video/') ? self::Video : self::Audio;
    }

    public function isVideo(): bool
    {
        return $this === self::Video;
    }

    /**
     * Les conteneurs qu'on accepte pour cette forme.
     *
     * @return list<string>
     */
    public function acceptedMimes(): array
    {
        $key = $this->isVideo()
            ? 'product.recording.video.accepted_mimes'
            : 'product.recording.accepted_mimes';

        return array_values(array_filter(
            (array) config($key),
            static fn (mixed $mime): bool => is_string($mime),
        ));
    }

    /**
     * Le poids maximal accepté. Une vidéo pèse dix fois une voix : lui
     * appliquer la borne du son ferait refuser tout récit de plus de trois
     * minutes, après l'envoi.
     */
    public function maxBytes(): int
    {
        return (int) config(
            $this->isVideo() ? 'product.recording.video.max_bytes' : 'product.recording.max_bytes',
        );
    }
}
