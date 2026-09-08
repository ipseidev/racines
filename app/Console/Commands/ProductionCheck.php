<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\ConsentKind;
use App\Models\ConsentText;
use App\Services\Storage\MediaStorage;
use App\Support\Database\EnumCheck;
use BackedEnum;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Laravel\Horizon\Contracts\MasterSupervisorRepository;
use Throwable;

/**
 * « Si quelqu'un achète maintenant, est-ce que ça marche ? »
 *
 * Une seule question, et c'est la seule que cette commande pose. Elle ne
 * vérifie **pas** la conformité, ni la mesure d'audience, ni le rendu du
 * livre : ce sont des choses importantes qui ne cassent pas la chaîne entre
 * un paiement et une famille qui écoute une voix.
 *
 * Chaque ligne dit **ce que le client perd** quand elle est rouge, pas ce qui
 * est techniquement absent. À trois heures du matin, « TWILIO_FROM est vide »
 * ne se lit pas ; « aucun SMS ne part, les parents ne reçoivent pas leur
 * invitation » se lit.
 *
 * Trois niveaux :
 *  - **rouge** : la chaîne est coupée, quelqu'un paie et ne reçoit rien ;
 *  - **orange** : une fonction est dégradée, la chaîne tient ;
 *  - **vert** : rien à faire.
 *
 * Les appels aux prestataires sont **réels**. C'est le point : une clé
 * présente dans l'environnement ne prouve pas qu'elle est valide, et c'est
 * exactement le genre de chose qu'on découvre avec le premier client.
 */
final class ProductionCheck extends Command
{
    protected $signature = 'prod:check {--rapide : sans appeler les prestataires}';

    protected $description = 'Vérifie que la chaîne « payer → raconter → écouter » fonctionne';

    /** @var list<string> Les files déclarées dans config/horizon.php. */
    private const FILES = ['default', 'notifications', 'engine', 'media', 'transcription', 'llm', 'exports'];

    private int $rouges = 0;

    private int $oranges = 0;

    public function handle(): int
    {
        $this->newLine();
        $this->line('  <options=bold>Encaisser</>');
        $this->stripe();
        $this->consentements();

        $this->newLine();
        $this->line('  <options=bold>Livrer</>');
        $this->base();
        $this->contraintes();
        $this->file();
        $this->stockage();

        $this->newLine();
        $this->line('  <options=bold>Joindre les gens</>');
        $this->sms();
        $this->courriel();

        $this->newLine();
        $this->line('  <options=bold>Transformer la voix en texte</>');
        $this->transcription();
        $this->rendu();

        $this->newLine();
        $this->line('  <options=bold>Ne pas se trahir</>');
        $this->debug();

        $this->newLine();

        if ($this->rouges > 0) {
            $this->components->error(sprintf(
                '%d rupture(s) dans la chaîne : quelqu’un peut payer sans rien recevoir.',
                $this->rouges,
            ));

            return self::FAILURE;
        }

        if ($this->oranges > 0) {
            $this->components->warn(sprintf('%d fonction(s) dégradée(s), la chaîne tient.', $this->oranges));

            return self::SUCCESS;
        }

        $this->components->info('La chaîne est entière.');

        return self::SUCCESS;
    }

    private function stripe(): void
    {
        $secret = (string) config('cashier.secret');
        $webhook = (string) config('cashier.webhook.secret');
        $live = str_starts_with($secret, 'sk_live_') || str_starts_with($secret, 'rk_live_');

        if ($secret === '') {
            $this->rouge('Paiement', 'aucune clé Stripe : le bouton « Payer » ne mène nulle part.');
        } elseif (! $live) {
            $this->selonEnv('Paiement', 'clé Stripe de test : les paiements ne sont pas encaissés.');
        } else {
            $this->vert('Paiement', 'clé live');
        }

        if ($webhook === '') {
            // Sans lui, Cashier n'installe pas la vérification de signature :
            // le webhook devient forgeable, et une commande peut naître sans
            // paiement.
            $this->rouge('Webhook Stripe', 'aucun secret : la commande ne se crée pas après un paiement.');
        } else {
            $this->vert('Webhook Stripe', 'signature vérifiée');
        }
    }

