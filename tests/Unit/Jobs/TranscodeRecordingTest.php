<?php

declare(strict_types=1);

use App\Jobs\SubmitTranscription;
use App\Jobs\TranscodeRecording;
use App\Models\Recording;
use App\Models\Story;
use App\Services\Storage\MediaStorage;
use Illuminate\Process\FakeProcessResult;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Queue;

/**
 * `ffprobe` puis `ffmpeg` : deux processus, simulés dans cet ordre.
 *
 * Le faux `ffmpeg` écrit vraiment son fichier de sortie : sans ça, le job
 * refuse le résultat — un `ffmpeg` qui sort en 0 sans rien produire est
 * exactement le cas qui trompait la chaîne, et le garde-fou le rattrape.
 */
function fakeFfmpeg(string $duration = '182.44'): void
{
    Process::fake([
        '*ffprobe*' => Process::result(output: $duration."\n"),
        '*ffmpeg*' => fn (): FakeProcessResult => writeDerivedMp3(),
    ]);
}

function writeDerivedMp3(string $contents = 'mp3 dérivé'): FakeProcessResult
{
    foreach (File::directories(storage_path('app/transcode')) as $directory) {
        File::put($directory.'/derived.mp3', $contents);
    }

    return Process::result(output: '');
}

function transcodable(): Recording
{
    $story = Story::factory()->recorded()->create();
    $recording = Recording::factory()->confirmed()->create(['story_id' => $story->id]);

    fakeMediaStorage()->put((string) $recording->original_path, 'audio brut');

    return $recording;
}

/**
 * Le faux ffmpeg d'un récit filmé : il écrit les deux dérivés (T-210).
 *
 * `ffprobe` est appelé deux fois — la durée, puis la hauteur de l'image — et
 * rend la même valeur ; ce que ces tests gardent, c'est la présence des deux
 * fichiers, pas la finesse du réencodage.
 */
function fakeFfmpegVideo(): void
{
    Process::fake([
        '*ffprobe*' => Process::result(output: "720\n"),
        '*ffmpeg*' => function (): FakeProcessResult {
            foreach (File::directories(storage_path('app/transcode')) as $directory) {
                File::put($directory.'/derived.mp3', 'mp3 dérivé');
                File::put($directory.'/derived.mp4', 'mp4 dérivé');
            }

            return Process::result(output: '');
        },
    ]);
}

function filmable(string $mime = 'video/webm'): Recording
{
    $story = Story::factory()->recorded()->create();
    $recording = Recording::factory()->video($mime)->confirmed()->create(['story_id' => $story->id]);

    fakeMediaStorage()->put((string) $recording->original_path, 'vidéo brute');

    return $recording;
}

beforeEach(function (): void {
    Queue::fake();
});

it('lit la durée réelle par ffprobe', function (): void {
    fakeFfmpeg('182.44');
    $recording = transcodable();

    app()->call([new TranscodeRecording($recording->id), 'handle']);

    // La durée annoncée par le navigateur est indicative ; celle-ci compte.
    expect($recording->refresh()->duration_seconds)->toBe('182.44');
});

it('écrit un dérivé MP3 sur le stockage et le note sur l’enregistrement', function (): void {
    fakeFfmpeg();
    $recording = transcodable();
    $storage = app(MediaStorage::class);

    app()->call([new TranscodeRecording($recording->id), 'handle']);

    $recording->refresh();

    expect($recording->derived_mp3_path)->toEndWith('.mp3')
        ->and($storage->exists((string) $recording->derived_mp3_path))->toBeTrue()
        ->and($storage->get((string) $recording->derived_mp3_path))->toBe('mp3 dérivé');
});

it('ne touche jamais à l’original', function (): void {
    fakeFfmpeg();
    $recording = transcodable();
    $original = (string) $recording->original_path;
    $storage = app(MediaStorage::class);

    app()->call([new TranscodeRecording($recording->id), 'handle']);

    // Principe non négociable : l'audio source est sacré.
    expect($recording->refresh()->original_path)->toBe($original)
        ->and($storage->get($original))->toBe('audio brut')
        ->and($storage->deletedKeys())->not->toContain($original);
});

it('ne refait rien quand le dérivé existe déjà', function (): void {
    fakeFfmpeg();
    $recording = transcodable();
    $recording->forceFill([
        'derived_mp3_path' => 'derives/deja.mp3',
        'duration_seconds' => '99.00',
    ])->save();

    app()->call([new TranscodeRecording($recording->id), 'handle']);

    Process::assertNothingRan();

    expect($recording->refresh()->derived_mp3_path)->toBe('derives/deja.mp3')
        ->and($recording->duration_seconds)->toBe('99.00');
});

it('demande la transcription, y compris quand il n’a rien eu à faire', function (): void {
    fakeFfmpeg();
    $recording = transcodable();

    app()->call([new TranscodeRecording($recording->id), 'handle']);
    Queue::assertPushed(SubmitTranscription::class, 1);

    app()->call([new TranscodeRecording($recording->id), 'handle']);
    // Rejoué, il redemande la suite : c'est ce qui rend la chaîne reprenable.
    Queue::assertPushed(SubmitTranscription::class, 2);
});

