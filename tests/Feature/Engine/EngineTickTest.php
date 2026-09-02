<?php

declare(strict_types=1);

use App\Engine\EngineTick;
use App\Engine\Occurrence;
use App\Engine\Rule;
use App\Enums\EngineAudience;
use App\Enums\EngineRuleId;
use App\Enums\ProjectStatus;
use App\Models\EngineEvent;
use App\Models\Project;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Une règle de laboratoire : elle détecte ce qu'on lui donne et compte ses
 * déclenchements. Les onze vraies règles ont leurs propres fichiers ; ici on
 * éprouve le **tick**, pas les règles.
 */
function fakeRule(
    EngineRuleId $id,
    Collection $occurrences,
    EngineAudience $audience = EngineAudience::Narrator,
    bool $capped = false,
    bool $throws = false,
): Rule {
    return new class($id, $occurrences, $audience, $capped, $throws) implements Rule
    {
        public int $fired = 0;

        public function __construct(
            private readonly EngineRuleId $id,
            private readonly Collection $occurrences,
            private readonly EngineAudience $audience,
            private readonly bool $capped,
            private readonly bool $throws,
        ) {}

        public function id(): EngineRuleId
        {
            return $this->id;
        }

        public function audience(Occurrence $occurrence): EngineAudience
        {
            return $this->audience;
        }

        public function detect(CarbonImmutable $now): Collection
        {
            if ($this->throws) {
                throw new RuntimeException('règle cassée');
            }

            return $this->occurrences;
        }

        public function isCapped(Occurrence $occurrence): bool
        {
            return $this->capped;
        }

        public function fire(Occurrence $occurrence): array
        {
            $this->fired++;

            return ['sent' => true];
        }

        public function resumed(EngineEvent $event, CarbonImmutable $now): ?bool
        {
            return null;
        }
    };
}

it('enregistre un événement par occurrence détectée', function (): void {
    // Deux projets, donc deux narrateurs : le plafond quotidien protège une
    // personne, pas une file d'attente.
    $first = Project::factory()->create(['status' => ProjectStatus::Active]);
    $second = Project::factory()->create(['status' => ProjectStatus::Active]);

    $rule = fakeRule(EngineRuleId::LinkNotOpened, collect([
        new Occurrence($first, key: 'a', attempt: 1),
        new Occurrence($second, key: 'b', attempt: 1),
    ]));

    (new EngineTick([$rule]))->run(CarbonImmutable::now());

    expect(EngineEvent::query()->count())->toBe(2)
        ->and($rule->fired)->toBe(2)
        ->and(EngineEvent::query()->first()?->rule_id)->toBe(EngineRuleId::LinkNotOpened);
});

it('ne parle pas deux fois au même narrateur, même pour deux histoires', function (): void {
    $project = Project::factory()->create(['status' => ProjectStatus::Active]);
    $rule = fakeRule(EngineRuleId::LinkNotOpened, collect([
        new Occurrence($project, key: 'histoire-1', attempt: 1),
        new Occurrence($project, key: 'histoire-2', attempt: 1),
    ]));

    (new EngineTick([$rule]))->run(CarbonImmutable::now());

    // Deux liens non ouverts la même semaine, c'est un narrateur débordé —
    // pas une raison de lui écrire deux fois dans la journée.
    expect($rule->fired)->toBe(1)
        ->and(EngineEvent::query()->count())->toBe(2);

    expect(EngineEvent::query()->get()->filter(fn ($e) => $e->wasSuppressed()))->toHaveCount(1);
});

it('ne déclenche jamais deux fois la même occurrence', function (): void {
    $project = Project::factory()->create(['status' => ProjectStatus::Active]);
    $rule = fakeRule(EngineRuleId::LinkNotOpened, collect([
        new Occurrence($project, key: 'a', attempt: 1),
    ]));

    $tick = new EngineTick([$rule]);
    $tick->run(CarbonImmutable::now());
    $tick->run(CarbonImmutable::now()->addHour());

    // La clé unique en base porte l'idempotence, pas une vérification PHP :
    // deux ticks simultanés ne peuvent pas envoyer deux fois.
    expect(EngineEvent::query()->count())->toBe(1)
        ->and($rule->fired)->toBe(1);
});

