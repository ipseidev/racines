<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\FulfillOrder;
use App\Actions\InviteFamilyMember;
use App\Actions\SaveCheckoutStep;
use App\Enums\AddressForm;
use App\Enums\Channel;
use App\Enums\ProjectStatus;
use App\Enums\TechComfort;
use App\Jobs\EraseProject;
use App\Models\CheckoutDraft;
use App\Models\Order;
use App\Models\Project;
use App\Models\Question;
use App\Models\Story;
use App\Models\User;
use App\Settings\PilotSettings;
use App\States\Story\Proposed;
use App\Support\Phone;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;
use Throwable;

/**
 * Un décor complet **en production**, pour dérouler le tunnel sur un vrai
 * téléphone.
 *
 * Toute la famille `demo:*` refuse de tourner en production, et c'est la bonne
 * règle : ces commandes servent à jouer un checkpoint sur une base jetable.
 * Celle-ci fait l'inverse, et le fait exprès. Ce qui casse entre « quelqu'un
 * paie » et « une famille écoute une voix » ne casse pas en local : c'est
 * Twilio qui refuse un expéditeur alphanumérique, un R2 dont la juridiction
 * refuse une URL présignée, un iPhone qui perd son enregistrement en changeant
 * d'onglet. `prod:check` dit que les clés répondent (T-208) ; il ne peut pas
 * dire que le parcours tient.
 *
 * Elle passe donc par **le vrai chemin d'achat** : un brouillon de tunnel
 * complet, puis `FulfillOrder` — la même action que le webhook Stripe appelle.
 * Le projet, la commande, ses lignes, le narrateur, la fiche d'écoute de
 * l'acheteuse, les consentements, le courriel de confirmation et le cadeau
 * programmé naissent comme pour un vrai client. Réimplémenter tout cela ici
 * aurait produit une seconde définition de « ce qu'un paiement déclenche », et
 * un second chemin finit toujours par diverger.
 *
 * Trois choses la distinguent d'un achat, et chacune est un choix :
 *
 *  - **Aucun argent ne bouge.** La session n'est pas une session Stripe : son
 *    identifiant porte le préfixe `demo_`, et `stripe_payment_intent_id` reste
 *    nul. C'est ce préfixe qui identifie le décor — donc rien à rembourser, et
 *    le bouton de remboursement du back-office le dira au lieu d'appeler
 *    Stripe dans le vide.
 *  - **Le projet n'appartient à aucune cohorte.** `FulfillOrder` range un
 *    achat dans la cohorte en cours ; y laisser le décor décalerait H0 et H1
 *    de la cohorte, soit les chiffres qui décident du Gate Phase 1. Sans
 *    cohorte, il sort de **toutes** les lectures par cohorte. Il reste compté
 *    dans la lecture globale : le dire est le prix de ne pas toucher aux
 *    métriques pour un outil d'essai.
 *  - **Le compte acheteur est un alias**, jamais l'adresse donnée telle
 *    quelle : `toi+demo@…` et non `toi@…`. Le décor ne doit pas pouvoir
 *    s'installer sur le compte d'administration — ni lui changer son mot de
 *    passe.
 *
 * Trois gestes, parce qu'un humain agit entre eux :
 *
 *  1. sans option : le décor, et l'invitation qui part vraiment ;
 *  2. `--question` : la première question, sans attendre la nuit que
 *     l'acceptation pose à dessein ;
 *  3. `--purge` : l'effacement, par le vrai chemin RGPD — les objets partent
 *     de R2, pas seulement les lignes.
 */
#[AsCommand(name: 'prod:demo', description: 'Fabrique un décor complet en production et déroule le tunnel sur un vrai téléphone')]
final class ProductionDemo extends Command
{
    /** @var string */
    protected $signature = 'prod:demo
        {--telephone= : le numéro du narrateur, au format international ; le tien par défaut}
        {--email= : la boîte qui recevra les courriels du décor ; le courriel de support sinon}
        {--prenom=Odette : le prénom du narrateur, celui que le SMS dira}
        {--canal=sms : le canal de l’invitation, « sms » ou « email »}
        {--proches=2 : combien de proches inviter, sur des alias + de la même boîte}
        {--motdepasse : réémet le mot de passe du compte acheteur, sans toucher au décor}
        {--question : la première question, sans attendre le lendemain}
        {--purge : efface le décor précédent et s’arrête là}
        {--force : sans demander confirmation}';

