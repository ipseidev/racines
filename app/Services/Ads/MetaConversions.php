<?php

declare(strict_types=1);

namespace App\Services\Ads;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * L'API de conversions de Meta : l'achat, envoyé par le serveur (T-226).
 *
 * C'est la moitié qui compte. Le pixel du navigateur mesure l'intention, mais
 * l'achat lui échappe dès qu'un bloqueur de publicité est installé, dès que
 * Safari coupe le cookie, dès que l'onglet est fermé pendant le paiement chez
 * Stripe. Ici, l'événement part du webhook : il arrive toujours, et c'est sur
 * lui que la campagne apprend.
 *
 * Trois règles de prudence, chacune pour une raison précise.
 *
 * **Rien ne part sans le verrou.** `META_PIXEL_ENABLED` et le jeton doivent
 * être là, sinon la méthode rend `false` sans toucher au réseau : un décor de
 * développement ne remplit pas le compte publicitaire (T-61), et la suite de
 * tests ne fait aucun appel sortant.
 *
 * **L'adresse est hachée, jamais envoyée en clair.** Meta l'exige, et c'est
 * de toute façon la seule forme sous laquelle nous acceptons de la
 * transmettre : le SHA-256 d'une adresse minuscule et détourée suffit à
 * l'appariement.
 *
 * **L'identifiant d'événement est celui de la commande.** Un webhook rejoué —
 * Stripe en rejoue — enverrait sinon deux achats, et le coût d'acquisition
 * afficherait la moitié de sa valeur réelle.
 */
final class MetaConversions
{
    /**
     * Un achat, et la réponse de Meta — ou `null` si rien n'est parti.
     *
     * La réponse plutôt qu'un booléen : c'est ce qui permet à `prod:meta` de
     * montrer combien d'évènements Meta a reçus et son numéro de trace, les
     * deux seules choses à donner au support quand il faut le saisir.
     *
     * @param  array{fbp?: string, fbc?: string, ua?: string, url?: string}  $click  Ce que
     *                                                                               le navigateur savait au moment du paiement : les identifiants de
     *                                                                               clic posés par le pixel. Sans eux l'événement compte quand même,
     *                                                                               mais Meta l'apparie moins bien.
     * @param  array{id?: int, name?: string}  $buyer  L'acheteur, pour la
     *                                                 correspondance : son identifiant interne et son nom, hachés.
     *                                                 **Jamais** ceux du narrateur, qui sont les coordonnées d'un
     *                                                 tiers n'ayant rien accepté chez Meta.
     * @return array<string, mixed>|null La réponse décodée de Meta, ou `null`
     *                                   quand rien n'est parti : mesure éteinte, jeton absent, ou refus.
     */
    public function purchase(
        string $orderId,
        int $totalCents,
        string $currency,
        string $email,
        array $click = [],
        array $buyer = [],
    ): ?array {
        $token = (string) config('services.meta.capi_token');
        $pixel = (string) config('services.meta.pixel_id');

        if ($token === '' || $pixel === '' || config('services.meta.enabled') !== true) {
            return null;
        }

        $user = ['em' => [self::hash($email)]];

        // Les trois valeurs que Meta veut **en clair** : deux identifiants de
        // cookie et l'agent utilisateur. Les hacher les rendrait inutiles.
        foreach (['fbp' => 'fbp', 'fbc' => 'fbc', 'ua' => 'client_user_agent'] as $key => $field) {
            $value = (string) ($click[$key] ?? '');

            if ($value !== '') {
                $user[$field] = $value;
            }
        }

        // L'identifiant interne : il rapproche deux commandes du même
        // acheteur sans rien dire de lui. Haché, comme le reste.
        if (($buyer['id'] ?? 0) > 0) {
            $user['external_id'] = [self::hash((string) $buyer['id'])];
        }

        /*
         * Le nom, coupé au premier espace.
         *
         * Approximatif — « Marie Claire Dupont » donne « marie » puis « claire
         * dupont » — et c'est sans conséquence : Meta compare des empreintes
         * et ignore celles qui ne tombent pas. Un nom mal coupé n'apparie
         * rien, il ne fausse rien.
         */
        $name = trim((string) ($buyer['name'] ?? ''));

        if ($name !== '') {
            $space = mb_strpos($name, ' ');

            $user['fn'] = [self::hash($space === false ? $name : mb_substr($name, 0, $space))];

            if ($space !== false) {
                $user['ln'] = [self::hash(mb_substr($name, $space + 1))];
            }
        }

        $event = [
            'event_name' => 'Purchase',
            'event_time' => time(),
            // L'identifiant de la commande : c'est lui qui dédoublonne un
            // webhook rejoué, chez Meta comme chez nous.
            'event_id' => 'order-'.$orderId,
            'action_source' => 'website',
            'user_data' => $user,
            'custom_data' => [
                'currency' => mb_strtoupper($currency),
                'value' => number_format($totalCents / 100, 2, '.', ''),
            ],
        ];

        if (($click['url'] ?? '') !== '') {
            $event['event_source_url'] = $click['url'];
        }

        $payload = ['data' => [$event]];
        $test = (string) config('services.meta.test_code');

        if ($test !== '') {
            $payload['test_event_code'] = $test;
        }

        $version = (string) config('services.meta.api_version');

        try {
            $response = Http::asJson()
                ->timeout(5)
                ->post(
                    "https://graph.facebook.com/{$version}/{$pixel}/events?access_token=".urlencode($token),
                    $payload,
                );

            if ($response->failed()) {
                // Le corps de la réponse, pas le jeton : celui-ci ne se
                // journalise jamais, et il est dans l'URL.
                Log::warning('ads.meta_purchase_refused', [
                    'order_id' => $orderId,
                    'status' => $response->status(),
                    'body' => mb_substr($response->body(), 0, 300),
                ]);

                return null;
            }

            Log::info('ads.meta_purchase_sent', [
                'order_id' => $orderId,
                'matched' => count($user),
            ]);

            $decoded = $response->json();

            return is_array($decoded) ? $decoded : [];
        } catch (Throwable $exception) {
            // Une mesure qui tombe ne fait pas tomber une commande : l'appel
            // est déjà hors du webhook, et l'exception s'arrête ici.
            Log::warning('ads.meta_purchase_failed', [
                'order_id' => $orderId,
                'reason' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    /** Le SHA-256 d'une adresse minuscule et détourée, comme Meta l'attend. */
    private static function hash(string $email): string
    {
        return hash('sha256', mb_strtolower(trim($email)));
    }
}
