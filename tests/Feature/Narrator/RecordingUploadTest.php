<?php

declare(strict_types=1);

use App\Enums\TokenType;
use App\Enums\UploadStatus;
use App\Jobs\ConcatenateSegments;
use App\Jobs\ReplicateRecording;
use App\Jobs\TranscodeRecording;
use App\Models\Recording;
use App\Models\Story;
use App\Services\Tokens\TokenService;
use App\States\Story\Proposed;
use App\States\Story\Recorded;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

/**
 * @return array{string, string}
 */
function segmentOf(Recording $recording, int $number): array
{
    $segment = collect($recording->segments ?? [])->firstWhere('number', $number);

    return [(string) $segment['key'], (string) $segment['upload_id']];
}

function recordLink(?Story $story = null): array
{
    $story ??= Story::factory()->proposed()->create();
    $issued = app(TokenService::class)->issue(TokenType::Record, $story, ['record', 'decide_share']);

    return [$issued->plain, $story];
}

it('crée un enregistrement initié avec un identifiant d’envoi', function (): void {
    fakeMediaStorage();
    [$token, $story] = recordLink();

    $response = $this->postJson("/r/{$token}/recordings", [
        'mime' => 'audio/webm',
        'expected_bytes' => 2_000_000,
    ]);

    $response->assertCreated()->assertJsonStructure(['recording_id', 'segments', 'part_size_bytes']);

    $recording = Recording::query()->sole();

    expect($recording->story_id)->toBe($story->id)
        ->and($recording->upload_status)->toBe(UploadStatus::Uploading)
        ->and($recording->segmentCount())->toBe(1)
        ->and($recording->is_current)->toBeTrue()
        ->and($story->refresh()->state)->toBeInstanceOf(Proposed::class);
});

it('refuse un type de fichier non accepté', function (): void {
    fakeMediaStorage();
    [$token] = recordLink();

    $this->postJson("/r/{$token}/recordings", [
        'mime' => 'application/zip',
        'expected_bytes' => 1000,
    ])->assertStatus(422)->assertJsonValidationErrors('mime');
});

it('refuse un envoi annoncé plus gros que la limite', function (): void {
    fakeMediaStorage();
    [$token] = recordLink();

    $this->postJson("/r/{$token}/recordings", [
        'mime' => 'audio/webm',
        'expected_bytes' => config('product.recording.max_bytes') + 1,
    ])->assertStatus(422)->assertJsonValidationErrors('expected_bytes');
});

it('signe une part et refuse un numéro hors bornes', function (): void {
    fakeMediaStorage();
    [$token, $story] = recordLink();

    $recording = Recording::query()->findOrFail(
        $this->postJson("/r/{$token}/recordings", ['mime' => 'audio/webm', 'expected_bytes' => 10])
            ->json('recording_id'),
    );

    $this->postJson("/r/{$token}/recordings/{$recording->id}/segments/1/parts/1/sign")
        ->assertOk()
        ->assertJsonStructure(['url', 'expires_in_minutes']);

    $this->postJson("/r/{$token}/recordings/{$recording->id}/segments/1/parts/2001/sign")
        ->assertNotFound();

    // Un segment jamais ouvert n'a pas d'envoi à signer.
    $this->postJson("/r/{$token}/recordings/{$recording->id}/segments/9/parts/1/sign")
        ->assertNotFound();
});

it('refuse un enregistrement qui n’appartient pas à l’histoire du jeton', function (): void {
    fakeMediaStorage();
    [$token] = recordLink();

    $foreign = Recording::factory()->create();

    $this->postJson("/r/{$token}/recordings/{$foreign->id}/segments/1/parts/1/sign")->assertNotFound();
    // Corps valable, pour que ce soit bien l'appartenance qui refuse.
    $this->postJson("/r/{$token}/recordings/{$foreign->id}/complete", [
        'segments' => [['number' => 1, 'parts' => [['number' => 1, 'etag' => 'x']]]],
    ])->assertNotFound();
    $this->postJson("/r/{$token}/recordings/{$foreign->id}/segments")->assertNotFound();
});

it('confirme l’enregistrement seulement après un HeadObject réussi', function (): void {
    Queue::fake();
    $storage = fakeMediaStorage();
    [$token, $story] = recordLink();

    $initiate = $this->postJson("/r/{$token}/recordings", [
        'mime' => 'audio/webm',
        'expected_bytes' => 10,
    ])->json();

    $recording = Recording::query()->findOrFail($initiate['recording_id']);
    [$key, $uploadId] = segmentOf($recording, 1);

    $storage->putPart($key, $uploadId, 1, 'bonjour');

    $this->postJson("/r/{$token}/recordings/{$recording->id}/complete", [
        'segments' => [[
            'number' => 1,
            'parts' => [['number' => 1, 'etag' => $storage->etagFor($key, $uploadId, 1)]],
        ]],
        'client_duration_seconds' => 42,
    ])->assertOk()->assertJson(['confirmed' => true]);

    $recording->refresh();

    expect($recording->confirmed_at)->not->toBeNull()
        ->and($recording->original_bytes)->toBe(7)
        ->and($recording->original_path)->toBe($key)
        ->and($recording->upload_status)->toBe(UploadStatus::Completed)
        ->and($story->refresh()->state)->toBeInstanceOf(Recorded::class);

    // Un seul segment : rien à recoller, on réplique puis on transcode.
    Queue::assertPushedWithChain(ReplicateRecording::class, [TranscodeRecording::class]);
});

