<?php

declare(strict_types=1);

use App\Engine\EngineTick;
use App\Engine\Rules\ValidatedNotListened;
use App\Enums\ProjectStatus;
use App\Models\FamilyMember;
use App\Models\OutboundMessage;
use App\Models\Project;
use App\Models\Story;
use Carbon\CarbonImmutable;

/**
 * Le défaut sorti du checkpoint du bloc 09, qu'aucun test ne pouvait voir.
 *
 * Tous les tests des règles faussent les notifications : ils prouvent que la
 * règle **appelle** `notify()` pour chaque proche, jamais qu'un message part.
 * Or la clé de déduplication des messages sortants était bâtie sur
 * l'occurrence et le canal, sans le destinataire. Trois proches, un seul
 * courriel parti : les deux suivants tombaient sur la contrainte unique et
 * repartaient en silence, `action_taken` annonçant fièrement « nudged: 3 ».
 *
 * C'est la boucle famille — l'hypothèse H2 — qui se jouait là. Ce fichier
 * n'utilise pas `Notification::fake()`, et c'est tout son intérêt : il envoie
 * jusqu'à la couche des messages sortants et compte les lignes.
 */
it('écrit un message par proche, et non un seul', function (): void {
    $project = Project::factory()->create(['status' => ProjectStatus::Active]);
    $story = Story::factory()->forProject($project)->shared()->create(['title' => 'Les crêpes']);
    $story->forceFill(['shared_at' => now()->subDays(5)])->save();

    FamilyMember::factory()->count(3)->create(['project_id' => $project->id]);

    (new EngineTick([app(ValidatedNotListened::class)]))->run(CarbonImmutable::now());

    $messages = OutboundMessage::query()
        ->where('template', 'engine_validated_not_listened')
        ->get();

    expect($messages)->toHaveCount(3)
        ->and($messages->pluck('to_hash')->unique())->toHaveCount(3)
        ->and($messages->pluck('dedupe_key')->unique())->toHaveCount(3);
});

/**
 * L'autre moitié de la règle : deux tours ne doivent pas doubler les messages.
 * C'est ce que la clé protégeait, et le correctif ne doit pas l'abîmer.
 */
it('ne double pas les messages au tour suivant', function (): void {
    $project = Project::factory()->create(['status' => ProjectStatus::Active]);
    $story = Story::factory()->forProject($project)->shared()->create(['title' => 'Les crêpes']);
    $story->forceFill(['shared_at' => now()->subDays(5)])->save();

    FamilyMember::factory()->count(3)->create(['project_id' => $project->id]);

    (new EngineTick([app(ValidatedNotListened::class)]))->run(CarbonImmutable::now());
    (new EngineTick([app(ValidatedNotListened::class)]))->run(CarbonImmutable::now());

    expect(OutboundMessage::query()->where('template', 'engine_validated_not_listened')->count())->toBe(3);
});
