<?php

declare(strict_types=1);

use App\Engine\InitiatorLoad;
use App\Enums\EngineAudience;
use App\Enums\EngineRuleId;
use App\Models\EngineEvent;
use App\Models\Project;

function tellInitiator(Project $project, EngineRuleId $rule, string $key, ?string $firedAt = null): EngineEvent
{
    $event = new EngineEvent([
        'rule_id' => $rule,
        'occurrence_key' => $key,
        'dedupe_key' => $rule->value.':'.$key,
        'fired_at' => $firedAt === null ? now() : now()->modify($firedAt),
        'action_taken' => ['told' => EngineAudience::Initiator->value],
    ]);

    $event->project()->associate($project);
    $event->save();

    return $event;
}

it('compte les sollicitations du mois adressées à l’Initiateur·rice', function (): void {
    $project = Project::factory()->create();

    tellInitiator($project, EngineRuleId::ThreeStoriesNoReaction, 'a');
    tellInitiator($project, EngineRuleId::NarratorSilence21d, 'b');

    expect(InitiatorLoad::requestsThisMonth($project))->toBe(2);
});

it('ne compte pas ce qui a été dit au narrateur', function (): void {
    $project = Project::factory()->create();

    $event = tellInitiator($project, EngineRuleId::LinkNotOpened, 'a');
    $event->forceFill(['action_taken' => ['told' => EngineAudience::Narrator->value]])->save();

    // R-7 protège l'Initiateur·rice, pas le narrateur : ce sont deux
    // personnes, deux seuils de fatigue.
    expect(InitiatorLoad::requestsThisMonth($project))->toBe(0);
});

it('ne compte pas un événement supprimé', function (): void {
    $project = Project::factory()->create();

    $event = tellInitiator($project, EngineRuleId::ThreeStoriesNoReaction, 'a');
    $event->forceFill(['action_taken' => [
        'suppressed_by' => 'link_not_opened',
        'would_have_told' => 'initiator',
    ]])->save();

    // Un message qui n'est pas parti n'a fatigué personne.
    expect(InitiatorLoad::requestsThisMonth($project))->toBe(0);
});

it('oublie le mois précédent', function (): void {
    $project = Project::factory()->create();

    tellInitiator($project, EngineRuleId::ThreeStoriesNoReaction, 'ancien', '-40 days');
    tellInitiator($project, EngineRuleId::NarratorSilence21d, 'récent');

    expect(InitiatorLoad::requestsThisMonth($project))->toBe(1);
});

it('ne compte que le projet demandé', function (): void {
    $project = Project::factory()->create();
    $other = Project::factory()->create();

    tellInitiator($other, EngineRuleId::ThreeStoriesNoReaction, 'a');

    expect(InitiatorLoad::requestsThisMonth($project))->toBe(0);
});

it('déclare le plafond atteint à la quatrième sollicitation', function (): void {
    $project = Project::factory()->create();

    foreach (['a', 'b', 'c'] as $key) {
        tellInitiator($project, EngineRuleId::ThreeStoriesNoReaction, $key);
        expect(InitiatorLoad::isSaturated($project))->toBeFalse();
    }

    tellInitiator($project, EngineRuleId::NarratorSilence21d, 'd');

    // Quatre actions par mois est le plafond du dossier (R-7) : la cinquième
    // ne part pas. Une Initiateur·rice épuisée ne relance plus personne.
    expect(InitiatorLoad::isSaturated($project))->toBeTrue()
        ->and((int) config('product.engine.initiator_max_requests_per_month'))->toBe(4);
});
