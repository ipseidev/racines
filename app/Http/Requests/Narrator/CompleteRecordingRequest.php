<?php

declare(strict_types=1);

namespace App\Http\Requests\Narrator;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Conclusion d'un envoi audio : les segments, leurs parts et leurs ETags.
 */
final class CompleteRecordingRequest extends FormRequest
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
            'segments' => ['required', 'array', 'min:1', 'max:200'],
            'segments.*.number' => ['required', 'integer', 'min:1', 'max:200'],
            'segments.*.parts' => ['required', 'array', 'min:1', 'max:2000'],
            'segments.*.parts.*.number' => ['required', 'integer', 'min:1', 'max:2000'],
            'segments.*.parts.*.etag' => ['required', 'string', 'max:128'],
            'client_duration_seconds' => ['sometimes', 'numeric', 'min:0', 'max:'.((int) config('product.recording.hard_stop_seconds') + 60)],
        ];
    }

    /**
     * @return list<array{number: int, parts: list<array{number: int, etag: string}>}>
     */
    public function segments(): array
    {
        /** @var list<array{number: mixed, parts: list<array{number: mixed, etag: mixed}>}> $segments */
        $segments = $this->validated('segments');

        return array_map(
            fn (array $segment): array => [
                'number' => (int) $segment['number'],
                'parts' => array_map(
                    fn (array $part): array => ['number' => (int) $part['number'], 'etag' => (string) $part['etag']],
                    $segment['parts'],
                ),
            ],
            $segments,
        );
    }
}
