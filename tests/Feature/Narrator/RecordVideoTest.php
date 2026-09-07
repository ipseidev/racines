<?php

declare(strict_types=1);

use App\Enums\AnswerType;
use App\Enums\RecordingKind;
use App\Enums\TokenType;
use App\Models\Recording;
use App\Models\Story;
use App\Services\Tokens\TokenService;
use App\States\Story\Recorded;
use Illuminate\Support\Facades\Queue;

/**
 * Se filmer plutôt que s'enregistrer (T-210).
 *
 * Ce que ces tests gardent, ce n'est pas « la vidéo marche » : c'est que la
 * nature de l'envoi se lit **dans le conteneur**, que sa borne de poids la
 * suit, et que la chaîne d'aval — transcription, livre, QR — continue de
 * travailler sur le son sans savoir qu'il y a eu une caméra.
 */
function videoLink(?Story $story = null): array
{
    $story ??= Story::factory()->proposed()->create();
    $issued = app(TokenService::class)->issue(TokenType::Record, $story, ['record', 'decide_share']);

    return [$issued->plain, $story];
}

it('ouvre un enregistrement vidéo à partir du seul conteneur annoncé', function (): void {
    fakeMediaStorage();
    [$token] = videoLink();

    $this->postJson("/r/{$token}/recordings", [
        'mime' => 'video/webm',
        'expected_bytes' => 300_000_000,
    ])->assertCreated();

    $recording = Recording::query()->sole();

    // Le client n'a déclaré aucune nature : c'est le serveur qui l'a lue.
    expect($recording->kind)->toBe(RecordingKind::Video)
        ->and($recording->original_mime)->toBe('video/webm')
        ->and($recording->isVideo())->toBeTrue();
});

it('donne à la vidéo sa propre borne de poids, bien au-dessus de celle du son', function (): void {
    fakeMediaStorage();
    [$token] = videoLink();

    // Trois cents mégaoctets : refusés pour du son, acceptés pour une vidéo.
    // Sans borne distincte, tout récit filmé de plus de trois minutes serait
    // rejeté à l'ouverture.
    $this->postJson("/r/{$token}/recordings", [
        'mime' => 'audio/webm',
        'expected_bytes' => 300_000_000,
    ])->assertJsonValidationErrorFor('expected_bytes');

    $this->postJson("/r/{$token}/recordings", [
        'mime' => 'video/webm',
        'expected_bytes' => 300_000_000,
    ])->assertCreated();
});

it('refuse un conteneur vidéo que nous ne savons pas traiter', function (): void {
    fakeMediaStorage();
    [$token] = videoLink();

    $this->postJson("/r/{$token}/recordings", [
        'mime' => 'video/avi',
        'expected_bytes' => 1_000,
    ])->assertJsonValidationErrorFor('mime');
});

it('note la forme de la réponse comme vidéo quand l’histoire passe à « enregistrée »', function (): void {
    Queue::fake();
    $storage = fakeMediaStorage();
    [$token, $story] = videoLink();

    $this->postJson("/r/{$token}/recordings", [
        'mime' => 'video/mp4',
        'expected_bytes' => 4_000_000,
    ])->assertCreated();

    $recording = Recording::query()->sole();
    $segment = collect($recording->segments ?? [])->firstWhere('number', 1);
    $key = (string) $segment['key'];
    $uploadId = (string) $segment['upload_id'];

    $storage->putPart($key, $uploadId, 1, 'la vidéo');

    $this->postJson("/r/{$token}/recordings/{$recording->id}/complete", [
        'segments' => [[
            'number' => 1,
            'parts' => [['number' => 1, 'etag' => $storage->etagFor($key, $uploadId, 1)]],
        ]],
    ])->assertOk()->assertJson(['confirmed' => true]);

    $story->refresh();

    expect($story->state)->toBeInstanceOf(Recorded::class)
        ->and($story->answer_type)->toBe(AnswerType::Video);
});

it('range le récit filmé sous une clé qui porte son conteneur', function (): void {
    fakeMediaStorage();
    [$token] = videoLink();

    $this->postJson("/r/{$token}/recordings", [
        'mime' => 'video/mp4',
        'expected_bytes' => 4_000_000,
    ])->assertCreated();

    $recording = Recording::query()->sole();
    $segment = collect($recording->segments ?? [])->firstWhere('number', 1);

    // Un `.bin` ici voudrait dire que le conteneur n'a pas été reconnu, et
    // le bloc 06 refuserait de le transcoder.
    expect((string) $segment['key'])->toEndWith('/segment-01.mp4');
});
