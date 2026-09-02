<?php

declare(strict_types=1);

use App\Models\User;
use App\Support\Auth\Authenticated;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;

it('retourne l’utilisateur authentifié', function (): void {
    $user = new User(['name' => 'Test']);
    $request = Request::create('/');
    $request->setUserResolver(fn (): User => $user);

    expect(Authenticated::user($request))->toBe($user);
});

it('refuse une requête sans utilisateur', function (): void {
    $request = Request::create('/');
    $request->setUserResolver(fn () => null);

    expect(fn () => Authenticated::user($request))
        ->toThrow(AuthenticationException::class);
});
