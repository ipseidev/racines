<?php

declare(strict_types=1);

namespace App\Http\Requests\Narrator;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Réponse écrite (P0-5).
 *
 * Le repli écrit n'est pas un lot de consolation : c'est ce qui permet à
 * quelqu'un dont le micro est refusé, ou qui préfère écrire, de participer
 * quand même. Une histoire écrite passe par la même machine d'états.
 */
final class WrittenAnswerRequest extends FormRequest
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
            'written_answer' => ['required', 'string', 'min:1', 'max:20000'],
        ];
    }
}
