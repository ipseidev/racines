<?php

declare(strict_types=1);

namespace App\Services\Sms;

use Illuminate\Support\Facades\Log;

/**
 * Hors production, un vrai fournisseur n'écrit qu'à des numéros permis.
 *
 * Le décor sème des numéros de la forme `+336000xxxx` : des mobiles français
 * **plausibles**, appartenant peut-être à quelqu'un. Ils étaient malformés
 * jusqu'au 2026-09-05, ce qui les protégeait par accident ; les rendre
 * valides (T-166) a retiré cette protection au moment même où les clés Twilio
 * arrivaient dans le `.env`.
 *
 * Un `engine:tick` ou un `prompts:dispatch-due` sur ce décor suffirait alors à
 * écrire à des inconnus. Pour un produit qui s'adresse à des personnes de plus
 * de quatre-vingts ans, c'est la faute qu'on ne rattrape pas : on ne peut pas
 * décommander un SMS, ni expliquer à quelqu'un pourquoi un service qu'il ne
 * connaît pas lui a écrit.
 *
 * La liste est donc **obligatoire hors production**, et vide par défaut : rien
 * ne part tant qu'on n'a pas nommé les numéros de l'équipe. En production elle
 * n'existe pas — ce décorateur n'y est pas monté.
 */
final readonly class AllowlistSmsSender implements SmsSender
{
    /** @param list<string> $allowed */
    public function __construct(
        private SmsSender $inner,
        private array $allowed,
    ) {}

    /**
     * L'expéditeur enveloppé.
     *
     * `prod:sms` a besoin d'annoncer l'expéditeur que verra le téléphone avant
     * d'envoyer quoi que ce soit, et cette réponse n'appartient qu'à
     * `TwilioSmsSender` : la recalculer ici en ferait une seconde vérité.
     */
    public function inner(): SmsSender
    {
        return $this->inner;
    }

    public function send(string $toE164, string $body, ?string $dedupeKey = null): SmsResult
    {
        if (! $this->permits($toE164)) {
            // `info` et non `warning` : en développement c'est le
            // fonctionnement normal, et un journal qui crie à chaque message
            // retenu finit par ne plus être lu.
            Log::info('sms.withheld', [
                'to_masked' => LogSmsSender::mask($toE164),
                'reason' => 'hors liste blanche (SMS_ALLOWLIST)',
            ]);

            return SmsResult::refused('Numéro hors de la liste blanche de développement.');
        }

        return $this->inner->send($toE164, $body, $dedupeKey);
    }

    private function permits(string $toE164): bool
    {
        $cible = self::normalise($toE164);

        foreach ($this->allowed as $permis) {
            if (self::normalise($permis) === $cible && $cible !== '') {
                return true;
            }
        }

        return false;
    }

    /** Un numéro recopié depuis un carnet d'adresses porte des espaces. */
    private static function normalise(string $numero): string
    {
        return (string) preg_replace('/[^0-9+]/', '', $numero);
    }
}
