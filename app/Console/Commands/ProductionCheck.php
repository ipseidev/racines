<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\ConsentKind;
use App\Models\ConsentText;
use App\Services\Storage\MediaStorage;
use App\Services\Storage\S3MediaStorage;
use App\Support\Brand;
use App\Support\Database\EnumCheck;
use App\Support\Storage\BrowserUploadCors;
use BackedEnum;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
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
        $this->cors();
        $this->presigne();
        $this->compartiments();
        $this->file();
        $this->stockage();

        $this->newLine();
        $this->line('  <options=bold>Joindre les gens</>');
        $this->sms();
        $this->courriel();

        $this->newLine();
        $this->line('  <options=bold>Transformer la voix en texte</>');
        $this->ffmpeg();
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
     * oui (T-222). Un semis oublié n'est pas une faute de code, et c'est bien
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
     * voyait sa commande annulée et Stripe désactiver le webhook (T-222).
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
        /*
         * Le pilote de file, avant Horizon : c'est lui qui décide si « plus
         * tard » veut dire quelque chose. `sync`, `deferred` et `background`
         * exécutent tout de suite ce qu'on leur demande de différer. Un
         * cadeau programmé pour dix heures part alors à la seconde du
         * paiement (T-239), et l'appel sortant vers Meta se retrouve dans la
         * requête du webhook, où un échec devient le 500 que Stripe punit en
         * désactivant l'endpoint (T-169).
         */
        $pilote = (string) config('queue.default');

        if (in_array($pilote, ['sync', 'deferred', 'background'], true)) {
            $this->rouge('File de travaux', sprintf(
                'file « %s » : rien n’est différé, un cadeau programmé part à l’instant du paiement.',
                $pilote,
            ));

            return;
        }

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
    /**
     * Le navigateur peut-il déposer, et lire la réponse ?
     *
     * `stockage()` écrit et relit avec **nos** identifiants, depuis le
     * serveur : ça prouve le compartiment et la clé, et rien du tout de ce
     * qu'un téléphone arrive à faire. Or l'envoi d'un enregistrement se fait
     * en direct du navigateur vers R2, par URL présignée, et il ne dépend
     * d'aucune de ces deux choses.
     *
     * Trois conditions, et il en faut les trois :
     *
     *  - l'**origine** qui sert la page d'enregistrement doit être autorisée,
     *    et en production c'est le domaine court, pas celui de l'application ;
     *  - `PUT` doit être autorisé ;
     *  - `ETag` doit être **exposé**. C'est celle qu'on oublie, et la plus
     *    cruelle : le dépôt réussit, le navigateur cache l'en-tête, et l'envoi
     *    multipart ne peut pas se conclure. `api.ts` lève alors « Le stockage
     *    n'a pas rendu d'ETag », un narrateur voit « L'envoi n'a pas abouti »
     *    après avoir parlé dix minutes, et les journaux du serveur sont vides
     *    parce que rien n'a échoué chez nous (T-224).
     *
     * `05_A_FAIRE_HUMAIN.md` prévenait déjà, ligne 13 : « l'exposition de
     * l'ETag en est la partie qu'on oublie et sans laquelle les envois
     * échouent en silence ». Une prose ne vérifie rien ; cette ligne, si.
     */
    private function cors(): void
    {
        // Le pilote configuré, et non un `instanceof` : c'est ce réglage qui
        // décide du stockage monté, et un double local n'a pas de
        // compartiment — la question n'a alors pas de réponse, ce qui n'est
        // pas la même chose qu'une réponse vide.
        if ((string) config('services.media.driver') !== 's3') {
            $this->selonEnv('CORS du stockage', 'stockage simulé : aucun envoi navigateur n’est éprouvé.');

            return;
        }

        try {
            $regles = (new S3MediaStorage)->corsRules();
        } catch (Throwable $e) {
            $this->orange('CORS du stockage', 'règles illisibles : '.$e->getMessage());

            return;
        }

        // L'origine qui sert la page à jeton, et non `app.url` : en production
        // `Links::routeDomain()` impose le domaine court, et c'est de là que
        // le navigateur émet sa requête.
        $origine = 'https://'.Brand::linksDomain();
        $manques = BrowserUploadCors::missing($regles, $origine);

        if ($manques === []) {
            $this->vert('CORS du stockage', sprintf('%s peut déposer et lire l’ETag', $origine));

            return;
        }

        /*
         * Aucune règle **lisible** n'est rouge en production et orange
         * ailleurs : MinIO ne sert pas ses règles par l'API S3 (T-58), donc en
         * local la réponse est vide alors que la console en porte une. Peindre
         * ça en rouge apprendrait à ignorer le rouge.
         */
        if ($regles === null || $regles === []) {
            $this->selonEnv('CORS du stockage', 'aucune règle lisible : aucun enregistrement ne peut partir d’un navigateur.');
            $this->remede($origine);

            return;
        }

        $this->rouge('CORS du stockage', sprintf(
            'il manque %s : « L’envoi n’a pas abouti » après avoir parlé.',
            implode(', ', $manques),
        ));
        $this->remede($origine);
    }

    /**
     * La règle à coller, sur les trois compartiments.
     */
    private function remede(string $origine): void
    {
        $this->line('      <fg=gray>Console R2 → chacun des trois compartiments → CORS :</>');

        foreach (BrowserUploadCors::suggestion($origine) as $ligne) {
            $this->line('      <fg=magenta>'.$ligne.'</>');
        }
    }

    /**
     * L'envoi présigné, par l'adresse que verra le **navigateur**.
     *
     * Les deux lignes précédentes ne suffisent pas, et c'est ce qui a coûté un
     * après-midi. `stockage()` écrit avec nos identifiants par le point de
     * terminaison **serveur** ; `cors()` lit la règle du compartiment par le
     * même. Or un enregistrement part par une URL signée sur
     * `R2_PUBLIC_ENDPOINT` — l'adresse vue depuis le téléphone — et cette
     * valeur n'était éprouvée par rien.
     *
     * En local elle diffère à dessein : MinIO répond sur un autre hôte depuis
     * le Mac que depuis le conteneur. En production elle doit être identique,
     * et un `.env` recopié depuis une machine de développement y laisse une IP
     * privée ou un `localhost` — l'URL est alors parfaitement signée, et
     * injoignable depuis le réseau mobile du narrateur. Le navigateur rend une
     * erreur réseau, le serveur n'a rien vu passer, et « L'envoi n'a pas
     * abouti » s'affiche après dix minutes de récit (T-226).
     *
     * Le contrôle refait donc ce que fait le magnétophone, dans l'ordre :
     * ouvrir un envoi multipart, signer une part, la **déposer par HTTP**, et
     * relire l'`ETag` de la réponse. Puis il annule l'envoi : un multipart
     * abandonné se facture jusqu'à son expiration.
     */
    private function presigne(): void
    {
        if ((string) config('services.media.driver') !== 's3') {
            return;
        }

        $storage = new S3MediaStorage;
        ['private' => $prive, 'public' => $public] = $storage->endpoints();

        /*
         * `r2.dev` d'abord, parce que c'est **la** faute que l'interface de
         * Cloudflare invite : le tableau de bord affiche en grand « Public
         * R2.dev Bucket URL », et une variable nommée `R2_PUBLIC_ENDPOINT`
         * semble faite pour l'accueillir. Elle ne l'est pas. `r2.dev` est un
         * domaine de **lecture publique** ; il ne répond à aucune requête
         * signée de l'API S3, donc à aucun dépôt. Le navigateur n'obtient même
         * pas de statut — WebKit dit « Load failed » — et le message générique
         * « diffère de R2_ENDPOINT » n'expliquerait pas pourquoi (T-227).
         *
         * Le même réglage sert les liens d'écoute (`temporaryUrl`) : posé sur
         * `r2.dev`, il casse aussi la lecture chez les proches, plus tard et
         * ailleurs.
         */
        if (str_contains($public, '.r2.dev')) {
            $this->rouge('Adresse d’envoi', 'R2_PUBLIC_ENDPOINT est un domaine r2.dev : lecture publique seulement, aucun dépôt signé.');
            $this->line('      <fg=gray>C’est l’adresse que le tableau de bord met en avant, et ce n’est pas</>');
            $this->line('      <fg=gray>celle de l’API S3. Il faut le point de terminaison du compte :</>');
            $this->line('      <fg=magenta>R2_PUBLIC_ENDPOINT='.($prive === '' ? 'https://<compte>.eu.r2.cloudflarestorage.com' : $prive).'</>');
            $this->line('      <fg=gray>Les liens d’écoute des proches passent par le même réglage.</>');
        } elseif ($public !== $prive) {
            // Dit avant l'appel : la différence est légitime en local et
            // presque toujours un `.env` recopié en production.
            $this->selonEnv('Adresse d’envoi', sprintf(
                'R2_PUBLIC_ENDPOINT (%s) diffère de R2_ENDPOINT : le téléphone signe pour une adresse qui n’est pas celle du stockage.',
                $public === '' ? 'vide' : $public,
            ));
        }

        if ($this->option('rapide')) {
            return;
        }

        $cle = 'health/prod-check-presigne.bin';
        $uploadId = null;

        try {
            $uploadId = $storage->createMultipartUpload($cle, 'application/octet-stream');
            $url = $storage->presignPart($cle, $uploadId, 1);

            // Cinq octets : on éprouve le chemin, pas le débit.
            $reponse = Http::withBody('essai', 'application/octet-stream')
                ->timeout(15)
                ->put($url);

            if ($reponse->failed()) {
                $this->rouge('Envoi présigné', sprintf(
                    'le stockage refuse le dépôt (HTTP %d) : aucun enregistrement ne peut être envoyé.',
                    $reponse->status(),
                ));

                return;
            }

            if ($reponse->header('ETag') === '') {
                // Le serveur, lui, voit l'en-tête : s'il manque **ici**, ce
                // n'est pas une affaire de CORS mais de fournisseur.
                $this->rouge('Envoi présigné', 'le stockage ne rend pas d’ETag : l’envoi multipart ne peut pas se conclure.');

                return;
            }

            $this->vert('Envoi présigné', sprintf('dépôt accepté et ETag rendu par %s', $public));
        } catch (Throwable $e) {
            $this->rouge('Envoi présigné', 'impossible : '.$e->getMessage());
        } finally {
            if ($uploadId !== null) {
                try {
                    // Un multipart abandonné se facture jusqu'à expiration.
                    $storage->abortMultipart($cle, $uploadId);
                } catch (Throwable) {
                    // Rien à dire : le contrôle a déjà rendu son verdict.
                }
            }
        }
    }

    /**
     * Trois compartiments, et la juridiction où ils vivent.
     *
     * `05_A_FAIRE_HUMAIN.md` le demande depuis le début, et pour deux raisons
     * que rien dans le code ne rattrape :
     *
     *  - **Trois, et pas un.** La réplique est ce qui protège de la perte d'un
     *    audio confirmé — le SLO du doc 04 §11 dit « zéro perte après
     *    "histoire enregistrée" ». Une sauvegarde rangée dans le compartiment
     *    qu'elle sauvegarde ne protège de rien : ce qui efface l'un efface
     *    l'autre.
     *  - **La juridiction UE**, choisie à la création et **irréversible** sur
     *    R2. C'est une exigence non négociable du dossier (doc 04, hébergement
     *    UE), et elle se lit dans le point de terminaison : un compartiment de
     *    juridiction européenne s'adresse par `<compte>.eu.r2…`. Sans le
     *    `.eu.`, il est très probable que les compartiments aient été créés
     *    sans juridiction — et la seule sortie est de les recréer, donc mieux
     *    vaut le savoir avant qu'une famille y ait déposé sa voix (T-228).
     */
    private function compartiments(): void
    {
        if ((string) config('services.media.driver') !== 's3') {
            return;
        }

        $noms = [
            'media' => (string) config('filesystems.disks.r2.bucket'),
            'réplique' => (string) config('filesystems.disks.r2_replica.bucket'),
            'sauvegardes' => (string) config('filesystems.disks.r2_backups.bucket'),
        ];

        $vides = array_keys(array_filter($noms, static fn (string $nom): bool => $nom === ''));

        if ($vides !== []) {
            $this->rouge('Compartiments', sprintf('sans nom : %s. Ce qui y va est perdu.', implode(', ', $vides)));
        }

        // Des noms **distincts** : deux rôles dans un seul compartiment, et
        // ce qui efface l'un efface l'autre.
        $doublons = array_keys(array_filter(
            $noms,
            static fn (string $nom): bool => $nom !== '' && count(array_keys($noms, $nom, true)) > 1,
        ));

        if ($doublons !== []) {
            $this->rouge('Compartiments', sprintf(
                '%s partagent le même compartiment : une sauvegarde rangée avec ce qu’elle sauvegarde ne protège de rien.',
                implode(' et ', $doublons),
            ));
        }

        $endpoint = (new S3MediaStorage)->endpoints()['private'];

        if ($endpoint !== '' && ! str_contains($endpoint, '.eu.')) {
            /*
             * Orange et non rouge : la chaîne n'est pas coupée, personne ne
             * perd rien aujourd'hui. C'est une exigence du dossier, et elle se
             * répare en recréant les compartiments — d'où l'urgence de la voir
             * tôt, sans pour autant peindre en rouge une chaîne qui marche.
             */
            $this->orange('Juridiction du stockage', 'R2_ENDPOINT ne porte pas « .eu. » : les compartiments n’ont pas la juridiction UE, qui se choisit à la création et ne se change pas.');
            $this->line('      <fg=gray>Ce n’est pas la même chose qu’être hors d’Europe : un indice de</>');
            $this->line('      <fg=gray>localisation — « Western Europe (WEUR) » dans la console — dit où</>');
            $this->line('      <fg=gray>les données vivent. La juridiction, elle, l’impose et la verrouille.</>');
            $this->line('      <fg=gray>Lire la ligne « Location » de chaque compartiment, puis trancher :</>');
            $this->line('      <fg=gray>la localisation suffit-elle à l’engagement du dossier, ou faut-il</>');
            $this->line('      <fg=gray>recréer les compartiments ? Mieux vaut le décider tôt.</>');

            return;
        }

        if ($vides === [] && $doublons === []) {
            $this->vert('Compartiments', sprintf('%s, distincts, juridiction UE', implode(', ', $noms)));
        }
    }

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

            return;
        }

        $this->compartimentsVoisins($cle);
    }

    /**
     * La réplique et les sauvegardes existent-elles vraiment ?
     *
     * « Stockage des voix » ne sonde que le compartiment des médias, et c'est
     * le seul dont l'absence se voit tout de suite. Les deux autres se taisent
     * jusqu'au jour où on en a besoin : `ReplicateRecording` échoue dans un
     * ouvrier, et la sauvegarde n'existe pas au moment du désastre. Or c'est
     * la réplique qui porte le SLO du doc 04 §11 — « zéro perte après
     * "histoire enregistrée" » — et un nom de compartiment se tape à la main
     * dans un `.env`, au singulier quand la console l'a créé au pluriel
     * (T-228).
     */
    private function compartimentsVoisins(string $cle): void
    {
        if ($this->option('rapide') || (string) config('services.media.driver') !== 's3') {
            return;
        }

        $roles = [
            'Réplique' => ['r2_replica', 'la voix confirmée n’a pas de second exemplaire : le SLO « zéro perte » n’est pas tenu.'],
            'Sauvegardes' => ['r2_backups', 'aucune archive ne pourra être écrite le jour où il en faut une.'],
        ];

        foreach ($roles as $nom => [$disque, $consequence]) {
            try {
                $storage = new S3MediaStorage($disque);
                $storage->put($cle, 'ok', 'text/plain');
                $storage->head($cle);
                $storage->delete($cle);

                $this->vert($nom, 'joignable en écriture');
            } catch (Throwable $e) {
                $this->rouge($nom, $consequence.' '.$e->getMessage());
            }
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
    /**
     * `ffmpeg` et `ffprobe` : le premier maillon, et le plus silencieux.
     *
     * Tout ce qui suit une voix en dépend. Sans dérivé MP3, il n'y a rien à
     * envoyer au transcripteur : pas de texte, donc pas de relecture, pas de
     * validation, rien qui atteigne la famille, et pas de livre. Le narrateur,
     * lui, a vu « votre histoire est enregistrée » — et c'était vrai, le
     * stockage l'avait confirmée. Elle dort simplement là, sans suite.
     *
     * Et rien ne le dit : `TranscodeRecording` échoue dans un ouvrier, trois
     * fois, puis se range dans `failed_jobs` où personne ne regarde. C'est
     * ainsi qu'on l'a trouvé — dans les journaux, après coup, « sh: 1: exec:
     * /usr/bin/ffmpeg: not found » (T-230).
     *
     * `ffprobe` compte autant : c'est lui qui donne la **durée réelle**, celle
     * qui nourrit les critères book-ready de R-6. Sans lui, on ne sait plus si
     * la matière suffit à faire un livre.
     */
    private function ffmpeg(): void
    {
        $binaires = [
            'ffmpeg' => [
                (string) config('product.media.ffmpeg'),
                'aucune voix ne devient du texte : ni relecture, ni validation, ni livre.',
            ],
            'ffprobe' => [
                (string) config('product.media.ffprobe'),
                'aucune durée réelle : les critères du livre (R-6) ne peuvent pas être mesurés.',
            ],
        ];

        foreach ($binaires as $nom => [$chemin, $consequence]) {
            if ($chemin === '') {
                $this->rouge($nom, 'aucun chemin configuré — '.$consequence);

                continue;
            }

            try {
                $resultat = Process::timeout(10)->run([$chemin, '-version']);
            } catch (Throwable $e) {
                $this->rouge($nom, $consequence.' '.$e->getMessage());

                continue;
            }

            if ($resultat->failed()) {
                $this->rouge($nom, sprintf('%s introuvable — %s', $chemin, $consequence));
                $this->line('      <fg=magenta>sudo apt-get install -y ffmpeg</> <fg=gray>puis `php artisan queue:retry all`</>');

                continue;
            }

            // La version, parce qu'un binaire trop ancien ne sait pas encoder
            // ce que le navigateur produit : WebM/Opus d'un Chrome Android.
            $premiere = strtok($resultat->output(), PHP_EOL);

            $this->vert($nom, is_string($premiere) ? mb_substr($premiere, 0, 60) : 'présent');
        }
    }

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
