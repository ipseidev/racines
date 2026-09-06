<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\TokenType;
use App\Features\ReactionNotificationTiming;
use App\Models\FamilyMember;
use App\Models\Story;
use App\Settings\PilotSettings;
use App\Support\Links;
use Database\Seeders\E2ELinksSeeder;
use Illuminate\Console\Command;
use Laravel\Pennant\Feature;

/**
 * La feuille des vérifications humaines.
 *
 * Six checkpoints attendent quelqu'un devant un navigateur, et chacun demande
 * un lien à jeton de quarante-trois caractères, un téléphone connu ou un code
 * à six chiffres. Rien de tout cela ne se retient, et le calculer à la main
 * — `str_pad("demo-variant-a-link", 43, 'x')` — est le genre de détail qui
 * transforme une vérification de dix minutes en une demi-heure de tinker.
 *
 * Les valeurs viennent de `E2ELinksSeeder`, jamais recopiées : une feuille de
 * test fausse coûte plus cher que pas de feuille du tout. Les URL passent par
 * `App\Support\Links`, donc par le domaine court réel — celui du tunnel quand
 * `laradev --tunnel` tourne, `localhost:8001` sinon.
 */
final class DemoLinks extends Command
{
    protected $signature = 'demo:liens {--bloc= : N’imprimer qu’un bloc, par exemple 07}';

    protected $description = 'La feuille des vérifications humaines : liens, comptes et codes du décor local.';

    public function handle(): int
    {
        if (app()->isProduction()) {
            $this->components->error('Ces liens n’existent que dans une base semée. Jamais en production.');

            return self::FAILURE;
        }

        $only = $this->option('bloc');

        if (! is_string($only) || $only === '') {
            $this->preamble();
        }

        foreach ($this->sheet() as $block) {
            if (is_string($only) && $only !== '' && $block['bloc'] !== str_pad($only, 2, '0', STR_PAD_LEFT)) {
                continue;
            }

            $this->block($block);
        }

        return self::SUCCESS;
    }

    private function preamble(): void
    {
        $this->newLine();
        $this->components->info('Décor local — le semis doit avoir tourné : sail artisan migrate:fresh --seed');
        $this->line('  Les liens ci-dessous ont une valeur connue. Ils meurent à chaque nouveau semis.');
        $this->newLine();
        $this->components->twoColumnDetail('<fg=cyan>Back-office</>', (string) config('product.seeding.admin_email').' / '.(string) config('product.seeding.admin_password'));
        $this->components->twoColumnDetail('  secret TOTP (à coller dans une appli d’authentification)', E2ELinksSeeder::E2E_TOTP_SECRET);
        $this->components->twoColumnDetail('<fg=cyan>Espace Initiateur·rice</>', E2ELinksSeeder::INITIATOR_EMAIL.' / '.(string) config('product.seeding.admin_password'));
        $this->components->twoColumnDetail('<fg=cyan>Code OTP de l’espace narrateur</>', E2ELinksSeeder::SPACE_CODE);
        $this->components->twoColumnDetail('<fg=cyan>Courriels sortants</>', 'Mailpit, http://localhost:8027');
        $this->newLine();
    }

    /**
     * @param  array{bloc: string, titre: string, avant?: list<string>, etapes: list<array{quoi: string, url?: string, cmd?: string, bloque?: string}>}  $block
     */
    private function block(array $block): void
    {
        $this->newLine();
        $this->line("<fg=yellow;options=bold>── Bloc {$block['bloc']} — {$block['titre']}</>");
        $this->newLine();

        foreach ($block['avant'] ?? [] as $line) {
            $this->line("  <fg=gray>Avant :</> {$line}");
        }

        if (($block['avant'] ?? []) !== []) {
            $this->newLine();
        }

        foreach ($block['etapes'] as $index => $step) {
            $marker = isset($step['bloque']) ? '<fg=red>✗</>' : '<fg=green>•</>';
            $this->line("  {$marker} ".($index + 1).'. '.$step['quoi']);

            if (isset($step['url'])) {
                $this->line("       <fg=blue>{$step['url']}</>");
            }

            if (isset($step['cmd'])) {
                $this->line("       <fg=magenta>sail artisan {$step['cmd']}</>");
            }

            if (isset($step['bloque'])) {
                $this->line("       <fg=red>bloqué : {$step['bloque']}</>");
            }
        }
    }

