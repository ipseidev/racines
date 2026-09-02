<?php

declare(strict_types=1);

use App\Actions\GrantMandate;
use App\Actions\RevokeMandate;
use App\Actions\ValidateStoryAction;
use App\Enums\ConsentChannel;
use App\Enums\ConsentKind;
use App\Enums\ValidatedVia;
use App\Exceptions\Domain\FeatureClosed;
use App\Features\MandateDelegation;
use App\Models\Consent;
use App\Models\FamilyMember;
use App\Models\Mandate;
use App\Models\Narrator;
use App\Models\Story;
use App\Models\User;
use App\States\Story\Validated;
use Laravel\Pennant\Feature;

/**
 * Un mandat en règle : drapeau ouvert pour ce projet, et un consentement du
 * narrateur recueilli par un canal qui laisse une trace.
 *
 * @return array{Mandate, Narrator, FamilyMember}
 */
function grantedMandate(ConsentChannel $channel = ConsentChannel::Web): array
{
    $narrator = Narrator::factory()->primary()->create();
    $project = $narrator->project;

    Feature::for($project)->activate(MandateDelegation::class);

    $holder = FamilyMember::factory()->create(['project_id' => $project->id]);

    $consent = Consent::factory()->create([
        'subject_id' => $narrator->id,
        'project_id' => $project->id,
        'kind' => ConsentKind::MandateDelegation,
        'channel' => $channel,
    ]);

    $mandate = app(GrantMandate::class)->handle($project, $narrator, $holder, $consent);

    return [$mandate, $narrator, $holder];
}

it('laisse un mandataire valider une histoire à relire', function (): void {
    [$mandate, $narrator, $holder] = grantedMandate();
    $story = Story::factory()->toReview()->create([
        'narrator_id' => $narrator->id,
        'project_id' => $narrator->project_id,
    ]);

    expect($mandate->covers($story, 'validate'))->toBeTrue();

    app(ValidateStoryAction::class)->handle($story, ValidatedVia::Mandate, $holder);

    expect($story->refresh()->state)->toBeInstanceOf(Validated::class)
        ->and($story->validated_via)->toBe(ValidatedVia::Mandate);
});

it('ne couvre pas une histoire seulement transcrite', function (): void {
    [$mandate, $narrator] = grantedMandate();
    $story = Story::factory()->transcribed()->create([
        'narrator_id' => $narrator->id,
        'project_id' => $narrator->project_id,
    ]);

    // Le mandat sert à débloquer une relecture que le narrateur ne fait pas ;
    // il ne remplace pas sa décision de partage.
    expect($mandate->covers($story, 'validate'))->toBeFalse();
});

it('ne couvre pas l’histoire d’un autre narrateur', function (): void {
    [$mandate] = grantedMandate();
    $stranger = Story::factory()->toReview()->create();

    expect($mandate->covers($stranger, 'validate'))->toBeFalse();
});

it('ne couvre que les actes de son périmètre', function (): void {
    [$mandate, $narrator] = grantedMandate();
    $story = Story::factory()->toReview()->create([
        'narrator_id' => $narrator->id,
        'project_id' => $narrator->project_id,
    ]);

    expect($mandate->covers($story, 'delete'))->toBeFalse();
});

it('cesse de couvrir dès la révocation', function (): void {
    [$mandate, $narrator] = grantedMandate();
    $story = Story::factory()->toReview()->create([
        'narrator_id' => $narrator->id,
        'project_id' => $narrator->project_id,
    ]);

    app(RevokeMandate::class)->handle($mandate);

    // Immédiatement : un mandat révoqué qui vaudrait encore une minute
    // vaudrait encore une histoire.
    expect($mandate->refresh()->revoked_at)->not->toBeNull()
        ->and($mandate->covers($story, 'validate'))->toBeFalse();
});

it('exige un consentement du narrateur', function (): void {
    $narrator = Narrator::factory()->primary()->create();
    Feature::for($narrator->project)->activate(MandateDelegation::class);
    $holder = User::factory()->create();

    $consent = Consent::factory()->revoked()->create([
        'subject_id' => $narrator->id,
        'project_id' => $narrator->project_id,
        'kind' => ConsentKind::MandateDelegation,
    ]);

    // Déléguer sa validation est une exception au principe de souveraineté :
    // elle n'existe que par un accord explicite, et journalisé.
    app(GrantMandate::class)->handle($narrator->project, $narrator, $holder, $consent);
})->throws(InvalidArgumentException::class);

it('refuse un consentement recueilli par l’administration', function (): void {
    // Un « accord » saisi par le support n'est pas l'accord du narrateur.
    grantedMandate(ConsentChannel::Admin);
})->throws(InvalidArgumentException::class);

it('n’existe pas quand le drapeau est fermé', function (): void {
    $narrator = Narrator::factory()->primary()->create();
    $holder = FamilyMember::factory()->create(['project_id' => $narrator->project_id]);
    $consent = Consent::factory()->create([
        'subject_id' => $narrator->id,
        'project_id' => $narrator->project_id,
        'kind' => ConsentKind::MandateDelegation,
    ]);

    expect(MandateDelegation::isOpenFor($narrator->project))->toBeFalse();

    app(GrantMandate::class)->handle($narrator->project, $narrator, $holder, $consent);
})->throws(FeatureClosed::class);

it('rend 404 pour une fonctionnalité fermée', function (): void {
    $exception = FeatureClosed::make('mandate-delegation');

    // Fermée doit se voir comme inexistante, pas comme interdite : un 403
    // annoncerait une fonctionnalité qu'on ne veut pas encore annoncer.
    expect($exception->getStatusCode())->toBe(404);
});

it('ne garde qu’un mandat vivant par mandataire', function (): void {
    [$mandate, $narrator, $holder] = grantedMandate();

    $consent = Consent::factory()->create([
        'subject_id' => $narrator->id,
        'project_id' => $narrator->project_id,
        'kind' => ConsentKind::MandateDelegation,
    ]);

    $second = app(GrantMandate::class)->handle($narrator->project, $narrator, $holder, $consent);

    expect($mandate->refresh()->revoked_at)->not->toBeNull()
        ->and($second->revoked_at)->toBeNull()
        ->and(Mandate::query()->whereNull('revoked_at')->count())->toBe(1);
});
