<?php

declare(strict_types=1);

use App\Enums\PhoneOptionEntry;
use App\Enums\Sku;
use App\Models\Order;
use App\Models\PhoneOption;
use App\Models\Project;
use App\Models\User;
use App\Services\Payments\CheckoutSessions;
use App\Services\Payments\FakeCheckoutSessions;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Compléter une commande déjà passée.
 *
 * Trouvé par le fondateur en jouant un achat : « j'ai oublié d'ajouter
 * l'option téléphone, et je n'ai plus aucun moyen d'ajouter d'extras une fois
 * la commande passée ». C'était vrai — la seule porte était l'alerte du
 * moteur, qui n'apparaît qu'après **trois semaines de silence**.
 *
 * Le défaut n'est pas seulement d'ergonomie. Le test de demande D-9 mesure le
 * taux d'attache séparément par point d'entrée, et sans porte accessible le
 * chiffre du sauvetage serait bas pour une raison d'interface, pas de demande
 * — on retirerait l'option sur une mesure faussée (T-184).
 */
function commandePayeePourComplement(): array
{
    $buyer = User::factory()->create();
    $project = Project::factory()->create(['owner_user_id' => $buyer->id]);

    $order = Order::factory()->paid()->create([
        'user_id' => $buyer->id,
        'project_id' => $project->id,
    ]);

    return [$buyer, $order];
}

function fakeTopUpSessions(): FakeCheckoutSessions
{
    $sessions = new FakeCheckoutSessions;
    app()->instance(CheckoutSessions::class, $sessions);

    return $sessions;
}

it('ouvre une session de paiement pour l’option téléphone', function (): void {
    $sessions = fakeTopUpSessions();
    [$buyer, $order] = commandePayeePourComplement();

    $this->actingAs($buyer)
        ->post(route('initiator.orders.top_up', ['order' => $order]), ['sku' => Sku::PhoneOption->value])
        ->assertRedirect();

    $session = $sessions->last();

    expect($session)->not->toBeNull()
        ->and($session['metadata']['order_id'])->toBe($order->id)
        ->and($session['metadata']['sku'])->toBe(Sku::PhoneOption->value);
});

it('refuse une option téléphone déjà présente', function (): void {
    fakeTopUpSessions();
    [$buyer, $order] = commandePayeePourComplement();

    $option = new PhoneOption(['entry' => PhoneOptionEntry::Checkout]);
    $option->project()->associate($order->project);
    $option->save();

    $this->actingAs($buyer)
        ->post(route('initiator.orders.top_up', ['order' => $order]), ['sku' => Sku::PhoneOption->value])
        ->assertRedirect();

    expect(PhoneOption::query()->count())->toBe(1);
});

it('refuse de compléter la commande de quelqu’un d’autre', function (): void {
    fakeTopUpSessions();
    [, $order] = commandePayeePourComplement();

    $this->actingAs(User::factory()->create())
        ->post(route('initiator.orders.top_up', ['order' => $order]), ['sku' => Sku::PhoneOption->value])
        ->assertNotFound();
});

it('refuse un article qui ne se complète pas', function (): void {
    fakeTopUpSessions();
    [$buyer, $order] = commandePayeePourComplement();

    $this->actingAs($buyer)
        ->post(route('initiator.orders.top_up', ['order' => $order]), ['sku' => Sku::Pilot->value])
        ->assertSessionHasErrors('sku');
});
