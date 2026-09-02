<?php

declare(strict_types=1);

use App\Actions\ValidateStoryAction;
use App\Enums\ShareDecision;
use App\Enums\StoryVisibility;
use App\Enums\ValidatedVia;
use App\Exceptions\Domain\ForbiddenTransition;
use App\Models\Story;
use App\States\Story\Shared;
use App\States\Story\Transitions\ShareStory;
use App\States\Story\Validated;
use Illuminate\Support\Facades\File;

/**
 * Le critère de sortie du bloc 07, éprouvé plutôt que promis.
 *
 * « Il n'existe aucun chemin de code qui passe une histoire en `shared` sans
 * `validated_at` et `validated_via` posés. » Deux façons de le vérifier : la
 * machine d'états le refuse, et aucun fichier ne contourne la machine.
 */
it('refuse le partage d’une histoire non validée', function (): void {
    $story = Story::factory()->transcribed()->create([
        'share_decision' => ShareDecision::Share,
        'share_decided_at' => now(),
    ]);

    // `Validated` est le seul état d'où l'on peut partager : la table des
    // transitions ne connaît pas d'autre chemin.
    expect($story->state->canTransitionTo(Shared::class))->toBeFalse();
});

it('pose toujours l’horodatage et le chemin de validation avant le partage', function (): void {
    $story = Story::factory()->transcribed()->create([
        'share_decision' => ShareDecision::Share,
        'share_decided_at' => now(),
    ]);

    app(ValidateStoryAction::class)->handle($story, ValidatedVia::RecordingEnd);
    $story->state->transitionTo(Shared::class);

    $story->refresh();

    expect($story->state)->toBeInstanceOf(Shared::class)
        ->and($story->validated_at)->not->toBeNull()
        ->and($story->validated_via)->toBe(ValidatedVia::RecordingEnd)
        ->and($story->shared_at)->not->toBeNull();
});

it('ne laisse aucun fichier instancier Shared en dehors de sa transition', function (): void {
    $offenders = [];

    foreach ([app_path(), database_path('seeders')] as $directory) {
        foreach (File::allFiles($directory) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $path = str_replace(base_path().'/', '', $file->getPathname());

            if ($path === 'app/States/Story/Transitions/ShareStory.php') {
                continue;
            }

            if (preg_match('/new\s+Shared\s*\(/', (string) File::get($file->getPathname())) === 1) {
                $offenders[] = $path;
            }
        }
    }

    // Un seul endroit construit l'état `Shared`, et c'est celui qui pose
    // `shared_at` et refuse les histoires réservées au livre.
    expect($offenders)->toBe([])
        ->and(File::exists(base_path('app/States/Story/Transitions/ShareStory.php')))->toBeTrue()
        ->and(ShareStory::class)->toBe(ShareStory::class);
});

it('ne rend jamais visible une histoire validée mais réservée au livre', function (): void {
    $story = Story::factory()->transcribed()->create([
        'share_decision' => ShareDecision::Share,
        'share_decided_at' => now(),
        'visibility' => StoryVisibility::BookOnly,
    ]);

    app(ValidateStoryAction::class)->handle($story, ValidatedVia::RecordingEnd);

    expect($story->refresh()->state)->toBeInstanceOf(Validated::class)
        ->and($story->isVisibleToFamily())->toBeFalse();

    // Et la transition refuse, plutôt que de partager en silence.
    $story->state->transitionTo(Shared::class);
})->throws(ForbiddenTransition::class);