it('ne confirme rien et ne transitionne pas quand le stockage ne détient pas l’objet', function (): void {
    Queue::fake();
    $storage = fakeMediaStorage();
    [$token, $story] = recordLink();

    $initiate = $this->postJson("/r/{$token}/recordings", [
        'mime' => 'audio/webm',
        'expected_bytes' => 10,
    ])->json();

    $recording = Recording::query()->findOrFail($initiate['recording_id']);

    // Aucune part envoyée : la conclusion doit échouer.
    $this->postJson("/r/{$token}/recordings/{$recording->id}/complete", [
        'segments' => [['number' => 1, 'parts' => [['number' => 1, 'etag' => 'inventé']]]],
    ])->assertStatus(422)->assertJson(['confirmed' => false]);

    $recording->refresh();

    expect($recording->confirmed_at)->toBeNull()
        ->and($recording->upload_status)->toBe(UploadStatus::Failed)
        ->and($story->refresh()->state)->toBeInstanceOf(Proposed::class);

    Queue::assertNotPushed(ReplicateRecording::class);
});

it('marque un envoi abandonné', function (): void {
    $storage = fakeMediaStorage();
    [$token, $story] = recordLink();

    $recording = Recording::query()->findOrFail(
        $this->postJson("/r/{$token}/recordings", ['mime' => 'audio/webm', 'expected_bytes' => 10])
            ->json('recording_id'),
    );

    $this->postJson("/r/{$token}/recordings/{$recording->id}/abort")->assertOk();

    expect($recording->refresh()->upload_status)->toBe(UploadStatus::Aborted);
});

it('conserve l’ancien enregistrement quand le narrateur recommence', function (): void {
    fakeMediaStorage();

    $story = Story::factory()->recorded()->create();
    $first = Recording::factory()->confirmed()->create(['story_id' => $story->id]);

    [$token] = recordLink($story);

    $this->postJson("/r/{$token}/recordings", [
        'mime' => 'audio/webm',
        'expected_bytes' => 10,
    ])->assertCreated();

    expect($first->refresh()->is_current)->toBeFalse()
        ->and($first->confirmed_at)->not->toBeNull()
        ->and(Recording::query()->current()->count())->toBe(1)
        ->and(Recording::query()->count())->toBe(2);
});

it('n’efface jamais le chemin d’un audio confirmé, même par écriture directe', function (): void {
    $recording = Recording::factory()->confirmed()->create();

    expect(fn () => DB::table('recordings')
        ->where('id', $recording->id)
        ->update(['original_path' => 'ailleurs.webm']))
        ->toThrow(QueryException::class);
});

it('demande la concaténation quand il y a plusieurs segments', function (): void {
    Queue::fake();
    $storage = fakeMediaStorage();
    [$token, $story] = recordLink();

    $recording = Recording::query()->findOrFail(
        $this->postJson("/r/{$token}/recordings", ['mime' => 'audio/webm', 'expected_bytes' => 10])
            ->json('recording_id'),
    );

    // Une interruption : le navigateur ouvre un second segment.
    $this->postJson("/r/{$token}/recordings/{$recording->id}/segments")
        ->assertCreated()
        ->assertJson(['number' => 2]);

    $recording->refresh();
    $segments = [];

    foreach ([1, 2] as $number) {
        [$key, $uploadId] = segmentOf($recording, $number);
        $storage->putPart($key, $uploadId, 1, "segment {$number}");

        $segments[] = [
            'number' => $number,
            'parts' => [['number' => 1, 'etag' => $storage->etagFor($key, $uploadId, 1)]],
        ];
    }

    $this->postJson("/r/{$token}/recordings/{$recording->id}/complete", ['segments' => $segments])
        ->assertOk();

    $recording->refresh();

    // Confirmé sur ses segments ; le fichier recollé arrive ensuite, et
    // c'est lui qui renseignera `original_path`.
    expect($recording->confirmed_at)->not->toBeNull()
        ->and($recording->original_path)->toBeNull()
        ->and($recording->original_bytes)->toBe(18)
        ->and($recording->segmentCount())->toBe(2);

    // L'ordre est le fond de l'affaire : la source est répliquée avant tout
    // dérivé, et c'est le fichier recollé qui part au transcodage.
    Queue::assertPushedWithChain(ReplicateRecording::class, [
        ConcatenateSegments::class,
        TranscodeRecording::class,
    ]);
});
