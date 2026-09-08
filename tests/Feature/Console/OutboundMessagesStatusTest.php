<?php

declare(strict_types=1);

use App\Enums\Channel;
use App\Enums\OutboundMessageStatus;
use App\Models\OutboundMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

/*
 * « Je n'ai jamais reçu le SMS. »
 *
 * La phrase qui ne se diagnostiquait pas : `outbound_messages` sait ce que
 * **nous** avons fait — ligne écrite, envoi accepté, identifiant rendu — et
 * rien de ce que l'opérateur en a fait. Cette moitié arrive par le rappel de
 * statut ; s'il n'aboutit pas, la ligne ne quitte jamais `sent` et
 * « accepté » se lit comme « reçu ». Or en France un expéditeur alphanumérique
 * non déposé est jeté par l'opérateur **après** l'acceptation de l'API.
 */
function messageSortant(array $attributs = []): OutboundMessage
{
    $message = new OutboundMessage([
        'channel' => Channel::Sms,
        'template' => 'gift_invitation',
        'dedupe_key' => 'essai:'.Str::random(12),
        ...$attributs,
    ]);

    $message->to_hash = OutboundMessage::hashRecipient('+33612345678');
    $message->to_masked = OutboundMessage::mask('+33612345678');
    $message->save();

    return $message;
}

it('ne dit rien plutôt que d’inventer quand rien n’est parti', function (): void {
    $this->artisan('prod:messages')
        ->expectsOutputToContain('Aucun message sortant')
        ->assertSuccessful();
});

it('signale un message resté à « sent », et nomme le rappel qui manque', function (): void {
    messageSortant()->markSent('SM0123456789', 'twilio');

    /*
     * Le rappel manquant est un défaut à lui seul, pas seulement une gêne de
     * diagnostic : sans lui le moteur de complétion ne distingue pas « lien
     * non ouvert » de « SMS jamais arrivé », et se tait quand il faudrait
     * relancer.
     */
    $this->artisan('prod:messages')
        ->expectsOutputToContain('le rappel de statut n’est pas arrivé')
        ->expectsOutputToContain('/webhooks/twilio/status')
        ->assertSuccessful();
});

it('se tait sur le rappel quand la livraison est confirmée', function (): void {
    $message = messageSortant();
    $message->markSent('SM0123456789', 'twilio');
    $message->forceFill([
        'status' => OutboundMessageStatus::Delivered,
        'delivered_at' => now(),
    ])->save();

    $this->artisan('prod:messages')
        ->doesntExpectOutputToContain('le rappel de statut n’est pas arrivé')
        ->assertSuccessful();
});

it('rend le refus que nous avons enregistré', function (): void {
    messageSortant()->markFailed('21612 The message From/To pair violates a blacklist rule');

    // Une seule attente : `expectsOutputToContain` consomme les lignes dans
    // l'ordre, et les deux moitiés de ce message tiennent sur la même.
    $this->artisan('prod:messages')
        ->expectsOutputToContain('chez nous : 21612')
        ->assertSuccessful();
});

it('n’interroge pas le fournisseur quand il n’y a rien à interroger', function (): void {
    messageSortant()->markSent('SM0123456789', 'twilio');

    // Hors production le fournisseur est `log` : aucun identifiant n'existe
    // chez Twilio, et l'interroger serait une question sur un message qui
    // n'est jamais parti. `--local` le force en plus, pour un serveur pressé.
    $this->artisan('prod:messages', ['--local' => true])
        ->expectsOutputToContain('identifiant : SM0123456789')
        ->assertSuccessful();
});

it('ne garde qu’un gabarit quand on le demande', function (): void {
    messageSortant(['template' => 'gift_invitation']);
    messageSortant(['template' => 'family_invitation']);

    $this->artisan('prod:messages', ['--gabarit' => 'gift_invitation'])
        ->expectsOutputToContain('gift_invitation')
        ->doesntExpectOutputToContain('family_invitation')
        ->assertSuccessful();
});
