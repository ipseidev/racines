<?php

declare(strict_types=1);

namespace App\Http\Requests\Narrator;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Ouverture d'un envoi audio.
 *
 * Le type et la taille annoncés sont bornés dès l'ouverture : refuser ici
 * coûte une requête, refuser après vingt minutes d'envoi coûte l'histoire.
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
            'mime' => ['required', 'string', Rule::in((array) config('product.recording.accepted_mimes'))],
            'expected_bytes' => ['required', 'integer', 'min:1', 'max:'.config('product.recording.max_bytes')],
            'device_info' => ['sometimes', 'array'],
            'device_info.platform' => ['sometimes', 'string', 'max:32'],
            'device_info.browser' => ['sometimes', 'string', 'max:32'],
            'device_info.version' => ['sometimes', 'string', 'max:32'],
            'device_info.user_agent' => ['sometimes', 'string', 'max:512'],
        ];
    }
}
