<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\Channel;
use App\Models\FamilyMember;
use App\Models\Narrator;
use App\Models\OutboundMessage;
use App\Services\Sms\AllowlistSmsSender;
use App\Services\Sms\SmsSender;
use App\Services\Sms\TwilioSmsSender;
use App\Support\Brand;
use App\Support\SmsLength;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use Illuminate\Support\Str;
use Laravel\Prompts\Exceptions\NonInteractiveValidationException;

use function Laravel\Prompts\text;

/**
 * Un vrai SMS, à un numéro nommé, suivi jusqu'à la livraison.
 *
 * `prod:check` vérifie que Twilio accepte nos identifiants, et il ne peut pas
 * aller plus loin : un envoi n'est pas idempotent et il coûte de l'argent. Or
 * entre « le compte répond » et « le message arrive au nom de la marque sur un
 * téléphone français », il reste tout ce qui échoue vraiment — l'expéditeur
 * alphanumérique refusé par un opérateur, le rappel de statut injoignable, le
 * message découpé en trois morceaux, la messagerie vers la France jamais
 * activée sur le compte.
 *
 * C'est donc une commande qui **écrit à une personne**, et c'est ce qui dicte
 * ses garde-fous. Hors production, la liste blanche de T-174 la retient déjà ;
 * en production ce décorateur n'existe pas, alors :
 *
 *  - le numéro est **explicite**, jamais lu en base, et affiché en clair avant
 *    l'envoi — la faute qu'on rattrape est celle qu'on a vue ;
 *  - un numéro qui appartient à un narrateur ou à un proche du produit est
 *    refusé : on ne fait pas ses essais sur un client ;
 *  - le texte par défaut **dit ce qu'il est** et ne porte aucun lien. La faute
 *    de frappe la plus probable est un chiffre pour un autre : un faux prompt
 *    chez un inconnu serait exactement le smishing que le doc 04 §9 combat.
 *
 * La ligne `outbound_messages` n'est pas de la décoration : c'est par elle que
 * le rappel de statut retrouve le message et le fait passer en `delivered`.
 * Sans elle, on saurait que Twilio a **accepté**, ce qui n'a jamais voulu dire
 * que le téléphone a **reçu**.
 */
final class SendTestSms extends Command implements PromptsForMissingInput
{
    protected $signature = 'prod:sms
        {destinataire : le numéro, au format international : +33612345678}
        {--corps= : un autre texte que le message d’essai}
        {--attendre=45 : secondes d’attente du rappel de livraison, 0 pour ne pas attendre}
        {--force : sans demander confirmation}';

    protected $description = 'Envoie un vrai SMS à un numéro nommé et le suit jusqu’à la livraison';

    /**
     * Le numéro manquant est demandé, et non reproché.
     *
     * Sans cela, Symfony répond « Not enough arguments » — en anglais, et sur
     * un serveur où l'on tape de mémoire. Le numéro reste explicite : il est
     * saisi, affiché en clair, puis confirmé.
     *
     * La question passe par une fermeture et non par une simple chaîne, parce
     * que `laravel/prompts` **lève** quand il n'y a personne pour répondre :
     * dans un script, une tâche planifiée ou un `docker compose exec` sans
     * terminal, la trace d'exception remplacerait le message de Symfony par
     * quelque chose de pire. On rend alors la main à `handle()`, qui sait le
     * dire en une ligne.
     *
     * @return array<string, \Closure(): string>
     */
    protected function promptForMissingArgumentsUsing(): array
    {
        return [
            'destinataire' => function (): string {
                try {
                    return text(
                        label: 'À quel numéro ?',
                        placeholder: '+33612345678',
                        validate: fn (?string $value): ?string => self::normalise((string) $value) === ''
                            ? 'Il faut un numéro, au format international.'
                            : null,
                    );
                } catch (NonInteractiveValidationException) {
                    return '';
                }
            },
        ];
    }

