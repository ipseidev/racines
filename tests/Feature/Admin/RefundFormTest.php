<?php

declare(strict_types=1);

use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Models\Order;
use App\Models\Project;
use App\Models\User;
use App\Services\Payments\FakeRefunds;
use App\Services\Payments\Refunds;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/**
 * Le formulaire de remboursement parle **en euros**.
 *
 * Il demandait des centimes, avec « 89,00 € » affiché dans la ligne juste
 * derrière. Trouvé au point 5 du checkpoint du bloc 11 : le montant voulu
 * était vingt euros, vingt centimes sont partis (T-195). Le libellé disait
 * bien « en centimes » — un libellé exact ne rachète pas une unité qui n'est
 * celle de nulle part ailleurs dans le panneau, sur le seul geste du
 * back-office qui déplace de l'argent.
 *
 * La conversion se fait à la frontière : `IssueRefund` continue de recevoir
 * des centimes entiers, et rien en dessous ne connaît les euros.
 */
function fauxRemboursements(): FakeRefunds
{
    $refunds = new FakeRefunds;
    app()->instance(Refunds::class, $refunds);

    return $refunds;
}

function commandePayeeAuxEuros(int $totalCents = 8_900): Order
{
    $buyer = User::factory()->create();
    $order = new Order([
        'stripe_checkout_session_id' => 'cs_test_'.uniqid(),
        'stripe_payment_intent_id' => 'pi_test_'.uniqid(),
        'status' => OrderStatus::Paid,
        'subtotal_cents' => $totalCents,
        'total_cents' => $totalCents,
        'paid_at' => now(),
        'withdrawal_deadline_at' => now()->addDays(14),
    ]);
    $order->user()->associate($buyer);
    $order->project()->associate(Project::factory()->create(['owner_user_id' => $buyer->id]));
    $order->save();

    return $order->refresh();
}

it('convertit les euros saisis en centimes entiers', function (): void {
    $refunds = fauxRemboursements();
    $order = commandePayeeAuxEuros();

    Livewire::actingAs(User::factory()->admin()->create())
        ->test(ListOrders::class)
        ->callAction(TestAction::make('refund')->table($order), ['amount' => '20', 'reason' => 'Geste commercial'])
        ->assertHasNoActionErrors();

    expect($refunds->last()['amount'])->toBe(2_000);
});

it('n’arrondit pas de travers les centimes saisis', function (): void {
    $refunds = fauxRemboursements();
    $order = commandePayeeAuxEuros();

    Livewire::actingAs(User::factory()->admin()->create())
        ->test(ListOrders::class)
        ->callAction(TestAction::make('refund')->table($order), ['amount' => '12,34', 'reason' => 'Geste commercial']);

    // La virgule est ce que tape une personne en France, et 12,34 € ne doit
    // pas devenir 1 233 centimes.
    expect($refunds->last()['amount'])->toBe(1_234);
});

it('propose par défaut ce qui reste dû, en euros', function (): void {
    fauxRemboursements();
    $order = commandePayeeAuxEuros();
    $order->forceFill(['refunded_cents' => 1_900])->save();

    Livewire::actingAs(User::factory()->admin()->create())
        ->test(ListOrders::class)
        ->mountAction(TestAction::make('refund')->table($order))
        ->assertActionDataSet(['amount' => '70.00']);
});

it('refuse plus que ce qui reste dû', function (): void {
    $refunds = fauxRemboursements();
    $order = commandePayeeAuxEuros();

    Livewire::actingAs(User::factory()->admin()->create())
        ->test(ListOrders::class)
        ->callAction(TestAction::make('refund')->table($order), ['amount' => '100', 'reason' => 'Trop demandé'])
        ->assertHasActionErrors(['amount']);

    expect($refunds->last())->toBeNull();
});