    /** @var string */
    protected $description = 'Fabrique un décor complet en production et déroule le tunnel sur un vrai téléphone';

    /**
     * Le numéro du fondateur.
     *
     * En dur, et c'est le point : le seul téléphone sur lequel il est légitime
     * de faire partir un vrai SMS de ce produit est le sien. Un défaut lu dans
     * l'environnement finirait par pointer ailleurs sans que personne ne le
     * relise, et `--telephone` reste là pour le numéro d'un coéquipier.
     */
    private const PHONE = '+33638503252';

    /**
     * Ce qui marque une commande de décor.
     *
     * Les identifiants de Stripe commencent par `cs_` : ce préfixe ne peut pas
     * en désigner une par accident, et une requête d'une ligne retrouve tout
     * le décor — c'est ce qui rend `--purge` sûr.
     */
    private const SESSION_PREFIX = 'demo_';

    /** L'alias du compte acheteur, et ceux des proches. */
    private const BUYER_ALIAS = 'demo';

    /** @var list<array{string, string}> Les proches invités, dans l'ordre. */
    private const PROCHES = [
        ['Camille', 'Petite-fille'],
        ['Julien', 'Fils'],
        ['Alice', 'Nièce'],
    ];

    private int $etape = 0;

    private const GIFT_MESSAGE = 'Maman, je voudrais garder tes histoires — celles que tu racontes toujours, et celles qu’on ne t’a jamais demandées.';

    public function handle(): int
    {
        if ($this->option('purge') === true) {
            return $this->purge();
        }

        /*
         * Avant `--question` et avant la fabrication : réémettre un mot de
         * passe est un geste **isolé**.
         *
         * Il modifiait la fabrication, ce qui en faisait un piège : la seule
         * façon de retrouver l'accès au compte était de relancer `prod:demo`,
         * qui efface le décor précédent — donc d'emporter le parcours en cours
         * pour récupérer le moyen de le regarder.
         */
        if ($this->option('motdepasse') === true) {
            return $this->resetPassword();
        }

        if ($this->option('question') === true) {
            return $this->firstQuestion();
        }

        return $this->create();
    }

    /**
     * Réémet le mot de passe du compte acheteur, et ne touche à rien d'autre.
     *
     * Le compte est celui du décor en cours, lu sur le projet plutôt que
     * recalculé depuis `--email` : on veut le compte qui possède le parcours
     * qu'on est en train d'éprouver, et non celui qu'une option retapée de
     * mémoire désignerait.
     */
    private function resetPassword(): int
    {
        $project = self::scenery()->latest('created_at')->first();
        $buyer = $project instanceof Project ? $project->owner : null;

        if (! $buyer instanceof User) {
            $this->components->error('Aucun décor en cours, donc aucun compte à rouvrir. Fabriques-en un : php artisan prod:demo');

            return self::FAILURE;
        }

        if ($buyer->isStaff()) {
            // La même garde qu'à la fabrication : un décor ne change pas le
            // mot de passe de celui qui répond au support.
            $this->components->error("Le compte {$buyer->email} appartient au personnel : on n’y touche pas.");

            return self::FAILURE;
        }

        $password = Str::password(20);

        $buyer->password = Hash::make($password);
        $buyer->save();

        $this->newLine();
        $this->components->twoColumnDetail('Compte', (string) $buyer->email);
        $this->components->twoColumnDetail('Mot de passe', $password);
        $this->newLine();
        $this->line('  <fg=gray>Le décor n’a pas bougé : ni projet, ni commande, ni invitation.</>');
        $this->newLine();

        return self::SUCCESS;
    }