it('ignore une occurrence plafonnée, sans l’enregistrer', function (): void {
    $project = Project::factory()->create(['status' => ProjectStatus::Active]);
    $rule = fakeRule(EngineRuleId::LinkNotOpened, collect([
        new Occurrence($project, key: 'a', attempt: 3),
    ]), capped: true);

    (new EngineTick([$rule]))->run(CarbonImmutable::now());

    expect(EngineEvent::query()->count())->toBe(0)
        ->and($rule->fired)->toBe(0);
});

it('ignore les projets qui ne doivent pas être sollicités', function (string $status): void {
    $project = Project::factory()->create(['status' => ProjectStatus::from($status)]);
    $rule = fakeRule(EngineRuleId::LinkNotOpened, collect([
        new Occurrence($project, key: 'a', attempt: 1),
    ]));

    (new EngineTick([$rule]))->run(CarbonImmutable::now());

    expect(EngineEvent::query()->count())->toBe(0)
        ->and($rule->fired)->toBe(0);
})->with(['paused', 'frozen_bereavement', 'cancelled', 'completed', 'dormant']);

it('ignore un projet en pause par sa date, même actif', function (): void {
    $project = Project::factory()->create(['status' => ProjectStatus::Active]);
    $project->forceFill(['paused_until' => now()->addWeeks(3)])->save();

    $rule = fakeRule(EngineRuleId::LinkNotOpened, collect([
        new Occurrence($project->refresh(), key: 'a', attempt: 1),
    ]));

    // « Aucun autre envoi pendant la pause » : la pause est une promesse, pas
    // une préférence d'affichage.
    (new EngineTick([$rule]))->run(CarbonImmutable::now());

    expect(EngineEvent::query()->count())->toBe(0);
});

it('laisse passer la confirmation de pause, qui parle de la pause elle-même', function (): void {
    $project = Project::factory()->create(['status' => ProjectStatus::Paused]);
    $project->forceFill(['paused_until' => now()->addWeeks(3)])->save();

    $rule = fakeRule(EngineRuleId::PauseRequested, collect([
        new Occurrence($project->refresh(), key: 'a', attempt: 1),
    ]));

    (new EngineTick([$rule]))->run(CarbonImmutable::now());

    // Sans cette exception, le narrateur ne saurait jamais que sa pause a été
    // prise en compte — et c'est précisément le message qu'il attend.
    expect(EngineEvent::query()->count())->toBe(1);
});

it('n’envoie jamais deux messages au narrateur le même jour', function (): void {
    $project = Project::factory()->create(['status' => ProjectStatus::Active]);

    $winner = fakeRule(EngineRuleId::LinkNotOpened, collect([
        new Occurrence($project, key: 'a', attempt: 1),
    ]));
    $loser = fakeRule(EngineRuleId::NarratorSilence10d, collect([
        new Occurrence($project, key: 'b', attempt: 1),
    ]));

    (new EngineTick([$winner, $loser]))->run(CarbonImmutable::now());

    // La règle la plus haute dans l'annexe C gagne ; l'autre est **consignée**
    // comme supprimée, parce que savoir qu'elle aurait parlé fait partie de la
    // mesure.
    expect($winner->fired)->toBe(1)
        ->and($loser->fired)->toBe(0);

    $suppressed = EngineEvent::query()->where('rule_id', EngineRuleId::NarratorSilence10d->value)->sole();

    expect($suppressed->wasSuppressed())->toBeTrue()
        ->and($suppressed->action_taken['suppressed_by'])->toBe('link_not_opened');
});

