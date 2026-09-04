<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AddressForm;
use App\Enums\Cadence;
use App\Enums\Channel;
use App\Enums\ConsentChannel;
use App\Enums\ConsentKind;
use App\Enums\Offer;
use App\Enums\OrderStatus;
use App\Enums\PhoneOptionEntry;
use App\Enums\ProjectMemberRole;
use App\Enums\PromptSlot;
use App\Enums\Sku;
use App\Jobs\SendGiftInvitation;
use App\Models\CheckoutDraft;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PhoneOption;
use App\Models\Project;
use App\Models\User;
use App\Notifications\OrderConfirmationNotification;
use App\Settings\PilotSettings;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Ce qu'un paiement déclenche.
 *
 * **Idempotent par `stripe_checkout_session_id`**, et ce n'est pas une
 * précaution théorique : Stripe rejoue ses webhooks, parfois plusieurs fois,
 * et un projet créé en double serait un projet de trop dans la vie d'une
 * famille — deux invitations au même parent, deux séries de questions.
 *
 * Le projet naît en `draft` et **pas** en `active` : rien ne part avant que le
 * narrateur ait accepté. C'est le critère de sortie du bloc, et l'invariant
 * H0 : le cadeau se propose, il ne s'impose pas.
 */
final readonly class FulfillOrder
{
    public function __construct(
        private AddNarrator $narrators,
        private AddFamilyMember $family,
        private RecordConsent $consents,
    ) {}

    /**
     * @param  array<string, mixed>  $session  La session Stripe, réduite à ce
     *                                         qu'on en utilise.
     */
    public function handle(array $session): ?Order
    {
        $sessionId = (string) ($session['id'] ?? '');

        if ($sessionId === '') {
            return null;
        }

        $existing = Order::query()->where('stripe_checkout_session_id', $sessionId)->first();

        if ($existing instanceof Order) {
            // Webhook rejoué : rien à refaire, et surtout rien à créer.
            Log::info('checkout.fulfilment_replayed', ['session_id' => $sessionId]);

            return $existing;
        }

        $draft = CheckoutDraft::query()
            ->whereKey((string) data_get($session, 'metadata.draft_id'))
            ->first();

        $buyer = User::query()->whereKey((int) data_get($session, 'metadata.user_id'))->first();

        if ($draft === null || $buyer === null) {
            // Un paiement orphelin ne crée rien en devinant : le support le
            // rattachera à la main plutôt qu'on invente une famille.
            Log::error('checkout.fulfilment_orphan', [
                'session_id' => $sessionId,
                'draft_id' => data_get($session, 'metadata.draft_id'),
            ]);

            return null;
        }

        return DB::transaction(fn (): Order => $this->create($session, $draft, $buyer));
    }

    /**
     * @param  array<string, mixed>  $session
     */
    private function create(array $session, CheckoutDraft $draft, User $buyer): Order
    {
        $settings = app(PilotSettings::class);
        $paidAt = CarbonImmutable::now();

        $order = new Order([
            'stripe_checkout_session_id' => (string) $session['id'],
            'stripe_payment_intent_id' => data_get($session, 'payment_intent'),
            'status' => OrderStatus::Paid,
            'subtotal_cents' => (int) data_get($session, 'amount_subtotal', 0),
            'total_cents' => (int) data_get($session, 'amount_total', 0),
            'price_variant' => $draft->price_variant,
            'paid_at' => $paidAt,
            // Stocké et non recalculé : le délai légal se compte à partir
            // d'un fait daté, et une règle qui change ne doit pas rétroagir.
            'withdrawal_deadline_at' => Order::withdrawalDeadlineFrom($paidAt),
            'service_started_at' => $draft->value('early_service_start') === true ? $paidAt : null,
        ]);

        $order->user()->associate($buyer);
        $order->save();

        $project = $this->createProject($draft, $buyer, $settings);
        $order->project()->associate($project);
        $order->save();

        $this->createItems($order, $draft, $settings, $project);
        $this->recordBuyerConsents($draft, $buyer, $project);

        // Programmée, jamais envoyée tout de suite : l'acheteur a choisi une
        // date, et un cadeau qui arrive avant l'heure n'est plus une surprise.
        SendGiftInvitation::dispatch($project->id, 1)
            ->delay($project->gift_send_at ?? now());

        $buyer->notify(new OrderConfirmationNotification($order));

        Log::info('checkout.fulfilled', [
            'order_id' => $order->id,
            'project_id' => $project->id,
            'total_cents' => $order->total_cents,
        ]);

        return $order->refresh();
    }

    private function createProject(CheckoutDraft $draft, User $buyer, PilotSettings $settings): Project
    {
        $sendAt = $draft->value('gift_send_at');
        [$hour, $minute] = self::sendTime($draft->value('gift_send_time'), $settings->gift_send_hour);

        $project = new Project([
            'offer' => $settings->isPrevente() ? Offer::Prevente : Offer::Pilot,
            'address_form' => AddressForm::from((string) $draft->value('address_form', AddressForm::Vous->value)),
            'cadence' => Cadence::Weekly,
            'prompt_day' => 1,
            'prompt_slot' => PromptSlot::Morning,
            'gift_message' => $draft->value('gift_message'),
            'gift_send_at' => $sendAt === null
                ? now()->addDay()->setTime($hour, $minute)
                : CarbonImmutable::parse((string) $sendAt)->setTime($hour, $minute),
        ]);

        $project->owner()->associate($buyer);

        if ($settings->cohort_id !== null) {
            $project->cohort_id = $settings->cohort_id;
        }

        $project->save();

        $this->narrators->handle($project, [
            'first_name' => (string) $draft->value('narrator_first_name'),
            'last_name' => $draft->value('narrator_last_name'),
            'display_name' => (string) $draft->value('narrator_first_name'),
            'email' => $draft->value('narrator_email'),
            'phone_e164' => $draft->value('narrator_phone'),
            'preferred_channel' => Channel::from((string) $draft->value('preferred_channel', Channel::Sms->value)),
            'tech_comfort' => $draft->value('narrator_tech_comfort'),
        ]);

        $project->members()->create([
            'user_id' => $buyer->id,
            'role' => ProjectMemberRole::Initiator,
        ]);

        // L'Initiateur·rice écoute comme un proche : sans fiche, elle ne
        // pourrait pas réagir aux histoires qu'elle a offertes.
        $this->family->handle($project, $buyer, [
            'display_name' => $buyer->name,
            'email' => $buyer->email,
            'relationship' => $draft->value('relationship'),
        ]);

        return $project->refresh();
    }

    /**
     * L'heure d'envoi choisie à l'achat (« 09:30 »), sinon celle des réglages.
     *
     * @return array{int, int}
     */
    private static function sendTime(mixed $time, int $defaultHour): array
    {
        if (is_string($time) && preg_match('/^(\d{2}):(\d{2})$/', $time, $matches) === 1) {
            return [(int) $matches[1], (int) $matches[2]];
        }

        return [$defaultHour, 0];
    }

    private function createItems(Order $order, CheckoutDraft $draft, PilotSettings $settings, Project $project): void
    {
        $main = $settings->isPrevente() ? Sku::CorePrevente : Sku::Pilot;

        $this->addItem($order, $main, $settings->isPrevente()
            ? ($draft->price_variant ?? $settings->prevente_prices_cents[0] ?? 9_900)
            : $settings->pilot_price_cents);

        $extra = (int) $draft->value('extra_copies', 0);

        if ($extra > 0) {
            $this->addItem($order, Sku::ExtraCopy, $settings->extra_copy_price_cents, $extra);
        }

        if ($draft->value('phone_option') !== true) {
            return;
        }

        $item = $this->addItem($order, Sku::PhoneOption, $settings->phone_option_price_cents);

        $option = new PhoneOption(['entry' => PhoneOptionEntry::Checkout]);
        $option->project()->associate($project);
        $option->orderItem()->associate($item);
        $option->save();
    }

    private function addItem(Order $order, Sku $sku, int $unitCents, int $quantity = 1): OrderItem
    {
        $item = new OrderItem([
            'sku' => $sku,
            'quantity' => $quantity,
            // Copié, jamais relu dans les réglages : le prix d'une commande
            // passée ne change pas quand celui du produit change.
            'unit_cents' => $unitCents,
            'stripe_price_id' => $sku->stripePriceId($order->price_variant),
        ]);

        $item->order()->associate($order);
        $item->save();

        return $item;
    }

    private function recordBuyerConsents(CheckoutDraft $draft, User $buyer, Project $project): void
    {
        $granted = [
            [ConsentKind::EarlyServiceStart, 'early_service_start'],
            [ConsentKind::MarketingEmail, 'marketing_email'],
        ];

        foreach ($granted as [$kind, $field]) {
            if ($draft->value($field) !== true) {
                continue;
            }

            $this->consents->handle($buyer, $project, $kind, ConsentChannel::Web);
        }
    }
}
