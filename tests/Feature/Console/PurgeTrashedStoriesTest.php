<?php

declare(strict_types=1);

use App\Jobs\PurgeDeletedStory;
use App\Models\Recording;
use App\Models\Story;
use App\Models\Transcript;
use App\Services\Storage\FakeMediaStorage;
use App\States\Story\Deleted;
use App\States\Story\Shared;
use App\States\Story\Trashed;
use Illuminate\Support\Facades\Queue;

/**
 * Une histoire à la corbeille, avec tout ce qu'une histoire porte.
 *
 * @return array{Story, Recording, FakeMediaStorage}
 */
function trashedWithContent(int $daysAgo): array
{
    $storage = fakeMediaStorage();

    $story = Story::factory()->trashed('shared')->create([
        'title' => 'Les crêpes de Kerhostin',
        'written_answer' => 'Un texte écrit à la main.',
    ]);
    $story->forceFill(['trashed_at' => now()->subDays($daysAgo)])->save();

    $recording = Recording::factory()->confirmed()->create(['story_id' => $story->id]);
    $recording->forceFill([
        'derived_mp3_path' => 'derives/histoire.mp3',
        'replica_path' => 'repliques/histoire.webm',
        'segments' => [['number' => 1, 'key' => (string) $recording->original_path, 'bytes' => 10]],
    ])->save();

    foreach ([(string) $recording->original_path, 'derives/histoire.mp3', 'repliques/histoire.webm'] as $key) {
        $storage->put($key, 'contenu');
    }

    Transcript::factory()->create(['story_id' => $story->id, 'recording_id' => $recording->id]);
    Transcript::factory()->fluide()->create(['story_id' => $story->id, 'recording_id' => $recording->id]);

    return [$story, $recording, $storage];
}

it('supprime les histoires en corbeille depuis plus de trente jours', function (): void {
    Queue::fake();
    [$story] = trashedWithContent(31);

    $this->artisan('stories:purge-trashed')->assertSuccessful();

    expect($story->refresh()->state)->toBeInstanceOf(Deleted::class);

    Queue::assertPushed(PurgeDeletedStory::class);
});

it('laisse tranquille une histoire encore dans le délai', function (): void {
    Queue::fake();
    [$story] = trashedWithContent(29);

    $this->artisan('stories:purge-trashed')->assertSuccessful();

    // Vingt-neuf jours, c'est encore récupérable : la promesse est de trente.
    expect($story->refresh()->state)->toBeInstanceOf(Trashed::class);

    Queue::assertNothingPushed();
});

/**
 * La commande fait passer l'histoire à `deleted` et **demande** l'effacement ;
 * c'est le job qui efface. Les tests le jouent donc explicitement, plutôt que
 * de compter sur une file synchrone — un pilote de file différent en
 * intégration continue rendrait ces tests verts en local et rouges ailleurs.
 */
function purgeAndRun(Story $story): void
{
    test()->artisan('stories:purge-trashed')->assertSuccessful();

    app()->call([new PurgeDeletedStory($story->id), 'handle']);
}

it('efface les objets du stockage et les transcriptions', function (): void {
    [$story, $recording, $storage] = trashedWithContent(31);

    purgeAndRun($story);

    $story->refresh();
    $recording->refresh();

    expect($storage->deletedKeys())->toContain('derives/histoire.mp3', 'repliques/histoire.webm')
        ->and($story->transcripts()->count())->toBe(0)
        ->and($recording->original_path)->toBeNull()
        ->and($recording->derived_mp3_path)->toBeNull()
        ->and($recording->replica_path)->toBeNull()
        ->and($recording->segments)->toBeNull();
});

it('vide le titre et la réponse écrite, mais garde la ligne', function (): void {
    [$story] = trashedWithContent(31);

    purgeAndRun($story);

    $story->refresh();

    // Assez pour savoir qu'une histoire a existé et qu'elle a été supprimée ;
    // rien pour la reconstituer. Une famille qui demande « où est passé le
    // récit de maman ? » mérite une réponse.
    expect(Story::query()->whereKey($story->id)->exists())->toBeTrue()
        ->and($story->title)->toBeNull()
        ->and($story->written_answer)->toBeNull()
        ->and($story->deleted_at)->not->toBeNull()
        ->and($story->questionText())->not->toBeNull();
});

it('supprime enfin le verbatim, que le déclencheur protégeait', function (): void {
    [$story] = trashedWithContent(31);

    // Le déclencheur Postgres du bloc 06 refuse la suppression d'un verbatim
    // tant que l'histoire vit. Elle ne vit plus : il laisse passer.
    purgeAndRun($story);

    expect(Transcript::query()->where('story_id', $story->id)->count())->toBe(0);
});

it('ne fait rien sur une histoire restaurée entre-temps', function (): void {
    [$story, , $storage] = trashedWithContent(1);
    $story->state->transitionTo(Shared::class);

    app()->call([new PurgeDeletedStory($story->id), 'handle']);

    // Un job rejoué ne doit pas vider une histoire qui a été récupérée.
    expect($story->refresh()->title)->not->toBeNull()
        ->and($storage->deletedKeys())->toBe([]);
});

it('est rejouable sans erreur', function (): void {
    [$story] = trashedWithContent(31);

    purgeAndRun($story);
    app()->call([new PurgeDeletedStory($story->id), 'handle']);

    expect($story->refresh()->state)->toBeInstanceOf(Deleted::class);
});
