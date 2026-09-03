<?php

declare(strict_types=1);

use App\Enums\OrderStatus;
use App\Enums\PhoneOptionEntry;
use App\Enums\Sku;
use App\Enums\SupportTicketKind;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PhoneOption;
use App\Models\Project;
use App\Models\SupportTicket;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

/**
 * Le droit de rétractation.
 *
 * Quatorze jours, sans justification. Et passé l'échéance, la page **ne dit
 * pas** « c'est trop tard » : elle explique la garantie de trente jours et
 * donne le contact du support. Un refus sec est l'occasion parfaite de perdre
 * une famille qu'on aurait pu garder.
 *
 * @return array{User, Order}
 */
function paidOrder(array $overrides = []): array
{
    $buyer = User::factory()->create();
    $project = Project::factory()->create(['owner_user_id' => $buyer->id]);

    $order = new Order(array_merge([
        'stripe_checkout_session_id' => 'cs_test_'.uniqid(),
        'status' => OrderStatus::Paid,
        'subtotal_cents' => 4_900,
        'total_cents' => 4_900,
        'paid_at' => now(),
        'withdrawal_deadline_at' => now()->addDays(14),
    ], $overrides));

    $order->user()->associate($buyer);
    $order->project()->associate($project);
    $order->save();

    $item = new OrderItem([
        'sku' => Sku::Pilot,
        'quantity' => 1,
        'unit_cents' => 4_900,
    ]);
    $item->order()->associate($order);
    $item->save();

    return [$buyer, $order->refresh()];
}

it('affiche la commande et son échéance', function (): void {
    [$buyer, $order] = paidOrder();

    $this->actingAs($buyer)
        ->get('/espace/commandes')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('initiator/Orders')
            ->has('orders', 1)
            ->where('orders.0.canBeWithdrawn', true)
            ->has('orders.0.withdrawalDeadlineAt')
            ->where('orders.0.items.0.sku', Sku::Pilot->value)
            // Le libellé est traduit, pas une clé.
            ->where('orders.0.items.0.label', fn (mixed $label) => is_string($label)
                && ! str_starts_with($label, 'enums.'))
            ->has('supportEmail'),
        );
});

it('ouvre un ticket quand la rétractation est demandée dans les quatorze jours', function (): void {
    [$buyer, $order] = paidOrder();

    $this->actingAs($buyer)
        ->post("/espace/commandes/{$order->id}/retractation")
        ->assertRedirect();

    expect(SupportTicket::query()
        ->where('project_id', $order->project?->id)
        ->where('kind', SupportTicketKind::WithdrawalRequested)
        ->exists())->toBeTrue();
});

it('explique la garantie plutôt que de refuser, passé l’échéance', function (): void {
    [$buyer, $order] = paidOrder([
        'paid_at' => now()->subDays(30),
        'withdrawal_deadline_at' => now()->subDays(16),
    ]);

    $this->actingAs($buyer)
        ->post("/espace/commandes/{$order->id}/retractation")
        ->assertRedirect()
        ->assertSessionHas('status');

    // Aucun ticket : la demande n'est pas recevable au titre du délai légal.
    // Mais on ne laisse pas la personne sans réponse.
    expect(SupportTicket::query()
        ->where('kind', SupportTicketKind::WithdrawalRequested)
        ->exists())->toBeFalse();

    $initiator = require base_path('lang/fr/initiator.php');

    expect($initiator['orders']['withdrawal_closed'])->toContain('trente jours')
        ->and($initiator['orders']['withdrawal_expired'])->toContain(':email');
});

it('n’ouvre pas la commande de quelqu’un d’autre', function (): void {
    [, $order] = paidOrder();

    $intruder = User::factory()->create();

    $this->actingAs($intruder)
        ->post("/espace/commandes/{$order->id}/retractation")
        ->assertNotFound();

    $this->actingAs($intruder)
        ->get('/espace/commandes')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->has('orders', 0));
});

it('reste accessible sans courriel vérifié', function (): void {
    $buyer = User::factory()->unverified()->create();
    $project = Project::factory()->create(['owner_user_id' => $buyer->id]);

    $order = new Order([
        'stripe_checkout_session_id' => 'cs_test_unverified',
        'status' => OrderStatus::Paid,
        'subtotal_cents' => 4_900,
        'total_cents' => 4_900,
        'paid_at' => now(),
        'withdrawal_deadline_at' => now()->addDays(14),
    ]);
    $order->user()->associate($buyer);
    $order->project()->associate($project);
    $order->save();

    // Un droit légal ne se conditionne pas à une confirmation technique.
    $this->actingAs($buyer)
        ->post("/espace/commandes/{$order->id}/retractation")
        ->assertRedirect();

    expect(SupportTicket::query()
        ->where('kind', SupportTicketKind::WithdrawalRequested)
        ->exists())->toBeTrue();
});

it('refuse la rétractation d’une commande déjà remboursée', function (): void {
    [$buyer, $order] = paidOrder([
        'status' => OrderStatus::Refunded,
        'refunded_cents' => 4_900,
    ]);

    $this->actingAs($buyer)
        ->post("/espace/commandes/{$order->id}/retractation")
        ->assertRedirect();

    expect(SupportTicket::query()
        ->where('kind', SupportTicketKind::WithdrawalRequested)
        ->exists())->toBeFalse();
});

it('montre l’état de l’option téléphone', function (): void {
    [$buyer, $order] = paidOrder();

    $item = new OrderItem([
        'sku' => Sku::PhoneOption,
        'quantity' => 1,
        'unit_cents' => 2_500,
    ]);
    $item->order()->associate($order);
    $item->save();

    $option = new PhoneOption(['entry' => PhoneOptionEntry::Checkout]);
    $option->project()->associate($order->project);
    $option->orderItem()->associate($item);
    $option->save();

    $this->actingAs($buyer)
        ->get('/espace/commandes')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('orders.0.phoneOption')
            ->where('orders.0.phoneOption.statusLabel', fn (mixed $label) => is_string($label)
                && ! str_starts_with($label, 'enums.')),
        );
});