    /**
     * Les textes de consentement : sans eux, **le paiement ne devient pas
     * une commande**.
     *
     * `RecordConsent` refuse d'enregistrer un accord dont il ne pourrait pas
     * dire, plus tard, ce qui avait été lu (doc 04 §2) — le bon choix. Mais
     * `FulfillOrder` recueille deux accords de l'acheteur, « démarrer tout de
     * suite » et « recevoir des nouvelles », et le tunnel affiche les deux
     * cases sans condition. Un texte manquant fait donc **lever** l'exécution
     * de la commande, à l'intérieur de sa transaction : la commande et le
     * projet sont annulés, le webhook répond 500, et Stripe le réessaie avant
     * de désactiver l'endpoint — soit la punition exacte de T-169, pour un
     * acheteur qui a simplement coché une case.
     *
     * Les cinq accords du narrateur comptent autant : sans eux, la page
     * d'opt-in ne peut pas s'afficher, et le cadeau n'a nulle part où aller.
     *
     * Cette ligne existe parce que `prod:demo` a trouvé le trou que
     * `prod:check` ne voyait pas : la question de cette commande est « si
     * quelqu'un achète maintenant, est-ce que ça marche ? », et elle répondait
     * oui (T-211). Un semis oublié n'est pas une faute de code, et c'est bien
     * pour cela qu'aucun test ne l'attrape.
     */
    private function consentements(): void
    {
        $locale = (string) config('app.locale');

        try {
            $manquants = array_values(array_filter(
                ConsentKind::cases(),
                static fn (ConsentKind $kind): bool => ConsentText::current($kind, $locale) === null,
            ));
        } catch (Throwable $e) {
            $this->rouge('Textes de consentement', 'illisibles : '.$e->getMessage());

            return;
        }

        if ($manquants === []) {
            $this->vert('Textes de consentement', sprintf('%d en vigueur en « %s »', count(ConsentKind::cases()), $locale));

            return;
        }

        /*
         * Le verdict court, la conséquence et le remède sur leurs propres
         * lignes : `twoColumnDetail` remplit la largeur de points, et une
         * phrase de trois lignes y perd sa fin — or ce qu'on vient chercher à
         * trois heures du matin est justement la commande à taper.
         */
        $this->rouge('Textes de consentement', sprintf(
            '%d manquant(s) en « %s » : un achat peut ne jamais devenir une commande.',
            count($manquants),
            $locale,
        ));

        // Les noms bruts, et non les libellés traduits : c'est la valeur que
        // porte le message d'erreur, et celle que le semis attend.
        $this->line('      <fg=gray>manquants : </><fg=red>'.implode(', ', array_map(
            static fn (ConsentKind $kind): string => $kind->value,
            $manquants,
        )).'</>');

        $this->line('      <fg=gray>une case « démarrer tout de suite » cochée fait lever l’exécution de</>');
        $this->line('      <fg=gray>la commande : elle est annulée, et Stripe désactive le webhook.</>');
        $this->line('      <fg=magenta>php artisan db:seed --class=ConsentTextSeeder --force</>');
    }

    private function base(): void
    {
        try {
            DB::connection()->getPdo();
            $this->vert('Base de données', 'joignable');
        } catch (Throwable $e) {
            $this->rouge('Base de données', 'injoignable : plus rien ne fonctionne. '.$e->getMessage());
        }
    }