it('échoue bruyamment si ffmpeg ne produit pas de fichier', function (): void {
    Process::fake([
        '*ffprobe*' => Process::result(output: "12.00\n"),
        // Code de sortie 0, aucun fichier : le cas qui trompait la chaîne.
        '*ffmpeg*' => Process::result(output: '', errorOutput: ''),
    ]);
    $recording = transcodable();

    app()->call([new TranscodeRecording($recording->id), 'handle']);
})->throws(RuntimeException::class, 'n’a pas produit de dérivé MP3');

it('ignore un enregistrement non confirmé', function (): void {
    fakeFfmpeg();
    $story = Story::factory()->recorded()->create();
    $recording = Recording::factory()->create(['story_id' => $story->id]);

    app()->call([new TranscodeRecording($recording->id), 'handle']);

    Process::assertNothingRan();
    Queue::assertNothingPushed();
    expect($recording->refresh()->derived_mp3_path)->toBeNull();
});

it('laisse la durée vide si ffprobe échoue, sans bloquer la chaîne', function (): void {
    Process::fake([
        '*ffprobe*' => Process::result(output: '', errorOutput: 'illisible', exitCode: 1),
        '*ffmpeg*' => fn (): FakeProcessResult => writeDerivedMp3(),
    ]);
    $recording = transcodable();

    app()->call([new TranscodeRecording($recording->id), 'handle']);

    // Une durée manquante se rattrape ; une transcription perdue, non.
    expect($recording->refresh()->duration_seconds)->toBeNull()
        ->and($recording->derived_mp3_path)->not->toBeNull();
    Queue::assertPushed(SubmitTranscription::class);
});

it('efface le dossier de travail, même après un échec', function (): void {
    Process::fake([
        '*ffprobe*' => Process::result(output: "12.00\n"),
        '*ffmpeg*' => Process::result(output: '', errorOutput: 'plantage', exitCode: 1),
    ]);
    $recording = transcodable();

    try {
        app()->call([new TranscodeRecording($recording->id), 'handle']);
    } catch (RuntimeException) {
        // Attendu : on vérifie le nettoyage, pas l'exception.
    }

    // L'audio d'une personne ne traîne pas sur le disque du serveur.
    expect(File::exists(storage_path('app/transcode/'.$recording->id)))->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| Le récit filmé (T-210)
|--------------------------------------------------------------------------
*/

it('tire du récit filmé le même MP3 que d’une voix', function (): void {
    fakeFfmpegVideo();
    $recording = filmable();

    app()->call([new TranscodeRecording($recording->id), 'handle']);

    // C'est tout l'enjeu : la transcription, le rendu Fluide et le QR du
    // livre continuent de lire un MP3 et ignorent qu'il y a eu une caméra.
    expect($recording->refresh()->derived_mp3_path)->toEndWith('.mp3')
        ->and(app(MediaStorage::class)->get((string) $recording->derived_mp3_path))->toBe('mp3 dérivé');
});

it('tire aussi un MP4, sans quoi un WebM d’Android serait invisible sur iPhone', function (): void {
    fakeFfmpegVideo();
    $recording = filmable('video/webm');

    app()->call([new TranscodeRecording($recording->id), 'handle']);

    $recording->refresh();

    expect($recording->derived_mp4_path)->toEndWith('.mp4')
        ->and(app(MediaStorage::class)->exists((string) $recording->derived_mp4_path))->toBeTrue()
        ->and($recording->playableVideoPath())->toBe($recording->derived_mp4_path);
});

it('ne dérive aucun MP4 d’un enregistrement de voix', function (): void {
    fakeFfmpeg();
    $recording = transcodable();

    app()->call([new TranscodeRecording($recording->id), 'handle']);

    expect($recording->refresh()->derived_mp4_path)->toBeNull()
        ->and($recording->playableVideoPath())->toBeNull();
});

it('ne donne pas un WebM à regarder tant que son MP4 n’existe pas', function (): void {
    // Le dérivé arrive par un job : entre la confirmation et lui, il y a un
    // moment où l'original est tout ce qu'on a. Servir ce WebM à un iPhone
    // n'afficherait pas une vidéo, mais un lecteur cassé — mieux vaut la
    // voix, qui elle se lit partout.
    $webm = Recording::factory()->video('video/webm')->confirmed()->create();
    $mp4 = Recording::factory()->video('video/mp4')->confirmed()->create();

    expect($webm->playableVideoPath())->toBeNull()
        ->and($mp4->playableVideoPath())->toBe($mp4->original_path);
});

it('ne refait pas le travail déjà fait', function (): void {
    fakeFfmpegVideo();
    $recording = filmable();

    app()->call([new TranscodeRecording($recording->id), 'handle']);
    $first = $recording->refresh()->derived_mp4_path;

    // Deux appels à ffmpeg au premier passage : le MP3, puis le MP4.
    Process::assertRanTimes(fn ($process): bool => str_contains($process->command[0] ?? '', 'ffmpeg'), 2);

    app()->call([new TranscodeRecording($recording->id), 'handle']);

    // Le second passage n'en ajoute aucun : réencoder vingt minutes de vidéo
    // parce qu'un job a été rejoué coûterait le prix d'un serveur.
    Process::assertRanTimes(fn ($process): bool => str_contains($process->command[0] ?? '', 'ffmpeg'), 2);

    expect($recording->refresh()->derived_mp4_path)->toBe($first);
    Queue::assertPushed(SubmitTranscription::class);
});