    public function handle(): int
    {
        $to = self::normalise((string) $this->argument('destinataire'));

        if ($to === '') {
            $this->components->error('Il faut un numéro : prod:sms +33612345678');

            return self::FAILURE;
        }

        if (preg_match('/^\+[1-9]\d{7,14}$/', $to) !== 1) {
            $this->components->error('Le numéro doit être au format international, indicatif compris : +33612345678.');

            return self::FAILURE;
        }

        $provider = (string) config('services.sms.provider');

        if ($provider !== 'twilio') {
            $this->components->error(sprintf(
                'Le fournisseur SMS est « %s » : rien de réel ne partirait, et l’essai ne prouverait rien. Il faut SMS_PROVIDER=twilio.',
                $provider === '' ? 'non réglé' : $provider,
            ));

            return self::FAILURE;
        }

        if ($this->belongsToSomeone($to) && ! $this->option('force')) {
            $this->components->error(
                'Ce numéro est celui d’un narrateur ou d’un proche du produit. On ne fait pas ses essais sur '
                .'un client : prends un numéro de l’équipe, ou --force si c’est bien le tien.',
            );

            return self::FAILURE;
        }

        $sender = app(SmsSender::class);
        $body = $this->body();
        $from = $this->senderFor($sender, $to);

        if ($from === '') {
            $this->components->error(
                'Aucun expéditeur : l’indicatif de ce numéro n’accepte pas un expéditeur alphanumérique et '
                .'TWILIO_FROM est vide. Twilio refuserait l’envoi.',
            );

            return self::FAILURE;
        }

        $this->recap($to, $from, $body);

        if (! $this->option('force') && ! $this->confirm(sprintf('Envoyer ce SMS au %s, pour de vrai ?', $to), false)) {
            $this->components->warn('Rien n’est parti.');

            return self::SUCCESS;
        }

        $message = $this->trace($to);
        $result = $sender->send($to, $body, $message->dedupe_key);

        if (! $result->accepted) {
            $message->markFailed($result->error ?? 'refus du fournisseur');
            $this->refusal($result->error ?? '');

            return self::FAILURE;
        }

        $message->markSent($result->providerMessageId, $provider);
        $this->components->info(sprintf(
            'Accepté par Twilio, identifiant %s. Accepté n’est pas reçu.',
            $result->providerMessageId ?? 'inconnu',
        ));

        return $this->follow($message);
    }

    /**
     * Le texte par défaut ne ressemble pas à un message du produit, et c'est
     * volontaire : il peut arriver chez quelqu'un qui n'a rien commandé.
     */
    private function body(): string
    {
        $custom = $this->option('corps');

        if (is_string($custom) && $custom !== '') {
            return $custom;
        }

        return sprintf(
            '%s : message d’essai technique envoyé par notre équipe. Aucune action de votre part, aucun lien à ouvrir.',
            Brand::name(),
        );
    }

    /**
     * L'expéditeur que verra le téléphone, demandé à la seule autorité en la
     * matière — jamais recalculé ici.
     *
     * `null` quand l'expéditeur n'est pas celui de Twilio : la question n'a
     * alors pas de réponse, ce qui n'est pas la même chose qu'une réponse
     * vide. Une chaîne vide, elle, veut dire que Twilio refuserait l'envoi.
     */
    private function senderFor(SmsSender $sender, string $to): ?string
    {
        $inner = $sender instanceof AllowlistSmsSender ? $sender->inner() : $sender;

        return $inner instanceof TwilioSmsSender ? $inner->senderFor($to) : null;
    }

    private function recap(string $to, ?string $from, string $body): void
    {
        $gsm7 = SmsLength::isGsm7($body);

        $this->newLine();
        $this->components->twoColumnDetail('<options=bold>Destinataire</>', "<options=bold>{$to}</>");
        $this->components->twoColumnDetail('Expéditeur', match (true) {
            $from === null => '<fg=yellow>inconnu : l’expéditeur monté n’est pas celui de Twilio</>',
            str_starts_with($from, '+') => $from.' <fg=yellow>(repli : cet indicatif refuse un expéditeur alphanumérique)</>',
            default => $from,
        });
        $this->components->twoColumnDetail('Rappel de statut', route('webhooks.twilio.status'));
        $this->components->twoColumnDetail('Longueur', sprintf(
            '%d caractères, %s, %d segment(s)',
            SmsLength::length($body),
            $gsm7 ? 'GSM-7' : 'UCS-2',
            SmsLength::segments($body),
        ));

        if (! $gsm7) {
            // « é », « è » et « à » sont dans l'alphabet GSM ; « ç », « ê »,
            // « ô », « ï » et l'apostrophe typographique n'y sont pas, et un
            // seul suffit à faire tomber la limite de 160 à 70.
            $this->components->twoColumnDetail('', '<fg=yellow>un caractère hors alphabet GSM ramène la limite de 160 à 70</>');
        }

        $this->newLine();
        $this->line("  <fg=gray>{$body}</>");
        $this->newLine();
    }

