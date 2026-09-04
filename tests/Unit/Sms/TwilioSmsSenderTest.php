<?php

declare(strict_types=1);

use App\Services\Sms\TwilioSmsSender;
use App\Settings\BrandSettings;
use Twilio\Exceptions\TwilioException;
use Twilio\Rest\Api\V2010\Account\MessageInstance;
use Twilio\Rest\Api\V2010\Account\MessageList;
use Twilio\Rest\Client;

/**
 * Le client Twilio est doublé : aucun test n'appelle le réseau (convention §5).
 */
function twilioDouble(array &$captured, ?TwilioException $throws = null): Client
{
    $messages = Mockery::mock(MessageList::class);

    if ($throws instanceof TwilioException) {
        $messages->shouldReceive('create')->andThrow($throws);
    } else {
        $messages->shouldReceive('create')
            ->andReturnUsing(function (string $to, array $options) use (&$captured): MessageInstance {
                $captured = ['to' => $to, 'options' => $options];

                $instance = Mockery::mock(MessageInstance::class);
                $instance->sid = 'SM-abc';

                return $instance;
            });
    }

    $client = Mockery::mock(Client::class);
    $client->messages = $messages;

    return $client;
}

beforeEach(function (): void {
    $brand = app(BrandSettings::class);
    $brand->sms_sender_id = 'NARRAEX';
    $brand->save();

    config()->set('services.twilio.from', '+33600000000');
});

it('construit la requête attendue et transmet le rappel de statut', function (): void {
    $captured = [];
    $sender = new TwilioSmsSender(twilioDouble($captured), 'https://exemple.test/webhooks/twilio/status');

    $result = $sender->send('+33612345678', 'Bonjour Odette');

    expect($result->accepted)->toBeTrue()
        ->and($result->providerMessageId)->toBe('SM-abc')
        ->and($captured['to'])->toBe('+33612345678')
        ->and($captured['options']['body'])->toBe('Bonjour Odette')
        // Sans rappel de statut, « envoyé » et « reçu » se confondent.
        ->and($captured['options']['statusCallback'])->toBe('https://exemple.test/webhooks/twilio/status');
});

it('signe du nom de la marque là où l’opérateur l’accepte', function (string $number): void {
    $captured = [];
    $sender = new TwilioSmsSender(twilioDouble($captured));

    expect($sender->senderFor($number))->toBe('NARRAEX');
})->with([
    'France' => '+33612345678',
    'Belgique' => '+32470123456',
    'Suisse' => '+41791234567',
    'Luxembourg' => '+352621123456',
    'Guadeloupe' => '+590690123456',
]);

it('retombe sur un numéro constant là où l’alphanumérique est interdit', function (string $number): void {
    $captured = [];
    $sender = new TwilioSmsSender(twilioDouble($captured));

    // L'engagement du doc 04 §9 devient « numéro constant » plutôt que
    // « expéditeur constant », mais il reste tenu : le narrateur reconnaît
    // toujours l'origine du message.
    expect($sender->senderFor($number))->toBe('+33600000000');
})->with([
    'États-Unis' => '+15551234567',
    'Royaume-Uni' => '+447700900123',
    'Allemagne' => '+4915123456789',
]);

it('retombe sur le numéro quand aucun expéditeur de marque n’est réglé', function (): void {
    $brand = app(BrandSettings::class);
    $brand->sms_sender_id = '';
    $brand->save();

    $captured = [];

    expect((new TwilioSmsSender(twilioDouble($captured)))->senderFor('+33612345678'))
        ->toBe('+33600000000');
});

it('rapporte un refus de l’opérateur sans lever', function (): void {
    $captured = [];
    $sender = new TwilioSmsSender(twilioDouble($captured, new TwilioException('Numéro non attribué')));

    $result = $sender->send('+33612345678', 'Bonjour');

    // Un refus est une information, pas un plantage : la ligne
    // `outbound_messages` doit pouvoir l'enregistrer.
    expect($result->accepted)->toBeFalse()
        ->and($result->error)->toContain('Numéro non attribué');
});

it('n’ajoute pas de rappel de statut quand aucune URL n’est fournie', function (): void {
    $captured = [];
    $sender = new TwilioSmsSender(twilioDouble($captured));

    $sender->send('+33612345678', 'Bonjour');

    expect($captured['options'])->not->toHaveKey('statusCallback');
});