    /**
     * Étape 1 : le décor, et l'invitation.
     */
    private function create(): int
    {
        $phone = Phone::e164((string) ($this->option('telephone') ?: self::PHONE));

        if ($phone === null || preg_match('/^\+[1-9]\d{7,14}$/', $phone) !== 1) {
            $this->components->error('Le numéro doit être au format international, indicatif compris : +33612345678.');

            return self::FAILURE;
        }

        // Deux valeurs et non l'énumération entière : `both` enverrait deux
        // fois, et `phone_operator` désigne un humain qui rappelle — ni l'un
        // ni l'autre n'est un chemin d'envoi qu'on éprouve ici.
        $channel = match ((string) $this->option('canal')) {
            'sms' => Channel::Sms,
            'email' => Channel::Email,
            default => null,
        };

        if ($channel === null) {
            $this->components->error('Le canal est « sms » ou « email » : c’est un chemin d’envoi qu’on vient éprouver, pas les deux à la fois.');

            return self::FAILURE;
        }

        $mailbox = (string) ($this->option('email') ?: config('brand.support_email'));

        if (filter_var($mailbox, FILTER_VALIDATE_EMAIL) === false) {
            $this->components->error('Il faut une boîte qui existe vraiment : --email=prenom@domaine.fr.');

            return self::FAILURE;
        }

        $proches = max(0, min(count(self::PROCHES), (int) $this->option('proches')));
        $account = self::alias($mailbox, self::BUYER_ALIAS);
        $existing = User::query()->where('email', $account)->first();

        if ($existing instanceof User && $existing->isStaff()) {
            // Le décor ne s'installe pas sur un compte du personnel : il lui
            // changerait son mot de passe, et rangerait un projet d'essai
            // sous le compte qui répond au support.
            $this->components->error("Le compte {$account} appartient au personnel. Le décor ne s’installe pas dessus : donne une autre boîte avec --email.");

            return self::FAILURE;
        }

        if (! $this->announce($phone, $account, $channel, $proches)) {
            // Dit, et non passé sous silence : sans terminal — un script, un
            // `docker compose exec` sans tty — la confirmation retombe sur son
            // défaut, et un `prod:demo` sans `--force` ne ferait rien du tout.
            $this->components->warn('Rien n’a été fait. `--force` pour ne pas demander.');

            return self::SUCCESS;
        }

        $this->eraseScenery();

        [$buyer] = $this->buyer($account, $existing);

        /*
         * Ce que le webhook Stripe encaisse, on l'encaisse aussi — traces
         * comprises.
         *
         * `FulfillOrder` lève sur ce qui manque en base : un texte de
         * consentement absent, une contrainte restée en arrière de son
         * énumération. La trace Symfony qui en sortait — « In
         * MissingConsentText.php line 19 » — est juste et inutilisable sur un
         * serveur : elle ne dit ni ce qui est cassé pour un client, ni quoi
         * taper. Le message brut est gardé, parce que c'est lui qui nomme la
         * valeur en cause, et la commande y ajoute la sortie (T-222).
         */
        try {
            $order = $this->purchase($buyer, $phone, $channel, $mailbox);
        } catch (Throwable $exception) {
            $this->newLine();
            $this->components->error('La commande n’a pas abouti : '.$exception->getMessage());
            $this->line('  <fg=gray>Le compte ci-dessus existe ; son mot de passe vient d’être montré.</>');
            $this->line('  <fg=gray>Rien d’autre n’a été créé : l’exécution est transactionnelle.</>');
            $this->line('  <fg=magenta>php artisan prod:check --rapide</> <fg=gray>nomme ce qui manque, et la commande à taper.</>');
            $this->newLine();

            return self::FAILURE;
        }

        if (! $order instanceof Order) {
            $this->components->error('La commande n’a pas abouti : voir `checkout.*` dans les journaux.');

            return self::FAILURE;
        }

        $project = $order->project;

        if (! $project instanceof Project) {
            $this->components->error('La commande est là mais son projet manque : voir `checkout.fulfilment_orphan`.');

            return self::FAILURE;
        }

        /*
         * Un proche qui ne part pas n'emporte pas le reste : le projet, la
         * commande et l'invitation du narrateur existent, et c'est le
         * parcours qu'on vient éprouver. Taire le récapitulatif pour un lien
         * d'écoute manquant cacherait l'identifiant du projet.
         */
        try {
            $this->invite($project, $buyer, $mailbox, $proches);
        } catch (Throwable $exception) {
            $proches = 0;
            $this->components->warn('Les proches n’ont pas été invités : '.$exception->getMessage());
        }

        return $this->recap($order, $project, $channel, $proches);
    }

