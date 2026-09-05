<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Payments\ProductCatalogue;
use App\Services\Payments\StripeProductCatalogue;
use App\Settings\PilotSettings;
use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * Le catalogue vendable, créé et vérifié depuis les réglages.
 *
 * **Pourquoi une commande plutôt que le tableau de bord.** Les montants ont
 * une seule source, `PilotSettings`, et un prix créé chez Stripe qui en
 * diverge ne se voit qu'au moment de payer — devant un client. Le compte de
 * test a été rempli à la main le 2026-09-05 en confrontant chaque montant aux
 * réglages ; cette commande fait de cette précaution une propriété du produit
 * plutôt qu'une vigilance qu'il faut se rappeler d'avoir.
 *
 * **Pourquoi la clé se demande.** Le catalogue live se crée avec une clé live,
 * qui n'a rien à faire dans le `.env` d'une machine de développement : le
 * tunnel y fonctionne, et un clic sur « Payer » prendrait une vraie carte. La
 * clé est donc saisie masquée, le temps de la commande, et n'est écrite nulle
 * part — ni fichier, ni historique du terminal.
 *
 * **Elle n'écrit rien sans `--write`.** Créer des objets dans le compte de
 * quelqu'un se demande, ne se suppose pas. Et sur un compte live, elle
 * confirme une seconde fois en nommant le compte.
 */
#[AsCommand(name: 'stripe:catalogue', description: 'Crée ou vérifie les produits, prix et coupons chez Stripe')]
final class StripeCatalogue extends Command
{
    /** @var string */
    protected $signature = 'stripe:catalogue
        {--write : Crée ce qui manque ; sans cette option, la commande se contente de comparer}
        {--ask : Demande la clé au lieu de prendre celle de la configuration — le chemin du live}';

    /** @var string */
    protected $description = 'Crée ou vérifie les produits, prix et coupons chez Stripe';

    private const COUPON_KEY = 'welcome';

    public function handle(PilotSettings $settings): int
    {
        $catalogue = $this->catalogue();

        $this->components->twoColumnDetail('Compte', $catalogue->account());
        $this->newLine();

        $voulu = self::desired($settings);
        $existant = $catalogue->prices();

        $aCreer = [];
        $divergences = [];

        foreach ($voulu as $key => $article) {
            $ici = $existant[$key] ?? null;

            if ($ici === null) {
                $aCreer[$key] = $article;
                $this->ligne($key, $article['amount'], 'à créer', 'yellow');

                continue;
            }

            if ($ici['amount'] !== $article['amount']) {
                $divergences[$key] = [$ici['amount'], $article['amount']];
                $this->ligne($key, $article['amount'], 'diverge : Stripe '.self::euros($ici['amount']), 'red');

                continue;
            }

            $this->ligne($key, $article['amount'], 'conforme', 'green');
        }

        $coupon = $catalogue->coupon(self::COUPON_KEY);
        $couponVoulu = $settings->welcome_offer_discount_percent;
        $couponACreer = $coupon === null;

        if ($couponACreer) {
            $this->ligne('coupon welcome', null, $couponVoulu.' % — à créer', 'yellow');
        } elseif ((int) $coupon['percent'] !== $couponVoulu) {
            $divergences['coupon'] = [(int) $coupon['percent'], $couponVoulu];
            $this->ligne('coupon welcome', null, 'diverge : Stripe '.$coupon['percent'].' %', 'red');
        } else {
            $this->ligne('coupon welcome', null, $couponVoulu.' % — conforme', 'green');
        }

        if ($divergences !== []) {
            $this->newLine();
            $this->components->error('Un prix Stripe ne se modifie pas, il se remplace : la divergence ne se corrige pas toute seule.');
            $this->line('  Archiver le prix périmé dans le tableau de bord, puis relancer — ou corriger le réglage');
            $this->line('  si c\'est lui qui a tort. Créer un second prix laisserait deux prix vivants pour le même');
            $this->line('  article : celui que la page affiche, et celui que la caisse encaisse.');

            return self::FAILURE;
        }

        if (! $this->option('write')) {
            $this->newLine();
            $this->components->info('Aucune écriture : relancez avec --write pour créer ce qui manque.');

            return self::SUCCESS;
        }

        if ($aCreer === [] && ! $couponACreer) {
            $this->newLine();
            $this->components->info('Le catalogue est déjà complet et conforme.');
            $this->configuration($existant, $coupon['id']);

            return self::SUCCESS;
        }

        if ($catalogue->isLive() && ! $this->confirm('Écrire pour de vrai sur ce compte ?', false)) {
            $this->components->warn('Rien n\'a été écrit.');

            return self::FAILURE;
        }

        foreach ($aCreer as $key => $article) {
            $id = $catalogue->createPrice($key, $article['name'], $article['description'], $article['amount']);
            $existant[$key] = ['price' => $id, 'amount' => $article['amount']];
            $this->components->twoColumnDetail('créé  '.$key, $id);
        }

        $couponId = $couponACreer ? null : $coupon['id'];

        if ($couponACreer) {
            $couponId = $catalogue->createCoupon(self::COUPON_KEY, 'Bienvenue '.$couponVoulu.' pour cent', $couponVoulu);
            $this->components->twoColumnDetail('créé  coupon welcome', $couponId);
        }

        $this->configuration($existant, $couponId);

        return self::SUCCESS;
    }

