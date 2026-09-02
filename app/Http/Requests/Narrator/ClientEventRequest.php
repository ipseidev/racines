<?php

declare(strict_types=1);

namespace App\Http\Requests\Narrator;

use App\Enums\ClientEventName;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Ce que le navigateur rapporte.
 *
 * La liste d'événements est fermée et le payload borné : ce point d'entrée est
 * ouvert à quiconque détient un lien, il ne doit pas devenir un dépotoir.
 */
final class ClientEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'event' => ['required', 'string', Rule::enum(ClientEventName::class)],
            'payload' => ['sometimes', 'array'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $payload = $this->input('payload');

            if (is_array($payload) && strlen((string) json_encode($payload)) > 2048) {
                $validator->errors()->add('payload', __('validation.max.string', ['attribute' => 'payload', 'max' => 2048]));
            }
        });
    }
}