    /**
     * Ce qui va se passer, avant que ça se passe.
     *
     * Un SMS ne se décommande pas, et le décor écrit à de vraies boîtes : ce
     * qui se rattrape est ce qu'on a lu. La même raison que pour `prod:sms`.
     */
    private function announce(string $phone, string $account, Channel $channel, int $proches): bool
    {
        $settings = app(PilotSettings::class);
        $precedents = self::scenery()->count();

        $this->newLine();
        $this->line('  <fg=yellow;options=bold>Décor de production</>');
        $this->newLine();

        $this->components->twoColumnDetail('Narrateur', (string) $this->option('prenom').' <fg=gray>'.$phone.'</>');
        $this->components->twoColumnDetail('Invitation par', $channel === Channel::Sms ? 'SMS — un vrai, facturé' : 'courriel');
        $this->components->twoColumnDetail('Compte acheteur', $account);
        $this->components->twoColumnDetail('Proches invités', (string) $proches.' <fg=gray>(courriels réels)</>');
        $this->components->twoColumnDetail('Commande', number_format($settings->pilot_price_cents / 100, 2, ',', ' ').' € <fg=gray>— rien n’est encaissé, rien à rembourser</>');

        if ($precedents > 0) {
            // Dit avant, jamais après : l'opt-in est définitif, donc un tunnel
            // se rejoue sur un projet neuf et le précédent doit partir — mais
            // un enregistrement en cours d'épreuve partirait avec lui.
            $this->components->twoColumnDetail(
                '<fg=red>Décor précédent</>',
                $precedents.' projet(s) <fg=gray>effacés d’abord — voix comprises</>',
            );
        }

        $this->newLine();

        $this->line('  <fg=gray>Le projet n’appartient à aucune cohorte : il sort des lectures par</>');
        $this->line('  <fg=gray>cohorte. La lecture globale du tableau de bord le compte, et l’entonnoir</>');
        $this->line('  <fg=gray>PostHog reçoit un `purchase_completed` de plus — `--purge` puis</>');
        $this->line('  <fg=magenta>metrics:compute --date=…</> <fg=gray>remet les jours touchés.</>');
        $this->newLine();

        if ($channel === Channel::Sms && (string) config('services.sms.provider') !== 'twilio') {
            $this->line('  <fg=red>Le fournisseur SMS n’est pas Twilio : l’invitation ne partira pas.</>');
            $this->line('  <fg=gray>`prod:check` le dit en une ligne, ou --canal=email pour contourner.</>');
            $this->newLine();
        }

        if ($this->option('force') === true) {
            return true;
        }

        return $this->confirm('On envoie ?', false);
    }

    /**
     * Le compte acheteur, et son mot de passe s'il en faut un nouveau.
     *
     * On ne réécrit pas un mot de passe déjà connu : le décor se rejoue
     * souvent, et changer l'accès à chaque tour ferait chercher dans
     * l'historique du terminal. `prod:demo --motdepasse` le réémet quand il
     * est perdu, sans toucher au décor.
     *
     * **Les identifiants s'impriment ici**, et non dans le récapitulatif de
     * fin. La première fois que la commande a échoué en production — sur un
     * texte de consentement manquant — le compte venait d'être créé, son mot
     * de passe tiré, et l'exception a emporté la seule occasion de le lire :
     * un compte inaccessible, et rien pour le dire. Ce qui est tiré une fois
     * s'affiche avant tout ce qui peut lever.
     *
     * @return array{User, string|null}
     */
    private function buyer(string $account, ?User $existing): array
    {
        $password = $existing instanceof User ? null : Str::password(20);

        $buyer = $existing ?? new User;
        $buyer->name = 'Démonstration';
        $buyer->email = $account;

        if ($password !== null) {
            $buyer->password = Hash::make($password);
        }

        $buyer->save();

        // Vérifié d'office : l'espace de l'Initiateur·rice est derrière
        // `verified`, et aller chercher un lien de confirmation pour un décor
        // qu'on vient de fabriquer n'éprouve rien.
        if ($buyer->email_verified_at === null) {
            $buyer->markEmailAsVerified();
        }

        $this->newLine();
        $this->components->twoColumnDetail('Compte', $account);
        $this->components->twoColumnDetail(
            'Mot de passe',
            $password ?? '<fg=gray>inchangé — `prod:demo --motdepasse` pour en réémettre un</>',
        );

        return [$buyer, $password];
    }

