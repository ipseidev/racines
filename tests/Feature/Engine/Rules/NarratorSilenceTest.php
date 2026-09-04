<?php

declare(strict_types=1);

use App\Engine\EngineTick;
use App\Engine\Rules\NarratorSilence10d;
use App\Engine\Rules\NarratorSilence21d;
use App\Enums\EngineRuleId;
use App\Enums\ProjectStatus;
use App\Enums\TokenType;
use App\Features\PhoneOptionOffer;
use App\Models\AccessToken;
use App\Models\EngineEvent;
use App\Models\Narrator;
use App\Models\Project;
use App\Models\Story;
use App\Notifications\EngineNotification;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Notification;
use Laravel\Pennant\Feature;

/**
 * Un projet accepté, dont la dernière histoire date de `$daysAgo` jours.
 */
function silentProject(int $daysAgo): Project
{
    $project = Project::factory()->create(['status' => ProjectStatus::Active]);
    $project->forceFill(['accepted_at' => now()->subMonths(2)])->save();

    Narrator::factory()->primary()->create([
        'project_id' => $project->id,
        'first_name' => 'Odette',
        'email' => 'odette@example.test',
    ]);

    $story = Story::factory()->forProject($project->refresh())->recorded()->create();
    $story->forceFill(['recorded_at' => now()->subDays($daysAgo)])->save();

    return $project->refresh();
}

beforeEach(function (): void {
    Notification::fake();
});

describe('dix jours de silence', function (): void {
    function runLightRule(): void
    {
        (new EngineTick([app(NarratorSilence10d::class)]))->run(CarbonImmutable::now());
    }

    it('ne propose rien avant dix jours', function (): void {
        silentProject(9);

        runLightRule();

        expect(EngineEvent::query()->count())->toBe(0);
    });

    it('propose une question plus légère au dixième jour', function (): void {
        $project = silentProject(10);

        runLightRule();

        $event = EngineEvent::query()->sole();

        // On ne relance pas : on **change de question**. Un silence de dix
        // jours veut souvent dire que la précédente était trop lourde.
        expect($event->rule_id)->toBe(EngineRuleId::NarratorSilence10d)
            ->and($event->action_taken)->toHaveKey('story_id');

        Notification::assertSentTo($project->primaryNarrator, EngineNotification::class);
    });

    it('crée bien une nouvelle histoire à raconter', function (): void {
        $project = silentProject(10);
        $before = $project->stories()->count();

        runLightRule();

        expect($project->refresh()->stories()->count())->toBe($before + 1);
    });

    it('ne propose pas deux fois dans les dix jours', function (): void {
        silentProject(10);

        runLightRule();
        $this->travel(5)->days();
        runLightRule();

        // Au-delà, on ne propose plus, on harcèle.
        expect(EngineEvent::query()->count())->toBe(1);
    });

    it('se taît dès qu’une histoire est enregistrée', function (): void {
        $project = silentProject(10);
        Story::factory()->forProject($project)->recorded()->create();

        runLightRule();

        expect(EngineEvent::query()->count())->toBe(0);
    });

    it('se taît pour un projet jamais accepté', function (): void {
        $project = silentProject(10);
        $project->forceFill(['accepted_at' => null])->save();

        // Une invitation jamais acceptée relève d'une autre règle, avec
        // d'autres mots.
        runLightRule();

        expect(EngineEvent::query()->count())->toBe(0);
    });

    it('se taît pendant une pause', function (): void {
        $project = silentProject(10);
        $project->forceFill(['paused_until' => now()->addWeeks(4)])->save();

        runLightRule();

        expect(EngineEvent::query()->count())->toBe(0);
    });

    it('mesure la reprise au prochain enregistrement', function (): void {
        $project = silentProject(10);
        runLightRule();

        $event = EngineEvent::query()->sole();
        $rule = app(NarratorSilence10d::class);

        expect($rule->resumed($event, CarbonImmutable::now()))->toBeNull();

        // La fabrique date l'enregistrement d'il y a six jours : on le ramène
        // à maintenant, sinon il précéderait le déclenchement qu'il est censé
        // conclure.
        Story::factory()->forProject($project)->recorded()->create()
            ->forceFill(['recorded_at' => now()])->save();

        expect($rule->resumed($event, CarbonImmutable::now()))->toBeTrue();
    });
});

describe('vingt-et-un jours de silence', function (): void {
    function runAlertRule(): void
    {
        (new EngineTick([app(NarratorSilence21d::class)]))->run(CarbonImmutable::now());
    }

    it('n’alerte pas avant vingt-et-un jours', function (): void {
        silentProject(20);

        runAlertRule();

        expect(EngineEvent::query()->count())->toBe(0);
    });

    it('alerte l’Initiateur·rice avec quatre gestes possibles, téléphone compris', function (): void {
        $project = silentProject(21);

        runAlertRule();

        $event = EngineEvent::query()->sole();

        // Le quatrième geste, l'option téléphone, n'est proposé que s'il
        // peut être tenu : l'offre est ouverte par défaut (T-137) et sous
        // son plafond.
        expect($event->action_taken['told'])->toBe('initiator')
            ->and($event->action_taken['offered'])
            ->toBe(['resend_whatsapp', 'switch_biweekly', 'ack_call_parent', 'offer_phone_option']);

        Notification::assertSentTo($project->owner, EngineNotification::class);
    });

    it('ne propose pas le téléphone quand l’offre est fermée', function (): void {
        $project = silentProject(21);
        Feature::for($project)->deactivate(PhoneOptionOffer::class);

        runAlertRule();

        expect(EngineEvent::query()->sole()->action_taken['offered'])
            ->toBe(['resend_whatsapp', 'switch_biweekly', 'ack_call_parent']);
    });

    it('émet un jeton d’action par geste proposé', function (): void {
        silentProject(21);

        runAlertRule();

        $tokens = AccessToken::query()->where('type', TokenType::Action->value)->get();

        expect($tokens)->toHaveCount(4)
            ->and($tokens->pluck('scope')->map(fn (array $s): string => $s[1])->sort()->values()->all())
            ->toBe(['ack_call_parent', 'offer_phone_option', 'resend_whatsapp', 'switch_biweekly']);
    });

    it('n’alerte qu’une fois par mois', function (): void {
        silentProject(21);

        runAlertRule();
        $this->travel(20)->days();
        runAlertRule();

        // Au-delà, ce n'est plus une alerte, c'est un rappel de son échec.
        expect(EngineEvent::query()->count())->toBe(1);
    });

    it('se taît pour un projet gelé par un deuil', function (): void {
        $project = silentProject(21);
        $project->forceFill(['status' => ProjectStatus::FrozenBereavement])->save();

        runAlertRule();

        expect(EngineEvent::query()->count())->toBe(0);
        Notification::assertNothingSent();
    });

    it('mesure la reprise dans les trente jours', function (): void {
        $project = silentProject(21);
        runAlertRule();

        $event = EngineEvent::query()->sole();
        $rule = app(NarratorSilence21d::class);

        expect($rule->resumed($event, CarbonImmutable::now()->addDays(31)))->toBeFalse();

        Story::factory()->forProject($project)->recorded()->create()
            ->forceFill(['recorded_at' => now()])->save();

        expect($rule->resumed($event, CarbonImmutable::now()->addDays(31)))->toBeTrue();
    });
});
