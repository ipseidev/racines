<?php

declare(strict_types=1);

use App\Services\Sms\FakeSmsSender;
use App\Services\Sms\LogSmsSender;
use App\Services\Sms\SmsSender;

it('lie l’expéditeur au fournisseur configuré', function (string $provider, string $expected): void {
    config()->set('services.sms.provider', $provider);
    app()->forgetInstance(SmsSender::class);

    expect(app(SmsSender::class))->toBeInstanceOf($expected);
})->with([
    'fake' => ['fake', FakeSmsSender::class],
    'log' => ['log', LogSmsSender::class],
]);

it('refuse de se replier silencieusement sur le journal', function (): void {
    config()->set('services.sms.provider', 'twilio');
    app()->forgetInstance(SmsSender::class);

    expect(fn () => app(SmsSender::class))->toThrow(RuntimeException::class);
});

it('n’écrit jamais le numéro complet dans le journal', function (): void {
    expect(LogSmsSender::mask('+33612345678'))->not->toContain('612345678')
        ->and(LogSmsSender::mask('+33612345678'))->toContain('78');
});