    /**
     * La base accepte-t-elle tout ce que le code peut y écrire ?
     *
     * `EnumCheck::of($enum)` est évalué **au moment où la migration tourne**,
     * contre le code du jour. Une base créée par `migrate:fresh` obtient donc
     * l'énumération complète et paraît saine ; une base migrée pas à pas garde
     * la liste qu'avait l'énumération ce jour-là. Les deux divergent en
     * silence, la suite de tests tourne toujours sur la première, et c'est la
     * seconde qui est en production — **aucun test ne peut voir cet écart**.
     *
     * Il a coûté le tunnel d'achat : quatre motifs de consentement ajoutés
     * après le 2 septembre n'avaient élargi que `consents.kind`, pas
     * `consent_texts.kind`, et un client cochant « démarrer tout de suite »
     * voyait sa commande annulée et Stripe désactiver le webhook (T-211).
     *
     * La correspondance colonne → énumération ne se recopie pas ici : elle est
     * déjà dans les `casts()` des modèles, qui sont maintenus parce que le
     * produit s'en sert. Une seconde liste aurait divergé, ce qui est
     * précisément le défaut qu'on vient de corriger.
     */
    private function contraintes(): void
    {
        /*
         * Les colonnes **volontairement plus étroites** que leur énumération,
         * avec la raison. Sans elles, la ligne crierait au loup sur trois
         * contraintes justes, et on apprendrait à l'ignorer.
         */
        $etroites = [
            // Un narrateur choisit l'un, l'autre ou les deux ; « opérateur
            // téléphone » n'est pas une préférence, c'est un humain qui rappelle.
            'narrators.preferred_channel',
            // Un code à usage unique et un message sortant partent par un seul
            // canal : `both` n'a pas de sens à l'envoi.
            'otp_challenges.channel',
            'outbound_messages.channel',
        ];

        try {
            $ecarts = [];
            $sansGarde = [];

            foreach (self::colonnesEnumerees() as $colonne => $enum) {
                if (in_array($colonne, $etroites, true)) {
                    continue;
                }

                $autorisees = self::valeursAutorisees($colonne);

                if ($autorisees === null) {
                    $sansGarde[] = $colonne;

                    continue;
                }

                $manquantes = array_diff(EnumCheck::of($enum), $autorisees);

                if ($manquantes !== []) {
                    $ecarts[$colonne] = array_values($manquantes);
                }
            }
        } catch (Throwable $e) {
            $this->rouge('Contraintes de la base', 'illisibles : '.$e->getMessage());

            return;
        }

        if ($ecarts === []) {
            $this->vert('Contraintes de la base', 'chaque colonne accepte toute son énumération');
        }

        foreach ($ecarts as $colonne => $manquantes) {
            /*
             * Une ligne par colonne — c'est elle qu'on va réparer — et le
             * verdict court : `twoColumnDetail` remplit la largeur de points,
             * et une phrase de trois lignes y perd sa fin. Les valeurs
             * refusées vont donc sur leur propre ligne, où elles survivent à
             * un terminal étroit.
             */
            $this->rouge('Contrainte '.$colonne, sprintf(
                '%d valeur(s) refusées, que le code écrit pourtant : erreur 500.',
                count($manquantes),
            ));

            $this->line('      <fg=gray>refusées : </><fg=red>'.implode(', ', $manquantes).'</>');
        }

        if ($ecarts !== []) {
            $this->line('      <fg=gray>une énumération élargie oblige à réémettre la contrainte de chaque</>');
            $this->line('      <fg=gray>table qui la stocke : une migration `EnumCheck::drop` puis `add`.</>');
        }

        if ($sansGarde !== []) {
            // Orange : le produit fonctionne, c'est la garde qui manque.
            $this->orange('Colonnes sans contrainte', implode(', ', $sansGarde).' — convention §13 : la garde vit en base, pas seulement dans le code.');
        }
    }

    /**
     * Les colonnes qui stockent une énumération, d'après les `casts()`.
     *
     * @return array<string, class-string<BackedEnum>>
     */
    private static function colonnesEnumerees(): array
    {
        $colonnes = [];

        foreach (glob(app_path('Models/*.php')) ?: [] as $fichier) {
            $classe = 'App\\Models\\'.basename($fichier, '.php');

            if (! class_exists($classe) || ! is_subclass_of($classe, Model::class)) {
                continue;
            }

            $modele = new $classe;

            foreach ($modele->getCasts() as $colonne => $cast) {
                // `getCasts()` rend aussi « immutable_datetime » ou
                // « decimal:2 » : `enum_exists` les écarte sans bruit.
                if (enum_exists($cast) && is_subclass_of($cast, BackedEnum::class)) {
                    $colonnes[$modele->getTable().'.'.$colonne] = $cast;
                }
            }
        }

        ksort($colonnes);

        return $colonnes;
    }

    /**
     * Les valeurs qu'une contrainte `check` laisse passer, ou `null` s'il n'y
     * en a aucune.
     *
     * @return list<string>|null
     */
    private static function valeursAutorisees(string $colonne): ?array
    {
        [$table, $champ] = explode('.', $colonne, 2);

        $definition = DB::selectOne(
            'select pg_get_constraintdef(oid) as definition from pg_constraint where contype = ? and conname = ?',
            ['c', "{$table}_{$champ}_check"],
        );

        if ($definition === null) {
            return null;
        }

        preg_match_all("/'([^']*)'/", (string) $definition->definition, $trouvees);

        return $trouvees[1];
    }

