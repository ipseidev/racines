<?php

declare(strict_types=1);

namespace App\Http\Controllers\Narrator;

use App\Actions\AbortRecording;
use App\Actions\CompleteRecording;
use App\Actions\InitiateRecording;
use App\Actions\OpenRecordingSegment;
use App\Http\Requests\Narrator\CompleteRecordingRequest;
use App\Http\Requests\Narrator\InitiateRecordingRequest;
use App\Models\Recording;
use App\Models\Story;
use App\Services\Storage\MediaStorage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Envoi de l'audio, par segment, en plusieurs parts et par URL présignée.
 *
 * Le fichier ne passe jamais par le serveur : le navigateur dépose ses parts
 * directement sur le stockage. C'est ce qui rend l'envoi reprenable depuis un
 * téléphone en 4G, et ce qui évite d'occuper un worker pendant vingt minutes
 * d'audio.
 *
 * Un segment par continuité de flux. Le navigateur en ouvre un nouveau quand
 * un appel entrant ou une veille coupe l'enregistrement : les parts déjà
 * déposées restent valables, seule la couture est à faire côté serveur.
 */
final readonly class RecordingUploadController
{
    /** Bornes de R2 et de S3 : 10 000 parts. On s'arrête bien avant. */
    private const MAX_PARTS = 2000;

    public function __construct(
        private MediaStorage $storage,
        private InitiateRecording $initiate,
        private OpenRecordingSegment $openSegment,
        private CompleteRecording $complete,
        private AbortRecording $abort,
    ) {}

    public function initiate(InitiateRecordingRequest $request): JsonResponse
    {
        $story = self::storyFor($request);

        /** @var array<string, mixed> $deviceInfo */
        $deviceInfo = $request->validated('device_info', []);

        $recording = $this->initiate->handle(
            $story,
            (string) $request->validated('mime'),
            $deviceInfo,
        );

        return response()->json([
            'recording_id' => $recording->id,
            'segments' => $recording->segments ?? [],
            'part_size_bytes' => (int) config('product.recording.upload_part_bytes'),
            'max_parts' => self::MAX_PARTS,
        ], 201);
    }

    /**
     * Ouvre un segment supplémentaire, après une interruption.
     */
    public function openSegment(Request $request, string $token, Recording $recording): JsonResponse
    {
        self::guardOwnership($request, $recording);

        return response()->json($this->openSegment->handle($recording), 201);
    }

    public function sign(Request $request, string $token, Recording $recording, int $segment, int $part): JsonResponse
    {
        self::guardOwnership($request, $recording);

        abort_if($part < 1 || $part > self::MAX_PARTS, 404);

        $declared = collect($recording->segments ?? [])->firstWhere('number', $segment);

        abort_unless(is_array($declared) && is_string($declared['key'] ?? null) && is_string($declared['upload_id'] ?? null), 404);

        return response()->json([
            'url' => $this->storage->presignPart($declared['key'], $declared['upload_id'], $part),
            'expires_in_minutes' => 15,
        ]);
    }

    public function complete(CompleteRecordingRequest $request, string $token, Recording $recording): JsonResponse
    {
        self::guardOwnership($request, $recording);

        $clientDuration = $request->validated('client_duration_seconds');

        $confirmed = $this->complete->handle(
            $recording,
            $request->segments(),
            is_numeric($clientDuration) ? (float) $clientDuration : null,
        );

        return response()->json(['confirmed' => $confirmed], $confirmed ? 200 : 422);
    }

    public function abort(Request $request, string $token, Recording $recording): JsonResponse
    {
        self::guardOwnership($request, $recording);

        $this->abort->handle($recording);

        return response()->json(['aborted' => true]);
    }

    private static function storyFor(Request $request): Story
    {
        $subject = $request->attributes->get('token_subject');

        abort_unless($subject instanceof Story, 404);

        return $subject;
    }

    /**
     * Un jeton ne vaut que pour son histoire : un enregistrement d'une autre
     * histoire est introuvable, pas interdit — on ne confirme pas son
     * existence à quelqu'un qui n'y a pas droit.
     */
    private static function guardOwnership(Request $request, Recording $recording): void
    {
        abort_unless($recording->story_id === self::storyFor($request)->id, 404);
    }
}
