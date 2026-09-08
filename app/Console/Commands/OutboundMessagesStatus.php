<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\Channel;
use App\Enums\OutboundMessageStatus;
use App\Models\OutboundMessage;
use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Throwable;
use Twilio\Rest\Client;

/**
 * « Je n'ai jamais reçu le SMS. »
 *
 * La phrase qui ne se diagnostique pas depuis l'application, et voilà
 * pourquoi : `outbound_messages` sait ce que **nous** avons fait — la ligne
 * écrite, l'envoi accepté, l'identifiant rendu — et rien de ce que
 * l'opérateur en a fait ensuite. Cette moitié-là arrive par le rappel de
 * statut, un POST de Twilio sur `/webhooks/twilio/status`. Si ce rappel
 * n'aboutit pas — domaine pas encore branché, pare-feu, application derrière
 * un tunnel — la ligne ne quitte jamais `sent`, et « accepté » se lit comme
 * « reçu ».
 *
 * Or `accepted` ne veut rien dire pour un téléphone français. Twilio rend un
 * identifiant, puis l'opérateur filtre : un expéditeur alphanumérique non
 * déposé en France est **jeté sans erreur visible côté API**. C'est le seul
 * défaut du produit qu'aucune ligne de journal ne montre.
 *
 * La commande va donc chercher la vérité **à la source** plutôt que de
 * l'attendre : pour chaque SMS récent, elle demande à Twilio l'état réel de
 * l'identifiant, avec son code d'erreur. Elle ne remplace pas le rappel de
 * statut — celui-ci nourrit le moteur de complétion, qui doit distinguer
 * « lien non ouvert » de « SMS jamais arrivé » — elle le contourne le jour
 * où il manque, et dit qu'il manque.
 *
 * Lecture seule, et rien n'est envoyé : c'est `prod:sms` qui écrit.
 */
#[AsCommand(name: 'prod:messages', description: 'Les derniers messages sortants, et ce que le fournisseur en dit vraiment')]
final class OutboundMessagesStatus extends Command
{
    /** @var string */
    protected $signature = 'prod:messages
        {--nombre=10 : combien de messages remonter}
        {--gabarit= : ne garder qu’un gabarit, par exemple gift_invitation}
        {--local : sans interroger le fournisseur}';

    /** @var string */
    protected $description = 'Les derniers messages sortants, et ce que le fournisseur en dit vraiment';

    /**
     * Ce que les codes d'erreur de Twilio veulent dire, et quoi faire.
     *
     * Seuls ceux qu'on peut vraiment rencontrer en France, avec la sortie et
     * non la définition : « 30008 » ne se lit pas, « l'opérateur a filtré »
     * se lit.
     *
     * @var array<int, string>
     */
    private const CODES = [
        30_003 => 'téléphone éteint ou injoignable — à réessayer plus tard.',
        30_004 => 'numéro bloqué par l’opérateur.',
        30_005 => 'numéro inconnu ou hors service : vérifier le chiffre à chiffre.',
        30_006 => 'ligne fixe, ou incapable de recevoir un SMS.',
        30_007 => 'l’opérateur a filtré le message — en France, c’est presque toujours '
            .'un expéditeur alphanumérique non déposé, ou un contenu jugé promotionnel.',
        30_008 => 'l’opérateur n’a rien voulu dire de plus. En France, sur un expéditeur '
            .'alphanumérique, c’est le symptôme habituel d’un identifiant non déposé.',
        21_612 => 'la messagerie vers ce pays n’est pas activée : Messaging → Geo permissions.',
        21_606 => 'l’expéditeur est refusé : sur un compte d’essai l’alphanumérique n’est pas '
            .'ouvert, et TWILIO_FROM doit porter un numéro acheté sur le compte.',
        21_659 => 'l’expéditeur n’appartient pas au compte.',
    ];

