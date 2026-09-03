<?php

declare(strict_types=1);

use App\Enums\PhoneOptionStatus;
use App\Features\PhoneOptionOffer;
use App\Features\PreventePrice;
use App\Models\PhoneOption;
use App\Models\Project;
use App\Settings\PilotSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Laravel\Pennant\Feature;

it('assigne l’un des deux prix de prévente', function (): void {
    $price = (new PreventePrice)->resolve('un-visiteur');

    expect(PreventePrice::prices())->toBe([9_900, 12_900])
        ->and($price)->toBeIn([9_900, 12_900]);
});

it('rend le même prix dix visites de suite', function (): void {
    $identifier = 'visiteur-stable';
    $first = (new PreventePrice)->resolve($identifier);

    foreach (range(1, 10) as $visit) {
        // Un prix qui bouge d'une visite à l'autre n'est pas une expérience,
        // c'est une raison de ne pas acheter.
        expect((new PreventePrice)->resolve($identifier))->toBe($first);
    }
});

it('répartit à peu près moitié-moitié sur mille visiteurs', function (): void {
    $counts = [9_900 => 0, 12_900 => 0];

    foreach (range(1, 1_000) as $index) {
        $counts[(new PreventePrice)->resolve((string) Str::uuid7())]++;
    }

    // 40/60 au pire : en dessous, la comparaison H3 demanderait beaucoup plus
    // de familles pour dire quoi que ce soit.
    expect($counts[9_900])->toBeGreaterThan(400)
        ->and($counts[9_900])->toBeLessThan(600)
        ->and($counts[9_900] + $counts[12_900])->toBe(1_000);
});

it('retombe sur le premier prix sans cookie', function (): void {
    $request = Request::create('/acheter');

    // Un robot ou un navigateur qui refuse les cookies ne doit pas fausser la
    // répartition.
    expect(PreventePrice::forRequest($request))->toBe(9_900);
});

it('suit la variante portée par le cookie', function (): void {
    $withCookie = Request::create('/acheter');
    $withCookie->cookies->set(PreventePrice::COOKIE, 'visiteur-stable');

    expect(PreventePrice::forRequest($withCookie))
        ->toBe((new PreventePrice)->resolve('visiteur-stable'));
});

it('garde l’option téléphone fermée par défaut', function (): void {
    // Une promesse humaine ne s'ouvre pas par défaut : c'est une capacité de
    // l'équipe, pas une caractéristique du produit.
    expect(PhoneOptionOffer::isOpen())->toBeFalse()
        ->and(PhoneOptionOffer::cap())->toBe(10);
});

it('ouvre l’option téléphone quand le drapeau l’est', function (): void {
    Feature::activate(PhoneOptionOffer::class);

    expect(PhoneOptionOffer::isOpen())->toBeTrue()
        ->and(PhoneOptionOffer::remaining())->toBe(10);
});

it('referme l’option quand le plafond est atteint', function (): void {
    Feature::activate(PhoneOptionOffer::class);

    foreach (range(1, 10) as $index) {
        PhoneOption::factory()->create(['project_id' => Project::factory()]);
    }

    // La onzième famille recevrait une promesse qu'on ne tiendrait pas.
    expect(PhoneOptionOffer::isSaturated())->toBeTrue()
        ->and(PhoneOptionOffer::isOpen())->toBeFalse()
        ->and(PhoneOptionOffer::remaining())->toBe(0);
});

it('compte une demande en attente comme un créneau pris', function (): void {
    Feature::activate(PhoneOptionOffer::class);

    PhoneOption::factory()->count(5)->create(['project_id' => Project::factory()]);
    PhoneOption::factory()->active()->count(5)->create(['project_id' => Project::factory()]);

    // Le créneau est réservé dès qu'on l'a promis : ne compter que les
    // options actives ferait accepter onze familles pour dix appels.
    expect(PhoneOptionOffer::taken())->toBe(10)
        ->and(PhoneOptionOffer::isSaturated())->toBeTrue();
});

it('libère un créneau quand une option est annulée', function (): void {
    Feature::activate(PhoneOptionOffer::class);

    PhoneOption::factory()->count(10)->create(['project_id' => Project::factory()]);
    PhoneOption::query()->first()?->update(['status' => PhoneOptionStatus::Cancelled]);

    expect(PhoneOptionOffer::taken())->toBe(9)
        ->and(PhoneOptionOffer::isOpen())->toBeTrue();
});

it('démarre en mode pilote, jamais en prévente', function (): void {
    $settings = app(PilotSettings::class);

    // Se tromper dans ce sens-là coûte une vente ; se tromper dans l'autre
    // coûte une promesse qu'on ne peut pas tenir (règle §9 du bloc 10).
    expect($settings->mode)->toBe('pilot')
        ->and($settings->isPilot())->toBeTrue()
        ->and($settings->isPrevente())->toBeFalse()
        ->and($settings->pilot_price_cents)->toBe(4_900)
        ->and($settings->phone_option_price_cents)->toBe(2_500)
        ->and($settings->gift_send_hour)->toBe(9);
});

it('garde les pages légales en attente de validation', function (): void {
    expect(app(PilotSettings::class)->legalValidated())->toBeFalse();
});
