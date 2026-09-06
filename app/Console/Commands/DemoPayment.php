<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Order;
use Illuminate\Console\Command;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

/**
 * Un vrai paiement de test, attaché à la commande du décor.
 *
 * Le point 5 du bloc 11 demande de rembourser partiellement, et le
 * remboursement est le seul geste du back-office qui parle à Stripe pour de
 * bon. Or la commande semée porte une référence de paiement inventée : le
 * bouton existait, l'action journalisait, et l'appel échouait sur un
 * « No such payment_intent » que rien n'annonçait.
 *
 * La parade évidente — refaire un achat complet par `/acheter` — coûte le
 * tunnel entier pour obtenir une ligne, et se reperd au `migrate:fresh`
 * suivant. Cette commande crée le paiement directement, avec la carte de test
 * de Stripe, et le pose sur la commande existante.
 *
 * **Elle refuse une clé live.** Créer puis rembourser un paiement réel serait
 * un mouvement d'argent véritable pour une vérification de décor, et le `.env`
 * d'une machine de développement n'a aucune raison de porter une telle clé —
 * mais il suffit d'une fois.
 */
final class DemoPayment extends Command
{
    protected $signature = 'demo:paiement {--order= : L’identifiant de la commande ; la dernière payée sinon}';

    protected $description = 'Fabrique un paiement de test réel et l’attache à une commande, pour le remboursement du bloc 11';

    public function handle(): int
    {
        if (app()->isProduction()) {
            $this->components->error('Cette commande ne touche qu’un décor de démonstration. Jamais en production.');

            return self::FAILURE;
        }

        $secret = (string) config('cashier.secret');

        if (! str_starts_with($secret, 'sk_test_') && ! str_starts_with($secret, 'rk_test_')) {
            $this->components->error('La clé Stripe configurée n’est pas une clé de test. On ne fabrique pas un paiement réel pour un décor.');

            return self::FAILURE;
        }

        $order = $this->order();

        if (! $order instanceof Order) {
            $this->components->error('Aucune commande payée à équiper. Passez-en une par /acheter, ou semez le décor.');

            return self::FAILURE;
        }

        try {
            $intent = (new StripeClient($secret))->paymentIntents->create([
                'amount' => $order->total_cents,
                'currency' => 'eur',
                'payment_method' => 'pm_card_visa',
                'confirm' => true,
                'automatic_payment_methods' => ['enabled' => true, 'allow_redirects' => 'never'],
                'description' => 'Décor : commande '.$order->getKey(),
            ]);
        } catch (ApiErrorException $e) {
            $this->components->error('Stripe a refusé : '.$e->getMessage());

            return self::FAILURE;
        }

        // `saveQuietly` : on répare une référence, on ne rejoue pas le cycle
        // de vie de la commande, et un observateur qui renverrait un message
        // de confirmation serait un faux positif de plus dans la vérification.
        $order->forceFill(['stripe_payment_intent_id' => $intent->id])->saveQuietly();

        $this->components->info(sprintf(
            'Commande %s équipée du paiement %s (%s €). Le remboursement partiel du bloc 11 peut se jouer.',
            $order->getKey(),
            $intent->id,
            number_format($order->total_cents / 100, 2, ',', ' '),
        ));

        return self::SUCCESS;
    }

    private function order(): ?Order
    {
        $id = $this->option('order');

        if (is_string($id) && $id !== '') {
            return Order::query()->find($id);
        }

        return Order::query()
            ->whereNotNull('paid_at')
            ->orderByDesc('paid_at')
            ->first();
    }
}
