<?php

declare(strict_types=1);

use App\Actions\RecordConsent;
use App\Actions\RevokeConsent;
use App\Enums\ConsentChannel;
use App\Enums\ConsentKind;
use App\Enums\ConsentStatus;
use App\Exceptions\Domain\ConsentNotGranted;
use App\Exceptions\Domain\ConsentOperatorRequired;
use App\Exceptions\Domain\MissingConsentText;
use App\Models\ConsentText;
use App\Models\Narrator;
use App\Models\User;

it('enregistre un consentement accordé avec la version du texte en vigueur', function (): void {
    $narrator = Narrator::factory()->primary()->create();

    $consent = app(RecordConsent::class)->handle(
        $narrator,
        $narrator->project,
        ConsentKind::VoiceRecording,
        ConsentChannel::Web,
        null,
        ['ip' => '203.0.113.9', 'user_agent' => 'Safari/iOS'],
    );

    expect($consent->status)->toBe(ConsentStatus::Granted)
        ->and($consent->text_version)->toBe('1.0')
        ->and($consent->subject_id)->toBe($narrator->id)
        ->and($consent->subject_type)->toBe('narrator')
        ->and($consent->granted_at)->not->toBeNull()
        ->and($consent->user_agent)->toBe('Safari/iOS')
        ->and($narrator->hasConsent(ConsentKind::VoiceRecording))->toBeTrue();
});

it('ne garde de l’adresse IP qu’une empreinte', function (): void {
    $narrator = Narrator::factory()->primary()->create();

    $consent = app(RecordConsent::class)->handle(
        $narrator,
        $narrator->project,
        ConsentKind::VoiceRecording,
        ConsentChannel::Web,
        null,
        ['ip' => '203.0.113.9'],
    );

    expect($consent->ip_hash)->toHaveLength(64)
        ->and($consent->ip_hash)->not->toContain('203.0.113');
});

it('crée une ligne révoquée sans modifier la ligne d’origine', function (): void {
    $narrator = Narrator::factory()->primary()->create();

    $granted = app(RecordConsent::class)->handle(
        $narrator,
        $narrator->project,
        ConsentKind::FamilySharing,
        ConsentChannel::Web,
    );

    expect($narrator->hasConsent(ConsentKind::FamilySharing))->toBeTrue();

    $this->travel(1)->hour();

    $revoked = app(RevokeConsent::class)->handle(
        $narrator,
        $narrator->project,
        ConsentKind::FamilySharing,
        ConsentChannel::Web,
    );

    expect($revoked->id)->not->toBe($granted->id)
        ->and($revoked->status)->toBe(ConsentStatus::Revoked)
        ->and($revoked->revoked_at)->not->toBeNull()
        ->and($granted->fresh()?->status)->toBe(ConsentStatus::Granted)
        ->and($granted->fresh()?->revoked_at)->toBeNull()
        ->and($narrator->refresh()->hasConsent(ConsentKind::FamilySharing))->toBeFalse()
        ->and($narrator->consents()->where('kind', ConsentKind::FamilySharing->value)->count())->toBe(2);
});

it('refuse de révoquer un consentement jamais accordé', function (): void {
    $narrator = Narrator::factory()->primary()->create();

    expect(fn () => app(RevokeConsent::class)->handle(
        $narrator,
        $narrator->project,
        ConsentKind::AiRendering,
        ConsentChannel::Web,
    ))->toThrow(ConsentNotGranted::class);
});

it('exige un opérateur nommé pour un consentement recueilli par téléphone', function (): void {
    $narrator = Narrator::factory()->primary()->create();

    expect(fn () => app(RecordConsent::class)->handle(
        $narrator,
        $narrator->project,
        ConsentKind::PhoneCallRecording,
        ConsentChannel::Phone,
    ))->toThrow(ConsentOperatorRequired::class);

    $operator = User::factory()->support()->create();

    $consent = app(RecordConsent::class)->handle(
        $narrator,
        $narrator->project,
        ConsentKind::PhoneCallRecording,
        ConsentChannel::Phone,
        $operator,
    );

    expect($consent->recorded_by_user_id)->toBe($operator->id);
});

it('refuse d’enregistrer un consentement dont le texte n’existe pas', function (): void {
    ConsentText::query()->where('kind', ConsentKind::PhotoRights->value)->delete();

    $narrator = Narrator::factory()->primary()->create();

    expect(fn () => app(RecordConsent::class)->handle(
        $narrator,
        $narrator->project,
        ConsentKind::PhotoRights,
        ConsentChannel::Web,
    ))->toThrow(MissingConsentText::class);
});

it('prend la version la plus récente quand le texte a changé', function (): void {
    // `now()` et non `now()->subHour()` : le texte semé prend effet au début
    // de la journée, et une heure en arrière tombe la veille quand la suite
    // tourne entre minuit et une heure. Le test échouait alors pour une
    // raison qui n'avait rien à voir avec le produit (T-97).
    ConsentText::factory()->ofKind(ConsentKind::Transcription)->create([
        'version' => '2.0',
        'effective_from' => now(),
    ]);

    $narrator = Narrator::factory()->primary()->create();

    $consent = app(RecordConsent::class)->handle(
        $narrator,
        $narrator->project,
        ConsentKind::Transcription,
        ConsentChannel::Web,
    );

    expect($consent->text_version)->toBe('2.0');
});

it('ignore un texte qui n’est pas encore en vigueur', function (): void {
    ConsentText::factory()->ofKind(ConsentKind::Transcription)->create([
        'version' => '3.0',
        'effective_from' => now()->addWeek(),
    ]);

    $narrator = Narrator::factory()->primary()->create();

    $consent = app(RecordConsent::class)->handle(
        $narrator,
        $narrator->project,
        ConsentKind::Transcription,
        ConsentChannel::Web,
    );

    expect($consent->text_version)->toBe('1.0');
});
