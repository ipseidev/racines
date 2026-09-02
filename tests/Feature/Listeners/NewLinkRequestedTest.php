<?php

declare(strict_types=1);

use App\Enums\Channel;
use App\Enums\TokenIssuedReason;
use App\Enums\TokenType;
use App\Models\AccessToken;
use App\Models\OutboundMessage;
use App\Models\Story;
use App\Services\Tokens\TokenService;
use Illuminate\Support\Facades\Mail;

function expiredLink(): array
{
    $story = Story::factory()->proposed()->create();
    $issued = app(TokenService::class)->issue(TokenType::Record, $story);

    $issued->token->expires_at = now()->subDay();
    $issued->token->save();

    return [$issued->plain, $story];
}

it('renvoie un lien neuf au narrateur et alerte les deux autres', function (): void {
    $sender = fakeSms();
    Mail::fake();

    [$plain, $story] = expiredLink();

    $this->post("/r/{$plain}/request-new-link")->assertRedirect();

    // Un lien neuf, émis pour la bonne raison.
    $fresh = AccessToken::query()
        ->where('subject_id', $story->id)
        ->where('issued_reason', TokenIssuedReason::ReissueSupport->value)
        ->sole();

    expect($fresh->isUsable())->toBeTrue();

    // Le narrateur le reçoit sur son canal habituel.
    expect($sender->messages())->toHaveCount(1)
        ->and($sender->messages()[0]->to)->toBe($story->narrator->phone_e164)
        ->and($sender->messages()[0]->body)->toContain('/r/');

    // L'Initiateur·rice et le support sont prévenus, par courriel.
    $alerts = OutboundMessage::query()->whereIn('template', [
        'new_link_requested_initiator',
        'new_link_requested_support',
    ])->get();

    expect($alerts)->toHaveCount(2)
        ->and($alerts->pluck('channel')->unique()->all())->toBe([Channel::Email]);
});

it('ne met jamais le lien dans le message des tiers', function (): void {
    fakeSms();
    Mail::fake();

    [$plain] = expiredLink();

    $this->post("/r/{$plain}/request-new-link")->assertRedirect();

    foreach (OutboundMessage::query()->where('channel', Channel::Email->value)->get() as $message) {
        $payload = (string) json_encode($message->payload);

        expect($payload)->not->toContain('/r/')
            ->and($payload)->not->toContain($plain);
    }
});

it('ne rend pas le lien dans la réponse HTTP', function (): void {
    fakeSms();
    Mail::fake();

    [$plain] = expiredLink();

    $response = $this->post("/r/{$plain}/request-new-link");

    // La page d'erreur est publique : quiconque détient l'ancienne URL ne doit
    // pas pouvoir en obtenir une neuve à l'écran.
    expect((string) $response->headers->get('Location'))->not->toContain('/r/'.$plain.'x')
        ->and((string) $response->getContent())->not->toContain('X-Amz');
});

it('refuse la demande pour un lien encore valable', function (): void {
    fakeSms();
    Mail::fake();

    $story = Story::factory()->proposed()->create();
    $issued = app(TokenService::class)->issue(TokenType::Record, $story);

    $this->post("/r/{$issued->plain}/request-new-link")->assertNotFound();

    expect(OutboundMessage::query()->count())->toBe(0);
});

it('limite les demandes à une par heure et par lien', function (): void {
    fakeSms();
    Mail::fake();

    [$plain] = expiredLink();

    $this->post("/r/{$plain}/request-new-link")->assertRedirect();
    $this->post("/r/{$plain}/request-new-link")->assertStatus(429);
});
