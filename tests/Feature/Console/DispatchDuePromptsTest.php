<?php

declare(strict_types=1);

use App\Enums\Channel;
use App\Enums\OutboundMessageStatus;
use App\Enums\ProjectStatus;
use App\Enums\TokenType;
use App\Models\AccessToken;
use App\Models\Narrator;
use App\Models\OutboundMessage;
use App\Models\Project;
use App\Models\Question;
use App\Models\Story;
use App\Notifications\PromptNotification;
use Illuminate\Support\Facades\Mail;

function dueProject(array $projectAttributes = [], array $narratorAttributes = []): Project
{
    $project = Project::factory()->create([
        'status' => ProjectStatus::Active,
        'next_prompt_at' => now()->subMinute(),
        ...$projectAttributes,
    ]);

    Narrator::factory()->primary()->create([
        'project_id' => $project->id,
        'first_name' => 'Odette',
        'phone_e164' => '+33600000012',
        'preferred_channel' => Channel::Sms,
        ...$narratorAttributes,
    ]);

    return $project->refresh();
}

it('crée une histoire, émet un lien et envoie sur le canal choisi', function (): void {
    $sender = fakeSms();
    $project = dueProject();

    $this->artisan('prompts:dispatch-due')->assertSuccessful();

    $story = Story::query()->where('project_id', $project->id)->sole();

    expect($story->state->getValue())->toBe('proposed')
        ->and($story->question)->not->toBeNull()
        ->and($story->sequence)->toBe(1);

    $token = AccessToken::query()->where('subject_id', $story->id)->sole();

    expect($token->type)->toBe(TokenType::Record)
        ->and($token->scope)->toBe(['record', 'decide_share']);

    $messages = $sender->messages();

    expect($messages)->toHaveCount(1)
        ->and($messages[0]->to)->toBe('+33600000012')
        ->and($messages[0]->body)->toContain('Odette')
        ->and($messages[0]->body)->toContain('/r/');

    $outbound = OutboundMessage::query()->sole();

    expect($outbound->channel)->toBe(Channel::Sms)
        ->and($outbound->template)->toBe('prompt')
        ->and($outbound->status)->toBe(OutboundMessageStatus::Sent)
        ->and($outbound->dedupe_key)->toBe("prompt:{$story->id}:sms")
        ->and($outbound->to_masked)->not->toContain('600000012')
        ->and($outbound->to_hash)->toHaveLength(64);
});

it('envoie sur les deux canaux quand le narrateur les a choisis tous les deux', function (): void {
    $sender = fakeSms();
    Mail::fake();

    dueProject(narratorAttributes: [
        'preferred_channel' => Channel::Both,
        'email' => 'odette@example.test',
    ]);

    $this->artisan('prompts:dispatch-due')->assertSuccessful();

    expect($sender->messages())->toHaveCount(1)
        ->and(OutboundMessage::query()->count())->toBe(2)
        ->and(OutboundMessage::query()->pluck('channel')->map(fn ($c) => $c->value)->sort()->values()->all())
        ->toBe(['email', 'sms']);
});

it('ne renvoie jamais deux fois le message d’une même histoire', function (): void {
    $sender = fakeSms();
    dueProject();

    $this->artisan('prompts:dispatch-due')->assertSuccessful();

    $story = Story::query()->sole();

    // Rejouer exactement la même notification : elle est déjà partie.
    $story->narrator->notify(new PromptNotification($story, 'peu-importe'));

    expect($sender->messages())->toHaveCount(1)
        ->and(OutboundMessage::query()->count())->toBe(1);
});

it('replanifie l’échéance après l’envoi', function (): void {
    fakeSms();
    $project = dueProject(['prompt_day' => 3]);

    $before = $project->next_prompt_at;

    $this->artisan('prompts:dispatch-due')->assertSuccessful();

    $after = $project->refresh()->next_prompt_at;

    expect($after)->not->toBeNull()
        ->and($after->greaterThan($before))->toBeTrue()
        ->and($after->setTimezone('Europe/Paris')->dayOfWeekIso)->toBe(3);
});

it('ignore les projets en pause, gelés, terminés ou en brouillon', function (ProjectStatus $status): void {
    $sender = fakeSms();
    dueProject(['status' => $status]);

    $this->artisan('prompts:dispatch-due')->assertSuccessful();

    expect($sender->messages())->toBeEmpty()
        ->and(Story::query()->count())->toBe(0);
})->with([
    'en pause' => ProjectStatus::Paused,
    'gelé' => ProjectStatus::FrozenBereavement,
    'terminé' => ProjectStatus::Completed,
    'brouillon' => ProjectStatus::Draft,
]);

it('ignore un projet dont l’échéance est dans le futur', function (): void {
    $sender = fakeSms();
    dueProject(['next_prompt_at' => now()->addDay()]);

    $this->artisan('prompts:dispatch-due')->assertSuccessful();

    expect($sender->messages())->toBeEmpty();
});

it('prévient l’Initiateur·rice une seule fois quand le corpus est épuisé', function (): void {
    fakeSms();
    Mail::fake();

    $project = dueProject();

    foreach (Question::query()->get() as $question) {
        Story::factory()->forProject($project)->create([
            'question_id' => $question->id,
            'state' => 'validated',
        ]);
    }

    $this->artisan('prompts:dispatch-due')->assertSuccessful();

    expect($project->refresh()->next_prompt_at)->toBeNull()
        ->and(OutboundMessage::query()->where('template', 'corpus_exhausted')->count())->toBe(1);

    $project->next_prompt_at = now()->subMinute();
    $project->save();

    $this->artisan('prompts:dispatch-due')->assertSuccessful();

    expect(OutboundMessage::query()->where('template', 'corpus_exhausted')->count())->toBe(1);
});

it('ne casse pas sur un projet sans narrateur principal', function (): void {
    $sender = fakeSms();

    Project::factory()->create([
        'status' => ProjectStatus::Active,
        'next_prompt_at' => now()->subMinute(),
    ]);

    $this->artisan('prompts:dispatch-due')->assertSuccessful();

    expect($sender->messages())->toBeEmpty();
});

it('n’envoie pas de SMS à un narrateur qui n’a qu’un courriel', function (): void {
    $sender = fakeSms();
    Mail::fake();

    dueProject(narratorAttributes: [
        'preferred_channel' => Channel::Email,
        'email' => 'odette@example.test',
        'phone_e164' => null,
    ]);

    $this->artisan('prompts:dispatch-due')->assertSuccessful();

    expect($sender->messages())->toBeEmpty()
        ->and(OutboundMessage::query()->sole()->channel)->toBe(Channel::Email);
});
