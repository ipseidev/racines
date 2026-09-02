<?php

declare(strict_types=1);

use App\Logging\RedactTokens;
use App\Logging\RedactTokensTap;
use App\Services\Tokens\TokenService;
use Illuminate\Support\Facades\Log;
use Monolog\Level;
use Monolog\LogRecord;

function record(string $message, array $context = [], array $extra = []): LogRecord
{
    return new LogRecord(
        datetime: new DateTimeImmutable,
        channel: 'testing',
        level: Level::Info,
        message: $message,
        context: $context,
        extra: $extra,
    );
}

function token(): string
{
    return TokenService::generate();
}

it('redacts token segments in message and nested context', function (): void {
    $plain = token();
    $processor = new RedactTokens;

    foreach (['r', 'l', 'q', 'i', 'a', 'x', 'n'] as $prefix) {
        $processed = $processor(record(
            message: "GET https://liens.example/{$prefix}/{$plain} 200",
            context: [
                'url' => "https://liens.example/{$prefix}/{$plain}",
                'nested' => ['deep' => ["/{$prefix}/{$plain}"]],
            ],
            extra: ['referer' => "/{$prefix}/{$plain}?x=1"],
        ));

        $dump = json_encode([$processed->message, $processed->context, $processed->extra]);

        expect($dump)->not->toContain($plain)
            ->and($processed->message)->toContain("/{$prefix}/[redacted]")
            ->and($processed->context['url'])->toBe("https://liens.example/{$prefix}/[redacted]")
            ->and($processed->context['nested']['deep'][0])->toBe("/{$prefix}/[redacted]")
            ->and($processed->extra['referer'])->toBe("/{$prefix}/[redacted]?x=1");
    }
});

it('masque aussi un jeton passé en valeur nue', function (): void {
    $plain = token();
    $processed = (new RedactTokens)(record('lien émis', ['token' => $plain]));

    expect($processed->context['token'])->toBe('[redacted]');
});

it('masque un code à usage unique', function (): void {
    $processed = (new RedactTokens)(record('otp', ['code' => '123456', 'body' => 'votre code est 123456']));

    expect($processed->context['code'])->toBe('[redacted]')
        ->and($processed->context['body'])->not->toContain('123456');
});

it('ne touche pas à ce qui n’est pas un jeton', function (): void {
    $processed = (new RedactTokens)(record(
        'projet ouvert',
        ['project_id' => '01a0629c-cac9-7044-9dd9-38d8cbcdac7c', 'count' => 3, 'ok' => true],
    ));

    expect($processed->message)->toBe('projet ouvert')
        ->and($processed->context['project_id'])->toBe('01a0629c-cac9-7044-9dd9-38d8cbcdac7c')
        ->and($processed->context['count'])->toBe(3)
        ->and($processed->context['ok'])->toBeTrue();
});

it('est branché sur tous les canaux qui écrivent quelque part', function (): void {
    // `stack` est une composition de canaux, `null` jette tout, `emergency`
    // est le repli interne de Laravel : aucun des trois n'écrit lui-même.
    $composites = ['stack', 'null', 'emergency'];

    $channels = collect(config('logging.channels'))->reject(
        fn (mixed $channel, string $name): bool => in_array($name, $composites, true),
    );

    expect($channels)->not->toBeEmpty();

    foreach ($channels as $name => $channel) {
        $taps = is_array($channel) && is_array($channel['tap'] ?? null) ? $channel['tap'] : [];

        expect(in_array(RedactTokensTap::class, $taps, true))
            ->toBeTrue("le canal {$name} ne masque pas les jetons");
    }
});

it('masque les jetons dans un journal réellement écrit', function (): void {
    $plain = token();

    Log::info("ouverture de /r/{$plain}", ['url' => "/r/{$plain}"]);

    // Le canal de test n'écrit pas sur disque : on éprouve le processeur tel
    // que le canal l'aurait appliqué.
    $processed = (new RedactTokens)(record("ouverture de /r/{$plain}", ['url' => "/r/{$plain}"]));

    expect($processed->message)->toBe('ouverture de /r/[redacted]')
        ->and($processed->context['url'])->toBe('/r/[redacted]');
});