    /**
     * L'achat, par le chemin du webhook.
     *
     * Le brouillon porte les six étapes du tunnel comme si quelqu'un venait de
     * les remplir : c'est lui que `FulfillOrder` lit, et le remplir à moitié
     * fabriquerait un projet que le produit ne sait pas afficher.
     *
     * `gift_send_at` est **aujourd'hui, à cette minute** : le cadeau part tout
     * de suite. Le défaut du tunnel est demain, et attendre une nuit pour
     * vérifier qu'un SMS arrive n'a pas de sens.
     */
    private function purchase(User $buyer, string $phone, Channel $channel, string $mailbox): ?Order
    {
        $settings = app(PilotSettings::class);

        $draft = new CheckoutDraft([
            'step' => SaveCheckoutStep::LAST_STEP,
            'payload' => [
                'for' => 'relative',
                'narrator_first_name' => (string) $this->option('prenom'),
                'relationship' => 'Mère',
                // Les deux coordonnées, un seul canal : si le SMS ne part
                // pas, basculer `preferred_channel` suffit à reprendre le
                // parcours sans refabriquer le décor.
                'narrator_email' => self::alias($mailbox, 'narratrice'),
                'narrator_phone' => $phone,
                'preferred_channel' => $channel->value,
                'address_form' => AddressForm::Tu->value,
                'narrator_tech_comfort' => TechComfort::Sometimes->value,
                'gift_send_at' => now()->toDateString(),
                'gift_send_time' => now()->format('H:i'),
                'gift_message' => self::GIFT_MESSAGE,
                'extra_copies' => 0,
                'ebook' => false,
                'phone_option' => false,
                'accepts_terms' => true,
                // Le service commence tout de suite : sans ce consentement,
                // `service_started_at` reste nul et le décor n'éprouve pas ce
                // que vit un acheteur pressé.
                'early_service_start' => true,
                'marketing_email' => false,
            ],
            'expires_at' => now()->addDays(CheckoutDraft::LIFETIME_DAYS),
        ]);

        $draft->user()->associate($buyer);
        $draft->save();

        $order = app(FulfillOrder::class)->handle([
            'id' => self::SESSION_PREFIX.Str::uuid()->toString(),
            // Nul, et pas inventé : un identifiant de paiement qui n'existe
            // pas chez Stripe ferait échouer le remboursement sur un « No
            // such payment_intent » que rien n'annonce (T-171, demo:paiement).
            'payment_intent' => null,
            'amount_subtotal' => $settings->pilot_price_cents,
            'amount_total' => $settings->pilot_price_cents,
            'metadata' => [
                'draft_id' => $draft->getKey(),
                'user_id' => (string) $buyer->getKey(),
            ],
        ]);

        if ($order instanceof Order && $order->project instanceof Project) {
            // Hors cohorte : voir l'en-tête. `saveQuietly` parce qu'on corrige
            // un rangement, on ne rejoue pas le cycle de vie du projet.
            $order->project->forceFill(['cohort_id' => null])->saveQuietly();
        }

        return $order;
    }

    /**
     * Les proches, par le vrai chemin : un jeton d'écoute chacun, et le
     * courriel qui va avec.
     *
     * Des alias de la même boîte, jamais des adresses inventées : le maillon
     * H2 ne se vérifie qu'en ouvrant les liens, et un lien envoyé à
     * `camille@example.test` n'arrive nulle part.
     */
    private function invite(Project $project, User $buyer, string $mailbox, int $proches): void
    {
        $invite = app(InviteFamilyMember::class);

        for ($index = 0; $index < $proches; $index++) {
            [$name, $relationship] = self::PROCHES[$index];

            $invite->handle($project, $buyer, [
                'display_name' => $name,
                'relationship' => $relationship,
                'email' => self::alias($mailbox, mb_strtolower($name)),
            ]);
        }
    }

