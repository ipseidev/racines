<?php

declare(strict_types=1);

use App\Enums\TokenType;
use App\Settings\BrandSettings;
use App\Support\Links;
use InvalidArgumentException;

beforeEach(function (): void {
    $brand = app(BrandSettings::class);
    $brand->links_domain = 'liens.example';
    $brand->save();
});

it('construit chaque lien sur le domaine court des réglages', function (): void {
    $plain = str_repeat('a', 43);

    expect(Links::record($plain))->toBe("https://liens.example/r/{$plain}")
        ->and(Links::narratorSpace($plain))->toBe("https://liens.example/n/{$plain}")
        ->and(Links::listen($plain))->toBe("https://liens.example/l/{$plain}")
        ->and(Links::qr($plain))->toBe("https://liens.example/q/{$plain}")
        ->and(Links::invitation($plain))->toBe("https://liens.example/i/{$plain}")
        ->and(Links::action($plain))->toBe("https://liens.example/a/{$plain}")
        ->and(Links::export($plain))->toBe("https://liens.example/x/{$plain}");
});

it('suit le domaine court quand il change dans l’administration', function (): void {
    $brand = app(BrandSettings::class);
    $brand->links_domain = 'autre.example';
    $brand->save();

    expect(Links::record(str_repeat('b', 43)))->toStartWith('https://autre.example/r/');
});

it('reprend le schéma et le port de l’application quand le domaine est le sien', function (): void {
    config()->set('app.url', 'http://localhost:8001');

    $brand = app(BrandSettings::class);
    $brand->links_domain = 'localhost';
    $brand->save();

    expect(Links::record(str_repeat('c', 43)))->toStartWith('http://localhost:8001/r/');
});

it('refuse de mettre dans un lien un jeton qui ne voyage pas par lien', function (): void {
    expect(fn () => Links::for(TokenType::SensitiveGrant, str_repeat('d', 43)))
        ->toThrow(InvalidArgumentException::class);
});

it('ne met aucune donnée personnelle dans l’URL', function (): void {
    $plain = str_repeat('e', 43);

    foreach ([Links::record($plain), Links::listen($plain), Links::qr($plain)] as $url) {
        $path = (string) parse_url($url, PHP_URL_PATH);

        // Le chemin ne contient que le préfixe et le jeton : ni identifiant
        // séquentiel, ni nom, ni courriel, ni téléphone (doc 04 §12).
        expect($path)->toMatch('#^/[rnlqiax]/[A-Za-z0-9_-]{43}$#')
            ->and(parse_url($url, PHP_URL_QUERY))->toBeNull();
    }
});