    /**
     * La trace, écrite **avant** l'envoi : c'est elle que le rappel de statut
     * retrouvera par l'identifiant du fournisseur.
     */
    private function trace(string $to): OutboundMessage
    {
        $message = new OutboundMessage([
            'channel' => Channel::Sms,
            'template' => 'prod_sms_essai',
            // Une clé neuve à chaque tour : l'idempotence du canal ne doit pas
            // empêcher de rejouer l'essai.
            'dedupe_key' => 'prod:sms:'.Str::random(16),
        ]);

        $message->to_hash = OutboundMessage::hashRecipient($to);
        $message->to_masked = OutboundMessage::mask($to);
        $message->save();

        return $message;
    }

    private function follow(OutboundMessage $message): int
    {
        $seconds = max(0, (int) $this->option('attendre'));

        if ($seconds === 0) {
            return self::SUCCESS;
        }

        $this->line(sprintf('  <fg=gray>Attente du rappel de livraison, %d s…</>', $seconds));

        $deadline = now()->addSeconds($seconds);

        while (now()->lessThan($deadline)) {
            usleep(2_000_000);
            $message->refresh();

            if ($message->status->reached()) {
                $this->components->info('Reçu par le téléphone. La chaîne est entière.');

                return self::SUCCESS;
            }

            if ($message->status->isFailure()) {
                $this->components->error(sprintf(
                    'L’opérateur a refusé la livraison : %s',
                    $message->status_detail ?? $message->status->value,
                ));

                return self::FAILURE;
            }
        }

        $this->components->warn(sprintf(
            'Aucun rappel en %d s. Le SMS est peut-être arrivé quand même — c’est le rappel qui manque, et sans '
            .'lui le moteur de complétion ne distingue pas « lien non ouvert » de « SMS jamais reçu ». Vérifie que '
            .'%s est joignable depuis l’extérieur.',
            $seconds,
            route('webhooks.twilio.status'),
        ));

        return self::SUCCESS;
    }

    /**
     * Un refus de Twilio est un code, et le code dit quoi faire.
     */
    private function refusal(string $error): void
    {
        $this->components->error('Twilio a refusé l’envoi : '.$error);

        if (str_contains($error, '20003') || str_contains($error, '401')) {
            $this->components->warn(
                'Le jeton d’authentification n’est pas valide pour ce compte. À recopier depuis console.twilio.com, '
                .'encadré « Account Info » — et vérifier qu’on n’a pas collé le secret d’une clé d’API (SK…) à la place.',
            );
        }

        if (str_contains($error, '21612') || str_contains($error, '21408')) {
            $this->components->warn(
                'La région refuse cet envoi : activer la messagerie vers ce pays dans Messaging → Geo permissions.',
            );
        }

        if (str_contains($error, '21606') || str_contains($error, '21659')) {
            $this->components->warn(
                'L’expéditeur est refusé : sur un compte d’essai l’alphanumérique n’est en général pas ouvert, et '
                .'TWILIO_FROM doit alors porter un numéro acheté sur le compte.',
            );
        }
    }

    /**
     * Le numéro d'un client n'est pas un banc d'essai.
     */
    private function belongsToSomeone(string $to): bool
    {
        return Narrator::query()->where('phone_e164', $to)->exists()
            || FamilyMember::query()->where('phone_e164', $to)->exists();
    }

    /** Un numéro recopié depuis un carnet d'adresses porte des espaces, et parfois un 00. */
    private static function normalise(string $number): string
    {
        $clean = (string) preg_replace('/[^0-9+]/', '', $number);

        return str_starts_with($clean, '00') ? '+'.mb_substr($clean, 2) : $clean;
    }
}
