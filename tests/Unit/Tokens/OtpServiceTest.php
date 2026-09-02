<?php

declare(strict_types=1);

use App\Enums\Channel;
use App\Enums\OtpPurpose;
use App\Enums\TokenType;
use App\Exceptions\Domain\OtpExpired;
use App\Exceptions\Domain\OtpInvalid;
use App\Exceptions\Domain\OtpLocked;
use App\Exceptions\Domain\OtpNotDeliverable;
use App\Exceptions\Domain\OtpThrottled;
use App\Models\Narrator;
use App\Models\OtpChallenge;
use App\Notifications\OtpCodeNotification;
use App\Services\Tokens\OtpService;
use Database\Factories\OtpChallengeFactory;
use Illuminate\Support\Facades\Notification;

function otp(): OtpService
{
    return app(OtpService::class);
}

it('creates a 6 digit challenge hashed in database and sends it on the chosen channel', function (): void {
    $sms = fakeSms();
    $narrator = Narrator::factory()->primary()->create(['phone_e164' => '+33600000012']);

    $challenge = otp()->challenge($narrator, OtpPurpose::SensitiveAct, Channel::Sms);

    expect($challenge->code_hash)->toHaveLength(64)
        ->and($challenge->narrator_id)->toBe($narrator->id)
        ->and($challenge->purpose)->toBe(OtpPurpose::SensitiveAct)
        ->and($challenge->attempts)->toBe(0)
        ->and($challenge->expires_at->getTimestamp())->toBeGreaterThan(now()->getTimestamp());

    $sent = $sms->messages();

    expect($sent)->toHaveCount(1)
        ->and($sent[0]->to)->toBe('+33600000012');

    preg_match('/\b(\d{6})\b/', $sent[0]->body, $matches);

    expect($matches[1] ?? null)->not->toBeNull()
        // Le code n'est pas stocké : seule son empreinte, salée par le défi.
        ->and($challenge->code_hash)->toBe(OtpService::hashCode($matches[1], $challenge->id))
        ->and($challenge->code_hash)->not->toContain($matches[1]);
});

it('ne conserve du destinataire qu’une forme masquée', function (): void {
    fakeSms();
    $narrator = Narrator::factory()->primary()->create(['phone_e164' => '+33600000012']);

    $challenge = otp()->challenge($narrator, OtpPurpose::SensitiveAct, Channel::Sms);

    expect($challenge->sent_to_masked)->not->toContain('600000012')
        ->and($challenge->sent_to_masked)->toContain('12');
});

it('envoie le code par courriel quand c’est le canal choisi', function (): void {
    Notification::fake();
    $narrator = Narrator::factory()->primary()->byEmail()->create();

    otp()->challenge($narrator, OtpPurpose::NarratorSpace, Channel::Email);

    Notification::assertSentTo($narrator, OtpCodeNotification::class);
});

it('refuse d’envoyer un code sur un canal dont le narrateur n’a pas les coordonnées', function (): void {
    fakeSms();
    $narrator = Narrator::factory()->primary()->byEmail()->create();

    expect(fn () => otp()->challenge($narrator, OtpPurpose::SensitiveAct, Channel::Sms))
        ->toThrow(OtpNotDeliverable::class);
});

it('verifies a correct code once and issues a sensitive_grant token', function (): void {
    $challenge = OtpChallenge::factory()->create();

    $issued = otp()->verify($challenge, OtpChallengeFactory::CODE);

    expect($issued->token->type)->toBe(TokenType::SensitiveGrant)
        ->and($issued->token->single_use)->toBeTrue()
        ->and($issued->plain)->toHaveLength(43)
        ->and($challenge->refresh()->verified_at)->not->toBeNull();

    // Un défi vérifié ne se rejoue pas.
    expect(fn () => otp()->verify($challenge, OtpChallengeFactory::CODE))->toThrow(OtpInvalid::class);
});