    /**
     * La file : sans elle, **rien de différé n'arrive**.
     *
     * C'est la panne la plus sournoise du produit : le site répond, on peut
     * acheter, et l'invitation ne part jamais. Personne ne s'en aperçoit
     * avant qu'un parent appelle pour dire qu'il n'a rien reçu.
     */
    private function file(): void
    {
        try {
            if (app(MasterSupervisorRepository::class)->all() === []) {
                $this->rouge('File de travaux', 'Horizon ne tourne pas : ni invitation, ni transcription, ni relance.');

                return;
            }
        } catch (Throwable $e) {
            $this->rouge('File de travaux', 'Redis injoignable : rien de différé ne part. '.$e->getMessage());

            return;
        }

        $attente = array_sum(array_map(
            static fn (string $file): int => Queue::size($file),
            self::FILES,
        ));

        $this->vert('File de travaux', sprintf(
            'Horizon tourne, %d travail%s en attente',
            $attente,
            $attente > 1 ? 'x' : '',
        ));

        /*
         * Les travaux **échoués** sont le seul endroit où une invitation
         * disparaît sans bruit. Le site répond, la commande est payée, et
         * l'envoi est mort dans un worker il y a six heures : rien dans
         * l'interface ne le montre.
         */
        try {
            $echecs = (int) DB::table('failed_jobs')
                ->where('failed_at', '>=', now()->subDay())
                ->count();

            $echecs === 0
                ? $this->vert('Travaux échoués', 'aucun depuis 24 h')
                : $this->orange('Travaux échoués', sprintf(
                    '%d depuis 24 h : autant d’envois ou de transcriptions perdus (« sail artisan queue:failed »).',
                    $echecs,
                ));
        } catch (Throwable) {
            // Pas de table : rien à dire, et surtout pas une fausse alerte.
        }
    }

    /**
     * Le stockage : écriture **et** relecture.
     *
     * Écrire sans relire ne prouve rien — un bucket dont les droits de
     * lecture manquent accepte les envois et rend des enregistrements
     * inaudibles. C'est la panne qui coûte le plus cher : on ne redemande pas
     * à quelqu'un de quatre-vingts ans de tout raconter une seconde fois.
     */
    private function stockage(): void
    {
        $cle = 'health/prod-check.txt';

        try {
            $storage = app(MediaStorage::class);
            $storage->put($cle, 'ok', 'text/plain');
            $info = $storage->head($cle);
            $storage->delete($cle);

            $this->vert('Stockage des voix', sprintf('écriture, relecture et effacement (%d o)', $info->bytes));
        } catch (Throwable $e) {
            $this->rouge('Stockage des voix', 'inaccessible : un narrateur peut parler, rien ne sera conservé. '.$e->getMessage());
        }
    }

    private function sms(): void
    {
        $provider = (string) config('services.sms.provider');

        if ($provider !== 'twilio') {
            $this->selonEnv('SMS', sprintf(
                'fournisseur « %s » : aucun SMS réel ne part, les parents ne reçoivent pas leur invitation.',
                $provider,
            ));

            return;
        }

        $from = (string) config('services.twilio.from');

        if ($from === '' || ! str_starts_with($from, '+')) {
            // L'expéditeur alphanumérique est refusé par certains opérateurs :
            // sans numéro de repli, ces SMS-là n'arrivent jamais.
            $this->orange('SMS', 'aucun numéro de repli : les opérateurs qui refusent un expéditeur alphanumérique ne délivreront rien.');
        }

        if ($this->option('rapide')) {
            $this->vert('SMS', 'Twilio configuré (appel non vérifié)');

            return;
        }

        $sid = (string) config('services.twilio.sid');
        $token = (string) config('services.twilio.token');

        try {
            $reponse = Http::withBasicAuth($sid, $token)
                ->timeout(8)
                ->get("https://api.twilio.com/2010-04-01/Accounts/{$sid}.json");

            $reponse->successful()
                ? $this->vert('SMS', 'Twilio authentifié')
                : $this->rouge('SMS', 'Twilio refuse nos identifiants : aucune invitation ne partira.');
        } catch (Throwable $e) {
            $this->rouge('SMS', 'Twilio injoignable : '.$e->getMessage());
        }
    }

    private function courriel(): void
    {
        $mailer = (string) config('mail.default');

        if (in_array($mailer, ['log', 'array'], true)) {
            $this->selonEnv('Courriel', sprintf('transport « %s » : aucun message ne part.', $mailer));

            return;
        }

        $this->vert('Courriel', 'transport « '.$mailer.' »');
    }

