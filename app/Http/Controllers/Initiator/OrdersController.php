<?php

declare(strict_types=1);

namespace App\Http\Controllers\Initiator;

use App\Actions\OpenSupportTicket;
use App\Enums\Sku;
use App\Enums\SupportTicketKind;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PhoneOption;
use App\Support\Brand;
use App\Support\Options;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

/**
 * Les commandes, et le droit de rétractation.
 *
 * Accessible **sans** vérification de courriel : quelqu'un doit pouvoir
 * exercer sa rétractation sans avoir cliqué un lien dans sa boîte. Un droit
 * légal ne se conditionne pas à une confirmation technique.
 *
 * Passé l'échéance, la page ne dit pas « c'est trop tard » : elle explique la
 * garantie de trente jours et donne le contact du support. Le refus sec est
 * l'occasion parfaite de perdre une famille qu'on aurait pu garder.
 */
final readonly class OrdersController
{
    public function __construct(private OpenSupportTicket $tickets) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        abort_if($user === null, 403);

        $orders = Order::query()
            ->where('user_id', $user->id)
            ->with(['items', 'project'])
            ->latest()
            ->get()
            ->map(fn (Order $order): array => [
                'id' => $order->id,
                'status' => $order->status->value,
                'statusLabel' => Options::label($order->status),
                'totalCents' => $order->total_cents,
                'refundedCents' => $order->refunded_cents,
                'paidAt' => $order->paid_at?->toIso8601String(),
                'withdrawalDeadlineAt' => $order->withdrawal_deadline_at?->toIso8601String(),
                'canBeWithdrawn' => $order->canBeWithdrawn(),
                'invoiceUrl' => $order->stripe_invoice_url,
                'items' => array_values($order->items
                    ->map(fn (OrderItem $item): array => [
                        'sku' => $item->sku->value,
                        'label' => Options::label($item->sku),
                        'quantity' => $item->quantity,
                        'unitCents' => $item->unit_cents,
                    ])
                    ->all()),
                'phoneOption' => self::phoneOptionFor($order),
            ])
            ->all();

        return inertia('initiator/Orders', [
            'orders' => array_values($orders),
            'supportEmail' => Brand::supportEmail(),
        ]);
    }

    public function withdraw(Request $request, string $order): RedirectResponse
    {
        $user = $request->user();
        abort_if($user === null, 403);

        $found = Order::query()
            ->where('user_id', $user->id)
            ->whereKey($order)
            ->first();

        abort_unless($found instanceof Order, 404);

        if (! $found->canBeWithdrawn()) {
            // Ni refus sec ni silence : on explique la garantie de trente
            // jours et on donne le contact.
            return back()->with('status', __('initiator.orders.withdrawal_closed'));
        }

        $project = $found->project;

        if ($project !== null) {
            $this->tickets->handle(
                $project,
                SupportTicketKind::WithdrawalRequested,
                null,
                ['order_id' => $found->id, 'total_cents' => $found->total_cents],
            );
        }

        return back()->with('status', __('initiator.orders.withdrawal_requested'));
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function phoneOptionFor(Order $order): ?array
    {
        if (! $order->includes(Sku::PhoneOption) || $order->project === null) {
            return null;
        }

        $option = PhoneOption::query()
            ->where('project_id', $order->project->id)
            ->latest()
            ->first();

        return $option === null ? null : [
            'status' => $option->status->value,
            'statusLabel' => Options::label($option->status),
            'callDay' => $option->call_day,
            'callSlot' => $option->call_slot?->value,
        ];
    }
}
