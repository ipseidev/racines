<?php

declare(strict_types=1);

use App\Enums\Channel;
use App\Enums\OutboundMessageStatus;
use App\Models\OutboundMessage;
use Illuminate\Testing\TestResponse;
use Twilio\Security\RequestValidator;

const TWILIO_TOKEN = 'jeton-de-test-twilio';

beforeEach(function (): void {
    config()->set('services.twilio.token', TWILIO_TOKEN);
});

function outbound(string $sid = 'SM123'): OutboundMessage
{
    $message = new OutboundMessage([
        'channel' => Channel::Sms,
        'template' => 'prompt',
        'dedupe_key' => 'prompt:'.$sid,
        'provider' => 'twilio',
        'provider_message_id' => $sid,
        'status' => OutboundMessageStatus::Sent,
    ]);

    $message->to_hash = OutboundMessage::hashRecipient('+33600000012');
    $message->to_masked = '+336••••••12';
    $message->save();

    return $message;
}

/** Signe la requête comme le ferait Twilio. */
function twilioPost(array $payload): TestResponse
{
    $url = route('webhooks.twilio.status');
    $signature = (new RequestValidator(TWILIO_TOKEN))->computeSignature($url, $payload);

    return test()->post($url, $payload, ['X-Twilio-Signature' => $signature]);
}

it('met à jour le statut quand la signature est valable', function (string $twilioStatus, OutboundMessageStatus $expected): void {
    $message = outbound();

    twilioPost(['MessageSid' => 'SM123', 'MessageStatus' => $twilioStatus])->assertOk();

    expect($message->refresh()->status)->toBe($expected);
})->with([
    'envoyé' => ['sent', OutboundMessageStatus::Sent],
    'reçu' => ['delivered', OutboundMessageStatus::Delivered],
    'non délivré' => ['undelivered', OutboundMessageStatus::Undelivered],
    'échec' => ['failed', OutboundMessageStatus::Failed],
]);

it('horodate la livraison', function (): void {
    $message = outbound();

    twilioPost(['MessageSid' => 'SM123', 'MessageStatus' => 'delivered'])->assertOk();

    expect($message->refresh()->delivered_at)->not->toBeNull();
});

it('conserve le motif d’un échec pour le support', function (): void {
    $message = outbound();

    twilioPost([
        'MessageSid' => 'SM123',
        'MessageStatus' => 'failed',
        'ErrorMessage' => 'Numéro non attribué',
    ])->assertOk();

    expect($message->refresh()->status_detail)->toBe('Numéro non attribué')
        ->and($message->failed_at)->not->toBeNull();
});

it('ne fait jamais redescendre un message déjà reçu', function (): void {
    $message = outbound();

    twilioPost(['MessageSid' => 'SM123', 'MessageStatus' => 'delivered'])->assertOk();
    twilioPost(['MessageSid' => 'SM123', 'MessageStatus' => 'sent'])->assertOk();

    // Un rappel arrivé dans le désordre ne doit pas faire croire que le SMS
    // n'est plus arrivé : le moteur de complétion s'en servirait pour relancer.
    expect($message->refresh()->status)->toBe(OutboundMessageStatus::Delivered);
});

it('refuse une signature invalide', function (): void {
    $message = outbound();

    $this->post(route('webhooks.twilio.status'), [
        'MessageSid' => 'SM123',
        'MessageStatus' => 'delivered',
    ], ['X-Twilio-Signature' => 'signature-inventée'])->assertForbidden();

    expect($message->refresh()->status)->toBe(OutboundMessageStatus::Sent);
});

it('refuse une requête sans signature', function (): void {
    $this->post(route('webhooks.twilio.status'), ['MessageSid' => 'SM123'])->assertForbidden();
});

it('accepte sans broncher un message inconnu', function (): void {
    twilioPost(['MessageSid' => 'SM-jamais-vu', 'MessageStatus' => 'delivered'])->assertStatus(202);
});

it('accepte sans broncher un statut inconnu', function (): void {
    $message = outbound();

    twilioPost(['MessageSid' => 'SM123', 'MessageStatus' => 'inventé'])->assertStatus(202);

    expect($message->refresh()->status)->toBe(OutboundMessageStatus::Sent);
});
