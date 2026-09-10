<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Laravel\Fortify\Features;
use Tests\TestCase;

final class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skipUnlessFortifyHas(Features::emailVerification());
    }

    public function test_email_verification_screen_can_be_rendered()
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get(route('verification.notice'));

        $response->assertOk();
    }

    public function test_email_can_be_verified()
    {
        $user = User::factory()->unverified()->create();

        Event::fake();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)],
        );

        $response = $this->actingAs($user)->get($verificationUrl);

        Event::assertDispatched(Verified::class);

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $response->assertRedirect('/espace?verified=1');
    }

    public function test_email_is_not_verified_with_invalid_hash()
    {
        $user = User::factory()->unverified()->create();

        Event::fake();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1('wrong-email')],
        );

        $this->actingAs($user)->get($verificationUrl);

        Event::assertNotDispatched(Verified::class);
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_email_is_not_verified_with_invalid_user_id(): void
    {
        $user = User::factory()->unverified()->create();

        Event::fake();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => 123, 'hash' => sha1($user->email)],
        );

        $this->actingAs($user)->get($verificationUrl);

        Event::assertNotDispatched(Verified::class);
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_verified_user_is_redirected_to_dashboard_from_verification_prompt(): void
    {
        $user = User::factory()->create();

        Event::fake();

        $response = $this->actingAs($user)->get(route('verification.notice'));

        Event::assertNotDispatched(Verified::class);
        $response->assertRedirect('/espace');
    }

    /*
     * Un lien de confirmation périmé ne ferme pas la porte (T-240).
     *
     * Ces quatre cas rendaient tous un **403** dont la page dit « Il faut un
     * lien personnel pour y accéder. Demandez-le à la personne qui vous a
     * invité » — servie à quelqu'un qui vient d'acheter et n'a personne à qui
     * le demander.
     */

    public function test_expired_verification_link_sends_a_new_one_instead_of_refusing(): void
    {
        $user = User::factory()->unverified()->create();

        Notification::fake();

        $expired = URL::temporarySignedRoute(
            'verification.verify',
            now()->subMinute(),
            ['id' => $user->id, 'hash' => sha1($user->email)],
        );

        $this->actingAs($user)->get($expired)
            ->assertRedirect(route('verification.notice'))
            ->assertSessionHas('status', 'verification-link-expired');

        Notification::assertSentTo($user, VerifyEmail::class);
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_expired_verification_link_of_an_already_verified_account_leads_to_the_space(): void
    {
        $user = User::factory()->create();

        Notification::fake();

        $expired = URL::temporarySignedRoute(
            'verification.verify',
            now()->subMinute(),
            ['id' => $user->id, 'hash' => sha1($user->email)],
        );

        $this->actingAs($user)->get($expired)->assertRedirect('/espace');

        // Rien à confirmer : rien à renvoyer.
        Notification::assertNothingSent();
    }

    public function test_verification_link_of_another_account_explains_rather_than_refuses(): void
    {
        $mine = User::factory()->unverified()->create();
        $other = User::factory()->unverified()->create();

        Notification::fake();

        $link = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $other->id, 'hash' => sha1($other->email)],
        );

        $this->actingAs($mine)->get($link)
            ->assertRedirect(route('verification.notice'))
            ->assertSessionHas('status', 'verification-link-mismatch');

        // Le remède est l'autre compte, pas un lien de plus : on n'en envoie
        // ni à l'un ni à l'autre.
        Notification::assertNothingSent();
        $this->assertFalse($other->fresh()->hasVerifiedEmail());
        $this->assertFalse($mine->fresh()->hasVerifiedEmail());
    }

    public function test_verification_link_survives_a_night_in_the_mailbox(): void
    {
        $user = User::factory()->unverified()->create();

        $link = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes((int) config('auth.verification.expire')),
            ['id' => $user->id, 'hash' => sha1($user->email)],
        );

        // Le compte se crée au milieu du tunnel d'achat ; la boîte aux
        // lettres s'ouvre le soir.
        $this->travel(20)->hours();

        $this->actingAs($user)->get($link)->assertRedirect('/espace?verified=1');

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_already_verified_user_visiting_verification_link_is_redirected_without_firing_event_again(): void
    {
        $user = User::factory()->create();

        Event::fake();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)],
        );

        $this->actingAs($user)->get($verificationUrl)
            ->assertRedirect('/espace?verified=1');

        Event::assertNotDispatched(Verified::class);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }
}