    private const NO_SEED = '⚠ décor absent — sail artisan migrate:fresh --seed';

    /**
     * L'état du drapeau des réactions, imprimé dans la feuille.
     *
     * C'est un état **invisible** qui invalide silencieusement le point 3 : un
     * projet passé à « lendemain matin » n'envoie plus rien tout de suite, et
     * rien à l'écran ne le dit. La feuille l'affiche donc, parce qu'elle a
     * déjà fait perdre une vérification (écart T-131).
     */
    private static function reactionTiming(string $scenario): string
    {
        $subject = E2ELinksSeeder::subjectOf($scenario);

        if (! $subject instanceof FamilyMember && ! $subject instanceof Story) {
            return self::NO_SEED;
        }

        return (string) Feature::for($subject->project)->value(ReactionNotificationTiming::class);
    }

    /** L'identifiant du projet qui porte un scénario. */
    private static function projectId(string $scenario): string
    {
        $subject = E2ELinksSeeder::subjectOf($scenario);

        if ($subject instanceof FamilyMember || $subject instanceof Story) {
            return (string) $subject->project_id;
        }

        return self::NO_SEED;
    }

    /** L'adresse forgée d'une histoire, pour éprouver ce qu'un lien ne doit pas ouvrir. */
    private static function storyUrl(string $familyScenario, string $storyScenario): string
    {
        $story = E2ELinksSeeder::subjectOf($storyScenario);

        if (! $story instanceof Story) {
            return self::NO_SEED;
        }

        return self::link(TokenType::ListenProject, $familyScenario).'/stories/'.$story->id;
    }

    private static function record(string $scenario): string
    {
        return Links::record(E2ELinksSeeder::token($scenario));
    }

    private static function link(TokenType $type, string $scenario): string
    {
        return Links::for($type, E2ELinksSeeder::token($scenario));
    }

    /**
     * Un prix en euros, lu dans les réglages : la feuille disait « 49 € »
     * quand le produit se vendait déjà 89 € (T-136), et personne ne relit
     * une feuille de vérifications pour y corriger un prix.
     */
    private static function euros(int $cents): string
    {
        $decimals = $cents % 100 === 0 ? 0 : 2;

        return number_format($cents / 100, $decimals, ',', ' ').' €';
    }