it('émet un jeton d’espace narrateur quand c’est l’objet du défi', function (): void {
    $challenge = OtpChallenge::factory()->forNarratorSpace()->create();

    $issued = otp()->verify($challenge, OtpChallengeFactory::CODE);

    expect($issued->token->type)->toBe(TokenType::NarratorSpace)
        ->and($issued->token->single_use)->toBeFalse()
        ->and($issued->token->subject_id)->toBe($challenge->narrator_id);
});

it('refuses a wrong code and counts the attempt', function (): void {
    $challenge = OtpChallenge::factory()->create();

    expect(fn () => otp()->verify($challenge, '000000'))->toThrow(OtpInvalid::class);

    expect($challenge->refresh()->attempts)->toBe(1)
        ->and($challenge->verified_at)->toBeNull();
});

it('locks after 5 attempts for 15 minutes', function (): void {
    $this->freezeTime();

    $challenge = OtpChallenge::factory()->create();

    for ($i = 0; $i < 5; $i++) {
        expect(fn () => otp()->verify($challenge, '000000'))->toThrow(OtpInvalid::class);
    }

    $challenge->refresh();

    expect($challenge->attempts)->toBe(5)
        ->and($challenge->locked_until?->getTimestamp())->toBe(now()->addMinutes(15)->getTimestamp());

    // Même le bon code est refusé pendant le verrou.
    expect(fn () => otp()->verify($challenge, OtpChallengeFactory::CODE))->toThrow(OtpLocked::class);

    $this->travel(16)->minutes();

    // Le verrou (15 min) survit au code (10 min) : passé le verrou, il faut
    // toujours un nouveau code. C'est voulu — c'est ce que dit la page.
    expect(fn () => otp()->verify($challenge->refresh(), OtpChallengeFactory::CODE))
        ->toThrow(OtpExpired::class);
});

it('refuses an expired challenge', function (): void {
    $challenge = OtpChallenge::factory()->expired()->create();

    expect(fn () => otp()->verify($challenge, OtpChallengeFactory::CODE))->toThrow(OtpExpired::class);
});

it('rate limits challenge creation to 3 per hour per subject', function (): void {
    fakeSms();
    $narrator = Narrator::factory()->primary()->create();

    for ($i = 0; $i < 3; $i++) {
        otp()->challenge($narrator, OtpPurpose::SensitiveAct, Channel::Sms);
    }

    expect(fn () => otp()->challenge($narrator, OtpPurpose::SensitiveAct, Channel::Sms))
        ->toThrow(OtpThrottled::class);

    $this->travel(61)->minutes();

    expect(otp()->challenge($narrator, OtpPurpose::SensitiveAct, Channel::Sms))
        ->toBeInstanceOf(OtpChallenge::class);
});

it('périme les défis précédents quand un nouveau code est demandé', function (): void {
    fakeSms();
    $narrator = Narrator::factory()->primary()->create();

    $first = otp()->challenge($narrator, OtpPurpose::SensitiveAct, Channel::Sms);
    otp()->challenge($narrator, OtpPurpose::SensitiveAct, Channel::Sms);

    expect($first->refresh()->isExpired())->toBeTrue();
});

it('envoie un seul code, par SMS, à un narrateur qui a choisi les deux canaux', function (): void {
    $sms = fakeSms();
    $narrator = Narrator::factory()->primary()->create([
        'preferred_channel' => Channel::Both,
        'email' => 'odette@example.test',
        'phone_e164' => '+33600000012',
    ]);

    expect(OtpService::channelFor($narrator))->toBe(Channel::Sms);

    // Deux codes valides en même temps doubleraient la surface d'attaque.
    $challenge = otp()->challenge($narrator, OtpPurpose::SensitiveAct, OtpService::channelFor($narrator));

    expect($challenge->channel)->toBe(Channel::Sms)
        ->and($sms->messages())->toHaveCount(1);
});

it('retombe sur le courriel quand le narrateur veut les deux mais n’a pas de téléphone', function (): void {
    $narrator = Narrator::factory()->primary()->byEmail()->create(['preferred_channel' => Channel::Both]);

    expect(OtpService::channelFor($narrator))->toBe(Channel::Email);
});
