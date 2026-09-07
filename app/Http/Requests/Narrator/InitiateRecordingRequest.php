<?php

declare(strict_types=1);

namespace App\Http\Requests\Narrator;

use App\Enums\RecordingKind;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Ouverture d'un envoi, voix ou vidéo.
 *
 * Le type et la taille annoncés sont bornés dès l'ouverture : refuser ici
 * coûte une requête, refuser après vingt minutes d'envoi coûte l'histoire.
 *
 * La nature de l'envoi n'est pas déclarée par le client : elle se déduit du
 * conteneur (`RecordingKind::fromMime`), et c'est elle qui choisit la borne
 * de poids. Un navigateur qui annoncerait « audio » avec un conteneur vidéo
 * ne gagnerait donc pas la borne du son.
 */
final class InitiateRecordingRequest extends FormRequest
{
    public function authorize(): bool
    {
        // L'autorisation est portée par le jeton : `resolve.token:record` a
        // déjà refusé tout ce qui n'est pas un lien d'enregistrement valable.
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'mime' => ['required', 'string', Rule::in(self::everyAcceptedMime())],
            'expected_bytes' => ['required', 'integer', 'min:1', 'max:'.$this->kind()->maxBytes()],
            'device_info' => ['sometimes', 'array'],
            'device_info.platform' => ['sometimes', 'string', 'max:32'],
            'device_info.browser' => ['sometimes', 'string', 'max:32'],
            'device_info.version' => ['sometimes', 'string', 'max:32'],
            'device_info.user_agent' => ['sometimes', 'string', 'max:512'],
        ];
    }

    public function kind(): RecordingKind
    {
        $mime = $this->input('mime');

        return RecordingKind::fromMime(is_string($mime) ? $mime : '');
    }

    /** @return list<string> */
    private static function everyAcceptedMime(): array
    {
        return [
            ...RecordingKind::Audio->acceptedMimes(),
            ...RecordingKind::Video->acceptedMimes(),
        ];
    }
}