it('parle quand même aux proches et à l’Initiateur·rice le même jour', function (): void {
    $project = Project::factory()->create(['status' => ProjectStatus::Active]);

    $narrator = fakeRule(EngineRuleId::LinkNotOpened, collect([
        new Occurrence($project, key: 'a', attempt: 1),
    ]));
    $family = fakeRule(EngineRuleId::ValidatedNotListened, collect([
        new Occurrence($project, key: 'b', attempt: 1),
    ]), audience: EngineAudience::Family);
    $initiator = fakeRule(EngineRuleId::ThreeStoriesNoReaction, collect([
        new Occurrence($project, key: 'c', attempt: 1),
    ]), audience: EngineAudience::Initiator);

    (new EngineTick([$narrator, $family, $initiator]))->run(CarbonImmutable::now());

    // Le plafond quotidien protège le narrateur, pas la famille : ce sont
    // des personnes différentes, avec des seuils de fatigue différents.
    expect($narrator->fired)->toBe(1)
        ->and($family->fired)->toBe(1)
        ->and($initiator->fired)->toBe(1);
});

it('parle de nouveau au narrateur le lendemain', function (): void {
    $project = Project::factory()->create(['status' => ProjectStatus::Active]);

    $first = fakeRule(EngineRuleId::LinkNotOpened, collect([
        new Occurrence($project, key: 'jour-1', attempt: 1),
    ]));
    (new EngineTick([$first]))->run(CarbonImmutable::now());

    $this->travel(1)->day();

    $second = fakeRule(EngineRuleId::NarratorSilence10d, collect([
        new Occurrence($project, key: 'jour-2', attempt: 1),
    ]));
    (new EngineTick([$second]))->run(CarbonImmutable::now());

    expect($second->fired)->toBe(1);
});

it('ne compte pas un événement supprimé comme un message envoyé', function (): void {
    $project = Project::factory()->create(['status' => ProjectStatus::Active]);

    $winner = fakeRule(EngineRuleId::LinkNotOpened, collect([new Occurrence($project, key: 'a', attempt: 1)]));
    $first = fakeRule(EngineRuleId::MicDenied, collect([new Occurrence($project, key: 'b', attempt: 1)]));
    $second = fakeRule(EngineRuleId::NarratorSilence10d, collect([new Occurrence($project, key: 'c', attempt: 1)]));

    (new EngineTick([$winner, $first, $second]))->run(CarbonImmutable::now());

    // Deux règles supprimées, pas une supprimée puis une autorisée : sinon un
    // événement consigné vaudrait permission.
    expect($winner->fired)->toBe(1)
        ->and($first->fired)->toBe(0)
        ->and($second->fired)->toBe(0);
});

it('tolère une règle cassée sans bloquer les suivantes', function (): void {
    Log::spy();
    $project = Project::factory()->create(['status' => ProjectStatus::Active]);

    $broken = fakeRule(EngineRuleId::InvitationNotAccepted, collect(), throws: true);
    $healthy = fakeRule(EngineRuleId::ValidatedNotListened, collect([
        new Occurrence($project, key: 'a', attempt: 1),
    ]), audience: EngineAudience::Family);

    (new EngineTick([$broken, $healthy]))->run(CarbonImmutable::now());

    // Une règle qui plante ne doit pas priver dix familles de leurs relances.
    expect($healthy->fired)->toBe(1);

    Log::shouldHaveReceived('error')->once();
});

it('rapporte ce qu’il a fait', function (): void {
    $project = Project::factory()->create(['status' => ProjectStatus::Active]);
    $rule = fakeRule(EngineRuleId::LinkNotOpened, collect([
        new Occurrence($project, key: 'a', attempt: 1),
        new Occurrence($project, key: 'b', attempt: 1),
    ]));

    $report = (new EngineTick([$rule]))->run(CarbonImmutable::now());

    expect($report->fired)->toBe(1)
        ->and($report->suppressed)->toBe(1)
        ->and($report->failed)->toBe(0);
});
