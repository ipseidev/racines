<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Ads\MetaConversions;
use App\Support\Money;
use Illuminate\Console\Command;

/**
 * « Est-ce que Meta reçoit vraiment nos achats ? »
 *
 * Entre « le token est valide » et « l'achat arrive dans le compte
 * publicitaire », il reste tout ce qui échoue vraiment : un jeton pour le
 * mauvais jeu de données, une version d'API refusée, un paramètre que Meta
 * rejette en silence. `prod:check` ne peut pas le savoir — un envoi coûte un
 * évènement dans les données. Cette commande le fait, une fois, à la demande.
 *
 * Deux garde-fous, chacun pour une faute précise.
 *
 * **Le code de test est exigé**, sauf `--force`. Sans lui, l'évènement entre
 * dans l'optimisation réelle : Meta apprendrait qu'un achat de quatre-vingt-
 * neuf euros vient d'une adresse qui n'existe pas, et irait chercher des
 * acheteurs qui lui ressemblent. Un faux achat coûte plus cher qu'un test
 * raté.
 *
 * **L'adresse est manifestement fictive** et le numéro de commande porte son
 * préfixe : si l'évènement finit malgré tout dans les données, il se reconnaît
 * au premier coup d'œil.
 */
final class SendTestMetaPurchase extends Command
{
    protected $signature = 'prod:meta
        {--code= : le code de test de l’évènement, lu dans le Gestionnaire d’évènements}
        {--valeur=8900 : le montant en centimes}
        {--force : envoyer sans code de test, dans les données réelles}';

    protected $description = 'Envoie un achat d’essai à l’API de conversions et montre la réponse de Meta';

    public function handle(MetaConversions $meta): int
    {
        if (config('services.meta.enabled') !== true) {
            $this->components->error(
                'La mesure est éteinte : META_PIXEL_ENABLED=false. Aucune campagne ne peut apprendre, rien ne part.',
            );

            return self::FAILURE;
        }

        if ((string) config('services.meta.capi_token') === '') {
            $this->components->error(
                'META_CAPI_TOKEN est vide : le pixel mesure les clics, mais aucun achat ne remonte. '
                .'Une campagne optimise alors sur des visites de page.',
            );

            return self::FAILURE;
        }

        $code = (string) $this->option('code');
        $force = (bool) $this->option('force');

        if ($code === '' && ! $force) {
            $this->components->error(
                'Sans --code, l’achat d’essai entre dans l’optimisation réelle et apprend à Meta à '
                .'chercher de faux acheteurs. Le code se lit dans le Gestionnaire d’évènements, '
                .'onglet « Tester les évènements ». Passez --force pour l’envoyer quand même.',
            );

            return self::FAILURE;
        }

        // Le code de test vient de la ligne de commande : la variable
        // d'environnement du serveur reste vide, comme elle doit l'être.
        config()->set('services.meta.test_code', $code);

        $cents = max(0, (int) $this->option('valeur'));
        $reference = 'essai-'.now()->format('YmdHis');

        $this->components->twoColumnDetail('Jeu de données', (string) config('services.meta.pixel_id'));
        $this->components->twoColumnDetail('Montant', Money::euros($cents));
        $this->components->twoColumnDetail('Référence', 'order-'.$reference);
        $this->components->twoColumnDetail(
            'Destination',
            $code === '' ? '<fg=red>les données réelles</>' : 'les évènements de test ('.$code.')',
        );

        $response = $meta->purchase(
            orderId: $reference,
            totalCents: $cents,
            currency: 'eur',
            // Un domaine réservé aux exemples (RFC 2606) : cette adresse ne
            // peut appartenir à personne.
            email: 'essai-conversions@example.invalid',
            click: ['ua' => 'prod:meta', 'url' => url('/')],
            buyer: ['name' => 'Essai Conversions'],
        );

        if ($response === null) {
            $this->components->error(
                'Meta a refusé l’envoi. Le détail est dans les journaux, sous ads.meta_purchase_refused '
                .'ou ads.meta_purchase_failed — la cause la plus fréquente est un jeton émis pour un '
                .'autre jeu de données.',
            );

            return self::FAILURE;
        }

        $this->newLine();
        $this->components->twoColumnDetail(
            'Évènements reçus',
            (string) ($response['events_received'] ?? 0),
        );
        $this->components->twoColumnDetail(
            'Numéro de trace',
            (string) ($response['fbtrace_id'] ?? '—'),
        );

        $this->newLine();
        $this->components->info(
            $code === ''
                ? 'Envoyé dans les données réelles. Vérifiez la ligne dans le Gestionnaire d’évènements.'
                : 'Envoyé. L’évènement doit apparaître sous quelques secondes dans « Tester les évènements ».',
        );

        return self::SUCCESS;
    }
}
