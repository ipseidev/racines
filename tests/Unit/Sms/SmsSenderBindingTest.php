<?php

declare(strict_types=1);

use App\Services\Sms\FakeSmsSender;
use App\Services\Sms\LogSmsSender;
use App\Services\Sms\SmsSender;
use App\Services\Sms\TwilioSmsSender;

it('lie l’expéditeur au fournisseur configuré', function (string $provider, string $expected): void {
    config()->set('services.sms.provider', $provider);
    app()->forgetInstance(SmsSender::class);

    expect(app(SmsSender::class))->toBeInstanceOf($expected);
})->with([
    'fake' => ['fake', FakeSmsSender::class],
    'log' => ['log', LogSmsSender::class],
]);

it('construit l’expéditeur Twilio quand c’est le fournisseur configuré', function (): void {
    config()->set('services.sms.provider', 'twilio');
    config()->set('services.twilio.sid', 'AC'.str_repeat('0', 32));
    config()->set('services.twilio.token', str_repeat('t', 32));
    app()->forgetInstance(SmsSender::class);

    expect(app(SmsSender::class))->toBeInstanceOf(TwilioSmsSender::class);
});

it('refuse de se replier silencieusement sur le journal', function (): void {
    config()->set('services.sms.provider', 'inventé');
    app()->forgetInstance(SmsSender::class);

    // Un repli silencieux enverrait les codes et les questions dans
    // `storage/logs` au lieu de les envoyer aux narrateurs.
    expect(fn () => app(SmsSender::class))->toThrow(RuntimeException::class);
});

it('n’écrit jamais le numéro complet dans le journal', function (): void {
    expect(LogSmsSender::mask('+33612345678'))->not->toContain('612345678')
        ->and(LogSmsSender::mask('+33612345678'))->toContain('78');
});
