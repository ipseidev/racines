<?php

declare(strict_types=1);

use App\Enums\OutboundMessageStatus;
use App\Models\FamilyMember;
use App\Models\Narrator;
use App\Models\OutboundMessage;
use App\Services\Sms\SmsResult;
use App\Services\Sms\SmsSender;

/*
 * Cette commande écrit à une personne, et en production rien ne la retient :
 * `AllowlistSmsSender` n'y est pas monté. Ce qu'on vérifie ici, ce sont donc
 * ses refus — un numéro mal formé, un fournisseur en double, le téléphone d'un
 * client — bien avant le chemin heureux.
 */

beforeEach(function (): void {
    // Sans ça la commande refuse de tourner, et c'est le premier test.
    config()->set('services.sms.provider', 'twilio');
});

it('refuse un numéro qui n’est pas au format international', function (): void {
    $sms = fakeSms();

    $this->artisan('prod:sms', ['destinataire' => '0638503252', '--force' => true])
        ->expectsOutputToContain('format international')
        ->assertFailed();

    $sms->assertNothingSent();
});

it('accepte un numéro recopié avec des espaces ou un 00', function (): void {
    $sms = fakeSms();

    $this->artisan('prod:sms', ['destinataire' => '00 33 6 38 50 32 52', '--force' => true, '--attendre' => 0])
        ->assertSuccessful();

    $sms->assertSentTo('+33638503252');
});

it('demande le numéro plutôt que de le reprocher', function (): void {
    $sms = fakeSms();

    // Sans cela, Symfony répond « Not enough arguments » : en anglais, et sur
    // un serveur où l'on tape de mémoire.
    $this->artisan('prod:sms', ['--force' => true, '--attendre' => 0])
        ->expectsQuestion('À quel numéro ?', '+33638503252')
        ->assertSuccessful();

    $sms->assertSentTo('+33638503252');
});

it('refuse de tourner quand le fournisseur n’est pas Twilio', function (): void {
    config()->set('services.sms.provider', 'log');
    $sms = fakeSms();

    $this->artisan('prod:sms', ['destinataire' => '+33638503252', '--force' => true])
        ->expectsOutputToContain('ne prouverait rien')
        ->assertFailed();

    $sms->assertNothingSent();
});

it('refuse d’écrire au téléphone d’un narrateur', function (): void {
    $sms = fakeSms();
    Narrator::factory()->create(['phone_e164' => '+33612345678']);

    $this->artisan('prod:sms', ['destinataire' => '+33612345678'])
        ->expectsOutputToContain('On ne fait pas ses essais sur un client')
        ->assertFailed();

    $sms->assertNothingSent();
});

it('refuse d’écrire au téléphone d’un proche', function (): void {
    $sms = fakeSms();
    FamilyMember::factory()->create(['phone_e164' => '+33612345678']);

    $this->artisan('prod:sms', ['destinataire' => '+33612345678'])
        ->expectsOutputToContain('On ne fait pas ses essais sur un client')
        ->assertFailed();

    $sms->assertNothingSent();
});

it('n’envoie rien tant que la confirmation n’est pas donnée', function (): void {
    $sms = fakeSms();

    $this->artisan('prod:sms', ['destinataire' => '+33638503252'])
        ->expectsConfirmation('Envoyer ce SMS au +33638503252, pour de vrai ?', 'no')
        ->expectsOutputToContain('Rien n’est parti')
        ->assertSuccessful();

    $sms->assertNothingSent();
});

it('annonce la longueur et les segments avant d’envoyer', function (): void {
    fakeSms();

    $this->artisan('prod:sms', [
        'destinataire' => '+33638503252',
        '--corps' => 'Françoise, votre question de la semaine vous attend : https://narrae.fr/r/'.str_repeat('a', 43),
        '--force' => true,
        '--attendre' => 0,
    ])
        // « ç » sort de l'alphabet GSM : la limite tombe de 160 à 70, et ce
        // message de 117 caractères passe d'un segment à deux.
        //
        // Une seule attente pour toute la ligne : Mockery ne consomme qu'une
        // attente par écriture, et trois attentes sur la même ligne ne
        // peuvent pas toutes être satisfaites.
        ->expectsOutputToContain('117 caractères, UCS-2, 2 segment(s)')
        ->expectsOutputToContain('ramène la limite de 160 à 70')
        ->assertSuccessful();
});

it('laisse une trace que le rappel de statut pourra retrouver', function (): void {
    $sms = fakeSms();

    $this->artisan('prod:sms', ['destinataire' => '+33638503252', '--force' => true, '--attendre' => 0])
        ->assertSuccessful();

    $sms->assertSentTo('+33638503252', 'message d’essai technique');

    $message = OutboundMessage::query()->where('template', 'prod_sms_essai')->sole();

    // C'est `provider_message_id` que le webhook interroge : sans lui, la
    // livraison ne peut pas être rattachée à cet envoi.
    expect($message->status)->toBe(OutboundMessageStatus::Sent)
        ->and($message->provider_message_id)->not->toBeNull()
        ->and($message->to_masked)->not->toContain('638503252')
        ->and($message->project_id)->toBeNull();
});

it('dit quoi faire quand le jeton d’authentification est refusé', function (): void {
    app()->instance(SmsSender::class, new class implements SmsSender
    {
        public function send(string $toE164, string $body, ?string $dedupeKey = null): SmsResult
        {
            return SmsResult::refused('[HTTP 401] Unable to create record: Authenticate (20003)');
        }
    });

    $this->artisan('prod:sms', ['destinataire' => '+33638503252', '--force' => true, '--attendre' => 0])
        ->expectsOutputToContain('n’est pas valide pour ce compte')
        ->assertFailed();

    $message = OutboundMessage::query()->where('template', 'prod_sms_essai')->sole();

    expect($message->status)->toBe(OutboundMessageStatus::Failed);
});