    /**
     * Les articles vendables et leur montant, **lus dans les réglages**.
     *
     * @return array<string, array{name: string, description: string, amount: int}>
     */
    private static function desired(PilotSettings $settings): array
    {
        [$preventeA, $preventeB] = $settings->prevente_prices_cents;

        return [
            'pilot' => [
                'name' => 'Le livre relié et l\'année de questions',
                'description' => 'Un an de questions hebdomadaires et le livre relié.',
                'amount' => $settings->pilot_price_cents,
            ],
            'prevente_99' => [
                'name' => 'Prévente — variante A',
                'description' => 'Test de demande R-3, première variante.',
                'amount' => (int) $preventeA,
            ],
            'prevente_129' => [
                'name' => 'Prévente — variante B',
                'description' => 'Test de demande R-3, seconde variante.',
                'amount' => (int) $preventeB,
            ],
            'extra_copy' => [
                'name' => 'Exemplaire supplémentaire',
                'description' => 'Un exemplaire de plus du livre relié.',
                'amount' => $settings->extra_copy_price_cents,
            ],
            'ebook' => [
                'name' => 'Livre numérique',
                'description' => 'La version numérique du livre.',
                'amount' => $settings->ebook_price_cents,
            ],
            'phone_option' => [
                'name' => 'Enregistrement par téléphone',
                'description' => 'Option D-9 du pilote : un appel humain, plafonné.',
                'amount' => $settings->phone_option_price_cents,
            ],
        ];
    }

    private function catalogue(): ProductCatalogue
    {
        if (app()->bound(ProductCatalogue::class)) {
            return app(ProductCatalogue::class);
        }

        $key = $this->option('ask')
            ? (string) $this->secret('Clé Stripe (masquée, jamais écrite)')
            : (string) config('cashier.secret');

        return new StripeProductCatalogue($key);
    }

    private function ligne(string $key, ?int $amount, string $etat, string $couleur): void
    {
        $montant = $amount === null ? '' : str_pad(self::euros($amount), 12, ' ', STR_PAD_LEFT);

        $this->line(sprintf('  %-16s %s   <fg=%s>%s</>', $key, $montant, $couleur, $etat));
    }

    private static function euros(int $cents): string
    {
        return number_format($cents / 100, 2, ',', ' ').' €';
    }

    /**
     * @param  array<string, array{price: string, amount: int}>  $prices
     */
    private function configuration(array $prices, ?string $couponId): void
    {
        $this->newLine();
        $this->components->info('À reporter dans l\'environnement de destination :');

        foreach ($prices as $key => $prix) {
            $this->line('  STRIPE_PRICE_'.mb_strtoupper($key).'='.$prix['price']);
        }

        if ($couponId !== null) {
            $this->line('  STRIPE_COUPON_WELCOME='.$couponId);
        }
    }
}