    /**
     * La feuille de route, et rien de secret.
     *
     * Aucun lien à jeton n'est imprimé, et c'est volontaire : le jeton en clair
     * n'existe qu'entre son émission et son envoi, et le canal est justement ce
     * qu'on vient éprouver. Un lien recopié ici prouverait que la base sait
     * fabriquer une URL, pas qu'un téléphone reçoit un SMS.
     */
    private function recap(Order $order, Project $project, Channel $channel, int $proches): int
    {
        $this->newLine();
        $this->components->twoColumnDetail('<fg=green>Décor prêt</>', '');
        $this->components->twoColumnDetail('Projet', $project->getKey());
        $this->components->twoColumnDetail('Commande', $order->getKey().' <fg=gray>'.$order->status->value.'</>');
        $this->newLine();

        $this->step(sprintf(
            'L’invitation part maintenant, par %s, depuis la file `notifications`. Si Horizon dort, elle attend.',
            $channel === Channel::Sms ? 'SMS' : 'courriel',
        ));

        $this->step('Ouvrir l’annonce, accepter : cinq cases distinctes, le canal, le jour et le créneau. Rien n’y propose d’enregistrer avant.');

        $this->step('Puis, sans attendre le lendemain que l’acceptation vient de poser :');
        $this->line('       <fg=magenta>php artisan prod:demo --question</>');

        $this->step('Enregistrer, réécouter, envoyer, décider du partage. Rien ne dit « enregistrée » avant que le stockage l’ait confirmé.');

        if ($proches > 0) {
            $this->step(sprintf(
                '%d lien(s) d’écoute sont partis sur des alias de la même boîte : le maillon famille se vérifie en les ouvrant.',
                $proches,
            ));
        }

        $this->step('À la fin, le décor s’efface — les voix quittent R2, pas seulement les lignes :');
        $this->line('       <fg=magenta>php artisan prod:demo --purge</>');
        $this->newLine();

        return self::SUCCESS;
    }

    /**
     * Étape 2 : la première question, maintenant.
     *
     * On avance `next_prompt_at` du seul projet du décor, et c'est tout : le
     * reste est le travail de `prompts:dispatch-due`, celle qui tourne toutes
     * les cinq minutes en production. Envelopper la commande réelle
     * reviendrait à éprouver l'enveloppe (conventions §6).
     *
     * Rejouable : tant que l'histoire proposée n'est pas enregistrée, on ne
     * pose pas la suivante.
     */
    private function firstQuestion(): int
    {
        $project = self::scenery()->latest('created_at')->first();

        if (! $project instanceof Project) {
            $this->components->error('Aucun décor en cours. Commence par : php artisan prod:demo');

            return self::FAILURE;
        }

        if ($project->refused_at !== null) {
            $this->components->error('Cette annonce a été refusée, et un refus se respecte. Un décor neuf : php artisan prod:demo');

            return self::FAILURE;
        }

        if ($project->accepted_at === null) {
            // Rien ne part avant l'acceptation : c'est l'invariant du bloc 10,
            // et la commande le dit plutôt que de le contourner.
            $this->components->error('L’annonce n’a pas encore été acceptée : aucune question ne peut partir.');
            $this->line('  <fg=gray>Ouvre le lien reçu et accepte — un jeton en clair ne se relit pas.</>');

            return self::FAILURE;
        }

        if ($project->status !== ProjectStatus::Active) {
            $this->components->error("Le projet est « {$project->status->value} » : `prompts:dispatch-due` ne prend que les projets actifs.");

            return self::FAILURE;
        }

        if (! Question::query()->exists()) {
            // Le corpus n'est pas semé : la question ne partirait pas, et le
            // planificateur enverrait un « corpus épuisé » à l'acheteuse.
            $this->components->error('Le corpus de questions est vide en base : `php artisan db:seed --class=QuestionSeeder` d’abord.');

            return self::FAILURE;
        }

        $pending = self::pendingStory($project);

        if (! $pending instanceof Story) {
            $project->next_prompt_at = now()->subMinute();
            $project->save();

            $this->call('prompts:dispatch-due');

            $pending = self::pendingStory($project->refresh());
        }

        if (! $pending instanceof Story) {
            $this->components->error('Aucune question n’est partie : corpus épuisé, projet en pause, ou hors fenêtre de collecte. Voir `prompt.*`.');

            return self::FAILURE;
        }

        $this->newLine();
        $this->components->twoColumnDetail('Question', '<fg=gray>« '.(string) $pending->questionText().' »</>');
        $this->components->twoColumnDetail('Histoire', $pending->getKey());
        $this->newLine();
        $this->line('  <fg=gray>Le lien est parti sur le canal du narrateur. S’il n’arrive pas,</>');
        $this->line('  <fg=gray>l’espace de l’Initiateur·rice sait le réémettre : /espace.</>');
        $this->newLine();

        return self::SUCCESS;
    }