    /**
     * La transcription : sans elle, une histoire enregistrée **reste muette**.
     *
     * Elle ne bloque pas l'enregistrement, ce qui la rend traître : la
     * famille voit son récit arriver, puis plus rien — ni texte à relire, ni
     * validation possible, donc ni partage. La chaîne s'arrête au milieu.
     */
    private function transcription(): void
    {
        $provider = (string) config('services.asr.provider');

        if ($provider === 'fake') {
            $this->selonEnv('Transcription', 'fournisseur doublé : le texte serait inventé, pas transcrit. Posez ASR_PROVIDER=gladia.');

            return;
        }

        $cle = (string) config('services.asr.gladia_key');

        if ($cle === '') {
            $this->rouge('Transcription', 'aucune clé : les histoires resteront sans texte, donc sans validation possible.');

            return;
        }

        if ($this->option('rapide')) {
            $this->vert('Transcription', 'clé présente (appel non vérifié)');

            return;
        }

        try {
            $reponse = Http::withHeaders(['x-gladia-key' => $cle])->timeout(8)->get('https://api.gladia.io/v2/pre-recorded');

            // 4xx d'authentification = clé refusée. Un 404 ou un 405 signifie
            // que la clé est passée et que seule la route diffère.
            $reponse->status() === 401
                ? $this->rouge('Transcription', 'Gladia refuse la clé : les histoires resteront muettes.')
                : $this->vert('Transcription', 'Gladia répond');
        } catch (Throwable $e) {
            $this->orange('Transcription', 'Gladia injoignable à l’instant : '.$e->getMessage());
        }
    }

    private function rendu(): void
    {
        $provider = (string) config('services.anthropic.provider');
        $cle = (string) config('services.anthropic.key');

        if ($provider === 'fake') {
            $this->selonEnv('Mise au propre', 'fournisseur doublé : le texte lisible ne sera jamais produit. Posez LLM_PROVIDER=claude.');

            return;
        }

        if ($cle === '') {
            $this->rouge('Mise au propre', 'aucune clé : la famille n’aura que le mot à mot brut.');

            return;
        }

        if ($this->option('rapide')) {
            $this->vert('Mise au propre', 'clé présente (appel non vérifié)');

            return;
        }

        try {
            $reponse = Http::withHeaders([
                'x-api-key' => $cle,
                'anthropic-version' => '2023-06-01',
            ])->timeout(10)->post('https://api.anthropic.com/v1/messages', [
                'model' => (string) config('services.anthropic.model'),
                'max_tokens' => 1,
                'messages' => [['role' => 'user', 'content' => 'ok']],
            ]);

            $reponse->status() === 401
                ? $this->rouge('Mise au propre', 'Anthropic refuse la clé.')
                : $this->vert('Mise au propre', 'Anthropic répond');
        } catch (Throwable $e) {
            $this->orange('Mise au propre', 'Anthropic injoignable à l’instant : '.$e->getMessage());
        }
    }

    /**
     * Le mode de débogage : une trace d'erreur montrée à un client.
     *
     * Ce n'est pas un souci de conformité — c'est une page qui affiche le
     * contenu de l'environnement, clés comprises, à qui déclenche une erreur.
     */
    private function debug(): void
    {
        if (config('app.debug') === true) {
            $this->selonEnv('Mode débogage', 'activé : une erreur affiche vos clés au visiteur.');
        } else {
            $this->vert('Mode débogage', 'désactivé');
        }

        $url = (string) config('app.url');

        str_starts_with($url, 'https://')
            ? $this->vert('Adresse du site', $url)
            : $this->selonEnv('Adresse du site', 'APP_URL n’est pas en https : les liens envoyés partiront en clair.');
    }

    /**
     * Rouge en production, orange ailleurs — et le dit.
     *
     * Un décor local est **censé** avoir un faux transcripteur et une clé
     * Stripe de test : les peindre en rouge apprendrait à ignorer le rouge.
     * Mais les taire rendrait la répétition locale inutile, puisqu'on ne
     * verrait la ligne que le jour où elle casse pour de vrai.
     */
    private function selonEnv(string $quoi, string $detail): void
    {
        app()->isProduction()
            ? $this->rouge($quoi, $detail)
            : $this->orange($quoi, $detail.' <fg=gray>(normal hors production ; rouge en production)</>');
    }

    private function vert(string $quoi, string $detail): void
    {
        $this->components->twoColumnDetail("  <fg=green>●</> {$quoi}", "<fg=gray>{$detail}</>");
    }

    private function orange(string $quoi, string $detail): void
    {
        $this->oranges++;
        $this->components->twoColumnDetail("  <fg=yellow>●</> {$quoi}", "<fg=yellow>{$detail}</>");
    }

    private function rouge(string $quoi, string $detail): void
    {
        $this->rouges++;
        $this->components->twoColumnDetail("  <fg=red>●</> {$quoi}", "<fg=red>{$detail}</>");
    }
}
