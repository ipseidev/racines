<?php

declare(strict_types=1);

use App\Engine\EngineTick;
use App\Engine\Rules\LinkNotOpened;
use App\Enums\Channel;
use App\Enums\EngineRuleId;
use App\Enums\ProjectStatus;
use App\Enums\TokenType;
use App\Models\AccessToken;
use App\Models\EngineEvent;
use App\Models\Narrator;
use App\Models\Project;
use App\Models\Story;
use App\Notifications\Channels\TrackedMailChannel;
use App\Notifications\EngineNotification;
use App\Services\Tokens\TokenService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Notification;

/**
 * Une question posée il y a `$daysAgo` jours, dont le lien n'a jamais servi.
 *
 * @return array{Story, Narrator}
 */
function unopenedPrompt(int $daysAgo, Channel $preferred = Channel::Sms): array
{
    $project = Project::factory()->create(['status' => ProjectStatus::Active]);
    $narrator = Narrator::factory()->primary()->create([
        'project_id' => $project->id,
        'phone_e164' => '+33600000031',
        'email' => 'odette@example.test',
        'preferred_channel' => $preferred,
    ]);

    $story = Story::factory()->proposed()->create([
        'project_id' => $project->id,
        'narrator_id' => $narrator->id,
    ]);

    $issued = app(TokenService::class)->issue(TokenType::Record, $story, ['record']);
    $issued->token->forceFill(['created_at' => now()->subDays($daysAgo)])->save();

    return [$story->refresh(), $narrator];
}

function runLinkRule(): void
{
    (new EngineTick([app(LinkNotOpened::class)]))->run(CarbonImmutable::now());
}

beforeEach(function (): void {
    Notification::fake();
});

it('ne renvoie rien avant le troisième jour', function (): void {
    unopenedPrompt(2);

    runLinkRule();

    expect(EngineEvent::query()->count())->toBe(0);
    Notification::assertNothingSent();
});

it('renvoie au troisième jour', function (): void {
    [, $narrator] = unopenedPrompt(3);

    runLinkRule();

    expect(EngineEvent::query()->sole()->rule_id)->toBe(EngineRuleId::LinkNotOpened);
    Notification::assertSentTo($narrator, EngineNotification::class);
});

it('renvoie sur l’autre canal', function (): void {
    [, $narrator] = unopenedPrompt(3, Channel::Sms);

    runLinkRule();

    // Un SMS qui n'a pas été vu ne sera pas plus vu au deuxième envoi : le
    // courriel atterrit ailleurs, se lit sur un autre écran.
    expect(EngineEvent::query()->sole()->action_taken['channel'])->toBe('email');

    Notification::assertSentTo(
        $narrator,
        EngineNotification::class,
        fn (EngineNotification $n): bool => $n->via($narrator) === [TrackedMailChannel::class],
    );
});

it('reste sur le même canal quand il n’y en a pas d’autre', function (): void {
    [, $narrator] = unopenedPrompt(3, Channel::Sms);
    $narrator->forceFill(['email' => null])->save();

    runLinkRule();

    // Mieux vaut un second SMS que rien : c'est la limite d'un renvoi par
    // question qui borne l'insistance, pas le canal.
    expect(EngineEvent::query()->sole()->action_taken['channel'])->toBe('sms');
});

it('émet un lien neuf, pas celui d’origine', function (): void {
    [$story] = unopenedPrompt(3);
    $before = AccessToken::query()->where('subject_id', $story->id)->count();

    runLinkRule();

    // Le lien d'origine a pu expirer, et un narrateur n'a pas à comprendre
    // pourquoi celui de mardi ne marche plus.
    expect(AccessToken::query()->where('subject_id', $story->id)->count())->toBe($before + 1);
});

it('ne renvoie qu’une fois par question', function (): void {
    unopenedPrompt(3);

    runLinkRule();
    $this->travel(3)->days();
    runLinkRule();

    expect(EngineEvent::query()->count())->toBe(1);
    Notification::assertCount(1);
});

it('se taît dès que le lien a été ouvert', function (): void {
    [$story] = unopenedPrompt(3);
    AccessToken::query()->where('subject_id', $story->id)->update(['use_count' => 1]);

    runLinkRule();

    expect(EngineEvent::query()->count())->toBe(0);
});

it('se taît pendant une pause', function (): void {
    [$story] = unopenedPrompt(3);
    $story->project->forceFill(['paused_until' => now()->addWeeks(2)])->save();

    runLinkRule();

    expect(EngineEvent::query()->count())->toBe(0);
    Notification::assertNothingSent();
});

it('se taît pour un projet gelé', function (): void {
    [$story] = unopenedPrompt(3);
    $story->project->forceFill(['status' => ProjectStatus::FrozenBereavement])->save();

    runLinkRule();

    expect(EngineEvent::query()->count())->toBe(0);
});

it('mesure la reprise à l’ouverture du lien', function (): void {
    [$story] = unopenedPrompt(3);
    runLinkRule();

    $event = EngineEvent::query()->sole();
    $rule = app(LinkNotOpened::class);

    expect($rule->resumed($event, CarbonImmutable::now()))->toBeNull();

    AccessToken::query()->where('subject_id', $story->id)->update(['use_count' => 1]);

    expect($rule->resumed($event, CarbonImmutable::now()))->toBeTrue()
        ->and($rule->resumed($event, CarbonImmutable::now()->addDays(8)))->toBeTrue();
});

it('conclut à l’absence d’effet après sept jours', function (): void {
    unopenedPrompt(3);
    runLinkRule();

    $event = EngineEvent::query()->sole();

    expect(app(LinkNotOpened::class)->resumed($event, CarbonImmutable::now()->addDays(8)))->toBeFalse();
});