    public function handle(): int
    {
        $messages = OutboundMessage::query()
            ->when(
                is_string($this->option('gabarit')) && $this->option('gabarit') !== '',
                fn ($query) => $query->where('template', $this->option('gabarit')),
            )
            ->orderByDesc('created_at')
            ->limit(max(1, (int) $this->option('nombre')))
            ->get();

        if ($messages->isEmpty()) {
            $this->components->info('Aucun message sortant. Rien n’est encore parti d’ici.');

            return self::SUCCESS;
        }

        $client = $this->client();
        $muets = 0;

        $this->newLine();

        foreach ($messages as $message) {
            $this->render($message, $client, $muets);
        }

        $this->newLine();

        if ($muets > 0) {
            // Le rappel de statut manquant est un défaut à lui seul : sans
            // lui, le moteur de complétion ne distingue pas « lien non
            // ouvert » de « SMS jamais arrivé », et se tait quand il faudrait
            // relancer.
            $this->components->warn(sprintf(
                '%d message(s) restés à « sent » : le rappel de statut n’est pas arrivé.',
                $muets,
            ));
            $this->line('  <fg=gray>Vérifie que '.route('webhooks.twilio.status').'</>');
            $this->line('  <fg=gray>est joignable depuis l’extérieur, sans authentification.</>');
            $this->newLine();
        }

        return self::SUCCESS;
    }

    private function render(OutboundMessage $message, ?Client $client, int &$muets): void
    {
        $this->components->twoColumnDetail(
            sprintf(
                '<options=bold>%s</> <fg=gray>%s</>',
                $message->template,
                $message->to_masked,
            ),
            sprintf(
                '%s <fg=gray>%s</>',
                self::couleur($message->status),
                $message->created_at?->diffForHumans() ?? '',
            ),
        );

        if ($message->status_detail !== null) {
            $this->line('      <fg=gray>chez nous : </>'.$message->status_detail);
        }

        if ($message->channel !== Channel::Sms || $message->provider_message_id === null) {
            return;
        }

        if ($message->status === OutboundMessageStatus::Sent) {
            $muets++;
        }

        if ($client === null) {
            $this->line('      <fg=gray>identifiant : </>'.$message->provider_message_id);

            return;
        }

        $this->twilio($client, $message->provider_message_id);
    }

    /**
     * Ce que Twilio dit de cet identifiant, maintenant.
     *
     * C'est la seule autorité : notre ligne ne sait que ce que l'API a
     * accepté, et un message accepté puis jeté par l'opérateur a exactement
     * la même trace chez nous qu'un message reçu.
     */
    private function twilio(Client $client, string $sid): void
    {
        try {
            // `$client->messages` est la propriété typée du SDK ;
            // `$client->messages($sid)` passe par un `__call` magique que
            // l'analyse statique ne voit pas. Le contexte explicite dit la
            // même chose et se vérifie.
            $distant = $client->messages->getContext($sid)->fetch();
        } catch (Throwable $exception) {
            $this->line('      <fg=yellow>Twilio ne répond pas sur '.$sid.' : </>'.$exception->getMessage());

            return;
        }

        $etat = (string) $distant->status;
        $code = $distant->errorCode === null ? null : (int) $distant->errorCode;

        $this->line(sprintf(
            '      <fg=gray>chez Twilio : </>%s<fg=gray> — de %s</>',
            self::etatTwilio($etat),
            (string) $distant->from,
        ));

        if ($code === null) {
            return;
        }

        $this->line('      <fg=red>erreur '.$code.'</><fg=gray> : </>'.(
            self::CODES[$code] ?? (string) $distant->errorMessage
        ));
    }

    private static function etatTwilio(string $etat): string
    {
        return match ($etat) {
            'delivered' => '<fg=green>delivered</> <fg=gray>— le téléphone l’a reçu</>',
            'sent', 'queued', 'accepted', 'sending' => '<fg=yellow>'.$etat.'</> <fg=gray>— accepté, pas encore remis</>',
            'undelivered', 'failed' => '<fg=red>'.$etat.'</> <fg=gray>— l’opérateur ne l’a pas remis</>',
            default => $etat,
        };
    }

    private static function couleur(OutboundMessageStatus $statut): string
    {
        return match (true) {
            $statut->reached() => '<fg=green>'.$statut->value.'</>',
            $statut->isFailure() => '<fg=red>'.$statut->value.'</>',
            default => '<fg=yellow>'.$statut->value.'</>',
        };
    }

    /**
     * Le client Twilio, ou `null` quand la question n'a pas de sens.
     *
     * Pas de doublon monté ici : hors production le fournisseur est `log` ou
     * `fake`, aucun identifiant n'existe chez Twilio, et interroger serait
     * une question sur des messages qui ne sont jamais partis.
     */
    private function client(): ?Client
    {
        if ($this->option('local') === true) {
            return null;
        }

        if ((string) config('services.sms.provider') !== 'twilio') {
            return null;
        }

        $sid = (string) config('services.twilio.sid');
        $token = (string) config('services.twilio.token');

        return $sid === '' || $token === '' ? null : new Client($sid, $token);
    }
}
