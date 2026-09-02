<?php

declare(strict_types=1);

use App\Enums\Channel;
use App\Enums\TokenType;
use App\Models\Story;
use App\Notifications\PromptNotification;
use App\Services\Sms\FakeSmsSender;
use App\Services\Sms\SmsSender;
use App\Services\Tokens\TokenService;
use App\Settings\BrandSettings;
use App\Support\SmsLength;

function promptFor(array $narratorAttributes = []): array
{
    $story = Story::factory()->proposed()->create();

    $narrator = $story->narrator;
    $narrator->fill($narratorAttributes);
    $narrator->save();

    $issued = app(TokenService::class)->issue(TokenType::Record, $story);

    return [new PromptNotification($story, $issued->plain), $narrator, $story, $issued->plain];
}

it('écrit un SMS qui tient dans un seul segment, lien en dernier', function (): void {
    [$notification, $narrator] = promptFor(['first_name' => 'Odette']);

    $body = $notification->toSms($narrator);

    expect(SmsLength::exceedsSingleSegment($body))->toBeFalse()
        ->and($body)->toContain('Odette')
        // Le lien termine le message : c'est la seule position où un client
        // SMS ne le tronque pas.
        ->and($body)->toEndWith($notification->link());
});

it('raccourcit le prénom plutôt que de sacrifier le lien', function (): void {
    // Un prénom qui, seul, fait déborder le segment. Le contrat est clair :
    // on rogne le prénom, jamais le lien — un SMS sans lien ne sert à rien.
    [$notification, $narrator] = promptFor([
        'first_name' => 'Marie-Christine '.str_repeat('Bernadette ', 12),
    ]);

    $body = $notification->toSms($narrator);

    expect($body)->toEndWith($notification->link())
        ->and($body)->toContain('Marie')
        ->and($body)->not->toContain('Bernadette');
});

it('laisse un prénom normal intact', function (): void {
    [$notification, $narrator] = promptFor(['first_name' => 'Marie-Christine']);

    expect($notification->toSms($narrator))->toContain('Marie-Christine');
});

it('n’emprunte jamais de raccourcisseur : le lien part du domaine annoncé', function (): void {
    $brand = app(BrandSettings::class);
    $brand->links_domain = 'liens.example';
    $brand->save();

    [$notification, $narrator] = promptFor();

    expect($notification->link())->toStartWith('https://liens.example/r/')
        ->and($notification->toSms($narrator))->toContain('https://liens.example/r/');
});

it('nomme la marque dans le SMS, pour qu’on reconnaisse l’expéditeur', function (): void {
    $brand = app(BrandSettings::class);
    $brand->short_name = 'Souvenirs';
    $brand->save();

    [$notification, $narrator] = promptFor();

    expect($notification->toSms($narrator))->toContain('Souvenirs');
});

it('choisit les canaux d’après la préférence du narrateur', function (Channel $preference, array $expected): void {
    [$notification, $narrator] = promptFor([
        'preferred_channel' => $preference,
        'email' => 'odette@example.test',
        'phone_e164' => '+33600000012',
    ]);

    $channels = array_map(
        fn (string $class): string => class_basename($class),
        $notification->via($narrator),
    );

    expect($channels)->toBe($expected);
})->with([
    'SMS seul' => [Channel::Sms, ['SmsChannel']],
    'courriel seul' => [Channel::Email, ['TrackedMailChannel']],
    'les deux' => [Channel::Both, ['SmsChannel', 'TrackedMailChannel']],
]);

it('n’essaie pas un canal dont le narrateur n’a pas les coordonnées', function (): void {
    [$notification, $narrator] = promptFor([
        'preferred_channel' => Channel::Both,
        'email' => null,
        'phone_e164' => '+33600000012',
    ]);

    expect(array_map(fn ($c) => class_basename($c), $notification->via($narrator)))->toBe(['SmsChannel']);
});

it('écrit un courriel de marque avec la question et un seul bouton', function (): void {
    [$notification, $narrator, $story] = promptFor(['email' => 'odette@example.test']);

    $rendered = (string) $notification->toMail($narrator)->render();

    expect($rendered)->toContain((string) $story->questionText())
        ->and($rendered)->toContain($notification->link())
        // Un rappel anti-hameçonnage en clair (doc 04 §9).
        ->and($rendered)->toContain('jamais de mot de passe')
        // Un seul bouton : une seule action possible.
        ->and(substr_count($rendered, $notification->link()))->toBeLessThanOrEqual(2);
});

it('donne un objet reconnaissable au courriel', function (): void {
    [$notification, $narrator] = promptFor(['email' => 'odette@example.test']);

    expect($notification->toMail($narrator)->subject)->toBe('Votre question de la semaine');
});

it('dédupliquer un envoi ne dépend pas du destinataire mais de l’histoire', function (): void {
    [$notification, , $story] = promptFor();

    expect($notification->dedupeKey(Channel::Sms))->toBe("prompt:{$story->id}:sms")
        ->and($notification->dedupeKey(Channel::Email))->toBe("prompt:{$story->id}:email");
});

it('ne met aucune coordonnée dans le contexte enregistré', function (): void {
    [$notification, $narrator, $story] = promptFor(['email' => 'odette@example.test']);

    $payload = (string) json_encode($notification->deliveryPayload());

    expect($payload)->not->toContain('odette@example.test')
        ->and($payload)->not->toContain((string) $narrator->phone_e164)
        ->and($payload)->toContain($story->id);
});

it('part réellement sur le canal choisi', function (): void {
    $sender = new FakeSmsSender;
    app()->instance(SmsSender::class, $sender);

    [$notification, $narrator] = promptFor(['phone_e164' => '+33600000012']);

    $narrator->notify($notification);

    $sender->assertSentTo('+33600000012', '/r/');
});