    /**
     * Une liste, et non un tableau indexé par numéro de bloc : PHP convertirait
     * les clés « 10 », « 11 » et « 12 » en entiers tout en laissant « 07 »
     * en chaîne, et le filtre `--bloc` porterait sur deux types à la fois.
     *
     * @return list<array{bloc: string, titre: string, avant?: list<string>, etapes: list<array{quoi: string, url?: string, cmd?: string, bloque?: string}>}>
     */
    private function sheet(): array
    {
        $pilot = app(PilotSettings::class);

        return [
            [
                'bloc' => '07',
                'titre' => 'Elle valide, ou elle retire',
                'etapes' => [
                    [
                        'quoi' => 'Variante A — enregistrer, puis choisir « Partager ». Les trois choix doivent apparaître sans présélection et sans minuteur.',
                        'url' => self::record('variant-a'),
                    ],
                    [
                        'quoi' => 'Variante A, second lien — enregistrer, puis choisir « Décider plus tard ». La notification de relecture doit arriver après la transcription (journal ou Mailpit), et son lien mener à la page de relecture.',
                        'url' => self::record('variant-a-later'),
                    ],
                    [
                        'quoi' => '↳ le même projet vu par la famille : rien avant la décision, l’histoire après le partage.',
                        'url' => self::link(TokenType::ListenProject, 'variant-a-later-famille'),
                    ],
                    [
                        'quoi' => 'Variante B — le texte est déjà prêt. Corriger un mot, puis partager. Le mot corrigé doit rester après rechargement.',
                        'url' => self::record('variant-b').'/review',
                    ],
                    [
                        'quoi' => '↳ le même projet vu par la famille : après le partage, l’histoire doit apparaître ici.',
                        'url' => self::link(TokenType::ListenProject, 'variant-b-famille'),
                    ],
                    [
                        'quoi' => 'Variante B, second lien — décider « Garder pour moi ».',
                        'url' => self::record('variant-b-share').'/review',
                    ],
                    [
                        'quoi' => '↳ le même projet vu par la famille : rien ne doit jamais apparaître ici. C’est la promesse entière du bloc.',
                        'url' => self::link(TokenType::ListenProject, 'variant-b-share-famille'),
                    ],
                    [
                        'quoi' => 'Masquer un récit déjà partagé, depuis son propre lien d’enregistrement.',
                        'url' => self::record('withdraw'),
                    ],
                    [
                        'quoi' => '↳ le même projet vu par la famille : une histoire avant, aucune après. Recharger, sans attendre.',
                        'url' => self::link(TokenType::ListenProject, 'withdraw-famille'),
                    ],
                    [
                        'quoi' => 'Son espace personnel, par le chemin réel : demander un code pour '.E2ELinksSeeder::SPACE_NARRATORS['space'].' (le SMS part dans le journal en local), ou entrer directement par le lien ci-dessous. Le code du décor est '.E2ELinksSeeder::SPACE_CODE.'.',
                        'url' => rtrim((string) config('app.url'), '/').'/n/request',
                    ],
                    [
                        'quoi' => 'Depuis cet espace : mettre une histoire à la corbeille, la restaurer, puis la supprimer en tapant SUPPRIMER. La suppression doit demander le mot en entier.',
                        'url' => self::link(TokenType::NarratorSpace, 'space'),
                    ],
                    [
                        'quoi' => 'Une histoire à la corbeille depuis plus de trente jours doit voir ses fichiers disparaître.',
                        'cmd' => 'stories:purge-trashed',
                    ],
                ],
            ],
            [
                'bloc' => '08',
                'titre' => 'La famille écoute',
                'avant' => [
                    'les points sont à jouer **dans l’ordre** : le point 4 change le drapeau des réactions, et le point 3 ne veut plus rien dire après lui.',
                    'drapeau du projet d’essai, en ce moment : **'.self::reactionTiming('listen-react').'**.',
                ],
                'etapes' => [
                    [
                        'quoi' => 'Inviter un proche : le courriel arrive dans Mailpit avec son lien d’écoute, qui doit s’ouvrir.',
                        'cmd' => 'family:invite '.self::projectId('listen').' "Marie" marie@example.test',
                    ],
                    [
                        'quoi' => 'Ouvrir un lien d’écoute : seules les histoires partagées apparaissent. Masquer une histoire depuis l’espace narrateur la fait disparaître aussitôt.',
                        'url' => self::link(TokenType::ListenProject, 'listen'),
                    ],
                    [
                        'quoi' => 'Écouter 35 secondes, puis réagir « Merci » avec un mot. La notification est **différée d’une minute**, à dessein : un cœur et un merci envoyés d’affilée ne font qu’un seul SMS. Il faut donc attendre soixante secondes avant de conclure.',
                        'url' => self::link(TokenType::ListenProject, 'listen-react'),
                    ],
                    [
                        'quoi' => 'Passer le drapeau à « lendemain matin », puis réagir de nouveau : rien ne doit partir.',
                        'cmd' => 'demo:reaction-timing next-morning',
                    ],
                    [
                        'quoi' => 'Antidater la réaction — le résumé lit celles de la veille — puis l’envoyer.',
                        'cmd' => 'demo:reaction-timing --veille  puis  sail artisan reactions:send-digests',
                    ],
                    [
                        'quoi' => 'Remettre le drapeau où il était, sinon le point 3 ne sera plus jouable.',
                        'cmd' => 'demo:reaction-timing immediate',
                    ],
                    [
                        'quoi' => 'Forger l’adresse de l’histoire que vous venez de masquer, sur son propre lien famille : page « non disponible », et aucune donnée dans la réponse — ni le titre, ni le texte, ni l’audio.',
                        'url' => self::storyUrl('withdraw-famille', 'withdraw'),
                    ],
                ],
            ],
            [
                'bloc' => '09',
                'titre' => 'Le moteur relance sans harceler',
                'avant' => ['les trois horodatages s’arment par une commande, pas à la main : `sail artisan demo:moteur`. Elle dit ce qu’elle a armé, et se rejoue.'],
                'etapes' => [
                    ['quoi' => 'Armer les trois signaux : lien envoyé il y a 3 jours et jamais ouvert, histoire partagée depuis 5 jours et jamais écoutée, silence de 21 jours.', 'cmd' => 'demo:moteur'],
                    ['quoi' => 'Passer un tour de moteur.', 'cmd' => 'engine:tick'],
                    ['quoi' => 'Lire `outbound_messages` : un renvoi sur l’autre canal, un rappel par proche, une alerte à l’Initiateur·rice avec quatre liens en un tap. Deux autres règles apparaissent dans `engine_events` marquées supprimées : elles parlaient au même narrateur le même jour, et c’est la garde anti-harcèlement qui les a tues.'],
                    ['quoi' => 'Repasser un tour : plus rien ne part. C’est le point qui compte — un moteur qui relance deux fois harcèle.', 'cmd' => 'engine:tick'],
                    [
                        'quoi' => 'Cliquer « toutes les deux semaines » : confirmation, cadence changée, prochaine relance recalculée.',
                        'url' => self::link(TokenType::Action, 'onetap'),
                    ],
                    ['quoi' => 'Le rapport doit montrer les trois déclenchements.', 'cmd' => 'engine:report'],
                ],
            ],
            [
                'bloc' => '10',
                'titre' => 'On achète, elle dit oui ou non',
                'etapes' => [
                    ['quoi' => 'Page d’accueil, puis l’essai : enregistrer 20 secondes, réécouter. Rien ne doit partir.', 'url' => rtrim((string) config('app.url'), '/').'/essai'],
                    ['quoi' => sprintf('Commander %s plus l’option téléphone %s avec la carte de test.', self::euros($pilot->pilot_price_cents), self::euros($pilot->phone_option_price_cents)), 'cmd' => 'stripe listen --api-key "$(grep \'^STRIPE_SECRET=\' .env | cut -d= -f2-)" --forward-to http://localhost:8001/stripe/webhook'],
                    ['quoi' => 'Recevoir le webhook et vérifier commande, projet, narrateur, option. L’écouteur imprime le code de réponse : un 200 sur `checkout.session.completed` et la commande existe.'],
                    [
                        'quoi' => 'Elle accepte : les quatre cases, puis projet actif, premier prompt au jour choisi 09:00, fiche contact proposée. **Lien à usage unique**, et la suite bout en bout le consomme : s’il répond « vous avez déjà répondu », prenez-en un neuf avec `demo:invitation`.',
                        'url' => self::link(TokenType::Invitation, 'optin-accept'),
                    ],
                    [
                        'quoi' => 'Elle refuse : l’Initiateur·rice reçoit le message avec tact, et l’effacement des coordonnées est daté. Même remarque : un lien neuf s’obtient par `demo:invitation`.',
                        'url' => self::link(TokenType::Invitation, 'optin-refuse'),
                    ],
                    ['quoi' => 'Un lien d’invitation neuf, à volonté — chaque appel fabrique un projet, parce que l’opt-in ne se rejoue pas.', 'cmd' => 'demo:invitation'],
                    ['quoi' => 'Son espace : réordonner deux questions, inviter un proche, copier le lien WhatsApp, demander la rétractation.', 'url' => rtrim((string) config('app.url'), '/').'/espace'],
                ],
            ],
            [
                'bloc' => '11',
                'titre' => 'Le back-office, et ce qu’il laisse comme trace',
                'etapes' => [
                    ['quoi' => 'Se connecter avec `premiere-connexion@example.test` : la configuration du second facteur est forcée, puis exigée ensuite. **Ce compte est là pour ça** — le compte d’administration principal a déjà son second facteur, semé pour la suite bout en bout, et demanderait un code au lieu de proposer la configuration (T-180).', 'url' => rtrim((string) config('app.url'), '/').'/admin'],
                    ['quoi' => 'Ouvrir une histoire partagée, écouter 5 secondes, corriger un mot depuis la fiche : trois entrées d’audit. Le compte principal a son second facteur semé pour la suite bout en bout ; son code s’imprime, il n’est pas perdu.',
                        'cmd' => 'demo:totp'],
                    ['quoi' => 'Modifier une ligne d’audit à la main dans psql : le trigger refuse. Puis vérifier la chaîne.', 'cmd' => 'audit:verify'],
                    ['quoi' => 'Avec le compte de lecture seule `lecture@example.test` (même mot de passe) : aucun bouton d’action, et 403 sur une tentative directe.'],
                    [
                        'quoi' => 'Rembourser partiellement une commande, écouteur allumé. La commande du décor porte une référence factice que Stripe refuserait : la ligne ci-dessous fabrique un vrai paiement de test et l’attache à la commande, sans repasser par le tunnel d’achat.',
                        'cmd' => 'demo:paiement',
                    ],
                ],
            ],
            [
                'bloc' => '12',
                'titre' => 'Les photos, et qui a le droit d’en mettre',
                'etapes' => [
                    ['quoi' => 'Ajouter une photo HEIC prise à l’instant : elle ressort en JPEG, sans GPS, bien orientée.', 'bloque' => 'un téléphone réel et un accès HTTPS (laradev --tunnel)'],
                    [
                        'quoi' => 'Un proche autorisé ajoute une photo depuis son lien d’écoute ; un autre ne voit pas le bouton et reçoit 403 en POST.',
                        'url' => self::link(TokenType::ListenProject, 'listen-photo'),
                    ],
                    [
                        'quoi' => 'Téléverser le fichier de test EICAR : refus avec message, rien dans le stockage, une ligne dans l’audit. Demande `ANTIVIRUS_SCANNER=clamav` et le conteneur démarré : `docker compose up -d clamav`, une minute jusqu’à `healthy` (T-185).',
                        'url' => self::record('variant-a'),
                    ],
                    ['quoi' => 'Une photo de 800 px : « un peu petite pour l’impression », et elle n’est pas marquée prête pour le livre.'],
                ],
            ],
            [
                'bloc' => '13',
                'titre' => 'Le livre, et ce qu’il promet pour dix ans',
                'avant' => [
                    'La matière ne se fabrique pas à la main : `sail artisan demo:livre` ajoute dix histoires validées, `--riche` en fait assez pour un livre complet.',
                    'Le rendu réel demande Chromium, présent dans l’image depuis T-196, et `PDF_DRIVER=browsershot`.',
                ],
                'etapes' => [
                    [
                        'quoi' => 'Matière intermédiaire : la jauge doit dire qu’il manque quelque chose, et proposer un livret.',
                        'cmd' => 'demo:livre puis books:evaluate',
                    ],
                    [
                        'quoi' => 'Assez de matière : la jauge passe au vert et la forme devient un livre. Les quatre mesures comptent, pas un pourcentage — le seuil qui manque en dernier est celui des pages.',
                        'cmd' => 'demo:livre --riche puis books:evaluate',
                    ],
                    [
                        'quoi' => 'Exclure une histoire, en remonter une autre, écrire un avant-propos, vérifier les noms propres, puis générer le bon à tirer. Le PDF s’ouvre : sommaire juste, un QR par chapitre, colophon avec la durée d’engagement.',
                        'url' => url('/espace/livre'),
                    ],
                    [
                        'quoi' => 'Scanner un QR du PDF avec un téléphone : la page d’écoute s’ouvre sans compte. Poser un code famille sur le projet, rescanner : le code est demandé.',
                    ],
                    [
                        'quoi' => 'Approuver sans cocher les deux cases : refus. Cocher, approuver : la sélection se verrouille, les histoires entrent au livre, et un ticket d’impression apparaît avec le PDF.',
                        'url' => url('/admin/books'),
                    ],
                    [
                        'quoi' => 'Désactiver le QR d’une histoire depuis l’espace du narrateur : la page dit que le texte imprimé reste, sans dire pourquoi. Le réactiver redonne le **même** code.',
                        'url' => self::link(TokenType::NarratorSpace, 'space'),
                    ],
                ],
            ],
        ];
    }
}
