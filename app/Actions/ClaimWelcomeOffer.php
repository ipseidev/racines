<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ConsentKind;
use App\Models\ConsentText;
use App\Models\Lead;
use App\Notifications\WelcomeOfferNotification;
use App\Settings\PilotSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Une adresse contre un code de réduction (T-141).
 *
 * Une ligne par adresse : redemander ne crée pas un second code, on renvoie
 * le même. Un code expiré est remplacé, un code déjà utilisé ne l'est pas :
 * la réduction de bienvenue vaut une fois par personne, pas une fois par
 * commande.
 *
 * La demande de nouvelles est séparée du code, et jamais requise : l'adresse
 * sert à envoyer le code, et à rien d'autre si la case n'est pas cochée
 * (même règle que la case marketing du tunnel, bloc 10 §6.3).
 */
final readonly class ClaimWelcomeOffer
{
    /**
     * @param  array{ip?: string|null, user_agent?: string|null}  $context
     */
    public function handle(string $email, bool $wantsNews, array $context = []): Lead
    {
        $settings = app(PilotSettings::class);

        $lead = DB::transaction(function () use ($email, $wantsNews, $context, $settings): Lead {
            $lead = Lead::query()->where('email_hash', Lead::hashEmail($email))->lockForUpdate()->first();

            if (! $lead instanceof Lead) {
                $lead = new Lead([
                    'email' => trim($email),
                    'source' => Lead::SOURCE_LANDING,
                    // Copié, jamais relu : changer le réglage ne change pas
                    // ce qu'on a promis à quelqu'un.
                    'discount_percent' => $settings->welcome_offer_discount_percent,
                    'code_expires_at' => now()->addDays(Lead::CODE_LIFETIME_DAYS),
                ]);

                $lead->email_hash = Lead::hashEmail($email);
                $lead->discount_code = Lead::generateCode();
            } elseif ($lead->codeStatus() === 'expired') {
                $lead->discount_code = Lead::generateCode();
                $lead->discount_percent = $settings->welcome_offer_discount_percent;
                $lead->code_expires_at = now()->addDays(Lead::CODE_LIFETIME_DAYS);
            }

            if ($wantsNews && $lead->news_opted_in_at === null) {
                $lead->news_opted_in_at = now();
                $lead->consent_text_version = ConsentText::current(ConsentKind::MarketingEmail)?->version;
                $lead->ip_hash = RecordConsent::hashIp($context['ip'] ?? null);
                $lead->user_agent = $context['user_agent'] ?? null;
            }

            $lead->save();

            return $lead;
        });

        // Un code déjà utilisé ne se renvoie pas : la personne a acheté, la
        // réduction a servi. On ne dit rien de plus, pour ne pas révéler à
        // qui tape une adresse qu'elle a passé commande.
        if ($lead->codeUsable()) {
            $lead->notify(new WelcomeOfferNotification($lead));
        }

        Log::info('welcome_offer.claimed', [
            'lead_id' => $lead->id,
            'wants_news' => $wantsNews,
            'code_status' => $lead->codeStatus(),
        ]);

        return $lead;
    }
}
