<?php

declare(strict_types=1);

use App\Enums\Channel;
use App\Enums\OutboundMessageStatus;
use App\Models\OutboundMessage;
use Illuminate\Testing\TestResponse;

const RESEND_SECRET = 'whsec_'.'dGVzdC1zZWNyZXQtcmVzZW5kLXBvdXItbGVz';

beforeEach(function (): void {
    config()->set('services.resend.webhook_secret', RESEND_SECRET);
});

function mailMessage(string $providerId = 're_123'): OutboundMessage
{
    $message = new OutboundMessage([
        'channel' => Channel::Email,
        'template' => 'prompt',
        'dedupe_key' => 'prompt:'.$providerId,
        'provider' => 'resend',
        'provider_message_id' => $providerId,
        'status' => OutboundMessageStatus::Sent,
    ]);

    $message->to_hash = OutboundMessage::hashRecipient('odette@example.test');
    $message->to_masked = 'o•••••e@example.test';
    $message->save();

    return $message;
}

/** Signe la requête comme le ferait Svix pour Resend. */
function resendPost(array $payload): TestResponse
{
    $body = (string) json_encode($payload);
    $id = 'msg_test';
    $timestamp = (string) time();

    $secret = base64_decode(substr(RESEND_SECRET, 6), true);
    $signature = base64_encode(hash_hmac('sha256', "{$id}.{$timestamp}.{$body}", (string) $secret, true));

    return test()->call('POST', route('webhooks.resend'), [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_SVIX_ID' => $id,
        'HTTP_SVIX_TIMESTAMP' => $timestamp,
        'HTTP_SVIX_SIGNATURE' => 'v1,'.$signature,
    ], $body);
}

it('met à jour le statut quand la signature Svix est valable', function (string $event, OutboundMessageStatus $expected): void {
    $message = mailMessage();

    resendPost(['type' => $event, 'data' => ['email_id' => 're_123']])->assertOk();

    expect($message->refresh()->status)->toBe($expected);
})->with([
    'reçu' => ['email.delivered', OutboundMessageStatus::Delivered],
    'rejeté' => ['email.bounced', OutboundMessageStatus::Bounced],
    'signalé comme indésirable' => ['email.complained', OutboundMessageStatus::Bounced],
]);

it('refuse une signature invalide', function (): void {
    $message = mailMessage();

    $this->postJson(route('webhooks.resend'), [
        'type' => 'email.delivered',
        'data' => ['email_id' => 're_123'],
    ], [
        'svix-id' => 'msg_test',
        'svix-timestamp' => (string) time(),
        'svix-signature' => 'v1,signature-inventée',
    ])->assertForbidden();

    expect($message->refresh()->status)->toBe(OutboundMessageStatus::Sent);
});

it('refuse quand aucun secret n’est configuré', function (): void {
    config()->set('services.resend.webhook_secret', '');

    $this->postJson(route('webhooks.resend'), ['type' => 'email.delivered'])->assertForbidden();
});

it('accepte sans broncher un courriel inconnu', function (): void {
    resendPost(['type' => 'email.delivered', 'data' => ['email_id' => 're_jamais_vu']])->assertStatus(202);
});

it('accepte sans broncher un événement inconnu', function (): void {
    $message = mailMessage();

    resendPost(['type' => 'email.inventé', 'data' => ['email_id' => 're_123']])->assertStatus(202);

    expect($message->refresh()->status)->toBe(OutboundMessageStatus::Sent);
});