    /**
     * Étape 3 : l'effacement.
     *
     * Par `EraseProject`, le chemin RGPD, et non par un `delete` : lui seul
     * retire les objets de R2 — les segments, les dérivés, la réplique, les
     * photos — et révoque les jetons. Un décor supprimé en base laisserait
     * les voix sur le stockage, facturées et lisibles par une URL présignée
     * encore valide.
     *
     * Les commandes du décor, elles, partent pour de bon : `EraseProject` les
     * garde pour la comptabilité, et une commande jamais payée n'a rien à y
     * faire — elle ne ferait qu'un « payé » de trop dans le back-office.
     */
    private function purge(): int
    {
        $projects = self::scenery()->get();
        $orders = Order::query()->where('stripe_checkout_session_id', 'like', self::SESSION_PREFIX.'%')->count();

        if ($projects->isEmpty() && $orders === 0) {
            $this->components->info('Aucun décor à effacer.');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->components->twoColumnDetail('Projets à effacer', (string) $projects->count().' <fg=gray>voix, photos, transcriptions, jetons</>');
        $this->components->twoColumnDetail('Commandes à supprimer', (string) $orders.' <fg=gray>jamais payées</>');
        $this->newLine();

        if ($this->option('force') !== true && ! $this->confirm('On efface ?', false)) {
            $this->components->warn('Rien n’a été fait. `--force` pour ne pas demander.');

            return self::SUCCESS;
        }

        $erased = $this->eraseScenery();

        $this->components->info("{$erased} projet(s) effacé(s), {$orders} commande(s) supprimée(s).");
        $this->line('  <fg=gray>Les jours déjà calculés gardent la trace du décor :</>');
        $this->line('  <fg=magenta>php artisan metrics:compute --date=AAAA-MM-JJ</> <fg=gray>les recalcule.</>');

        return self::SUCCESS;
    }

    /**
     * Efface le décor et rend le nombre de projets partis.
     *
     * Synchrone et non mis en file : une purge dont la suite dépend d'un
     * worker laisserait des objets en place sans que rien ne le dise, et
     * l'appelant enchaîne aussitôt sur un décor neuf.
     */
    private function eraseScenery(): int
    {
        $projects = self::scenery()->get();

        foreach ($projects as $project) {
            EraseProject::dispatchSync($project->getKey());
        }

        // Après l'effacement : la ligne de commande porte le marqueur qui
        // retrouve les projets, la supprimer avant les rendrait introuvables.
        Order::query()
            ->where('stripe_checkout_session_id', 'like', self::SESSION_PREFIX.'%')
            ->each(function (Order $order): void {
                $order->delete();
            });

        return $projects->count();
    }

    /**
     * Les projets du décor : ceux dont la commande porte le marqueur.
     *
     * L'appartenance se lit sur la commande et non sur le compte acheteur : un
     * `--email` différent d'un tour à l'autre ne doit pas laisser un décor
     * orphelin derrière lui.
     *
     * @return Builder<Project>
     */
    private static function scenery(): Builder
    {
        return Project::query()
            ->whereNull('erased_at')
            ->whereIn('id', Order::query()
                ->whereNotNull('project_id')
                ->where('stripe_checkout_session_id', 'like', self::SESSION_PREFIX.'%')
                ->select('project_id'));
    }

    /** L'histoire déjà proposée et pas encore enregistrée, s'il y en a une. */
    private static function pendingStory(Project $project): ?Story
    {
        $story = $project->stories()
            ->where('state', Proposed::$name)
            ->orderByDesc('sequence')
            ->first();

        return $story instanceof Story ? $story : null;
    }

    /**
     * `toi@domaine.fr` + `demo` → `toi+demo@domaine.fr`.
     *
     * Une seule boîte suffit donc à tout le décor : l'acheteuse, la narratrice
     * et les proches y arrivent, chacun sur une adresse distincte — et donc un
     * compte, un narrateur et des fiches d'écoute distincts.
     */
    private static function alias(string $mailbox, string $tag): string
    {
        [$local, $domain] = explode('@', $mailbox, 2);

        // Un alias sur un alias empilerait les `+` : on repart du local.
        $local = Str::before($local, '+');

        return mb_strtolower("{$local}+{$tag}@{$domain}");
    }

    /**
     * Une étape de la feuille de route, numérotée d'elle-même.
     *
     * Le compteur vit ici parce que les étapes ne sont pas toutes imprimées :
     * sans proche, l'écoute famille disparaît, et une liste qui saute du 4 au
     * 6 fait chercher l'étape manquante.
     */
    private function step(string $quoi): void
    {
        $this->etape++;

        $this->newLine();
        $this->line("  <fg=green>•</> {$this->etape}. {$quoi}");
    }
}
