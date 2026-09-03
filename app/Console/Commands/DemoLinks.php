<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\TokenType;
use App\Features\ReactionNotificationTiming;
use App\Models\FamilyMember;
use App\Models\Story;
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
     * Une liste, et non un tableau indexé par numéro de bloc : PHP convertirait
     * les clés « 10 », « 11 » et « 12 » en entiers tout en laissant « 07 »
     * en chaîne, et le filtre `--bloc` porterait sur deux types à la fois.
     *
     * @return list<array{bloc: string, titre: string, avant?: list<string>, etapes: list<array{quoi: string, url?: string, cmd?: string, bloque?: string}>}>
     */
    private function sheet(): array
    {
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
                'avant' => ['forcer les horodatages : un lien envoyé il y a 3 jours non ouvert, une histoire partagée il y a 5 jours non écoutée, un silence de 21 jours.'],
                'etapes' => [
                    ['quoi' => 'Passer un tour de moteur.', 'cmd' => 'engine:tick'],
                    ['quoi' => 'Lire `outbound_messages` : un renvoi sur l’autre canal, un rappel par proche, une alerte à l’Initiateur·rice avec quatre liens en un tap.'],
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
                    ['quoi' => 'Commander 49 € plus l’option téléphone 25 € avec la carte de test.', 'bloque' => 'compte Stripe de test et ses cinq prix'],
                    ['quoi' => 'Recevoir le webhook et vérifier commande, projet, narrateur, option.', 'bloque' => 'Stripe CLI'],
                    [
                        'quoi' => 'Elle accepte : les quatre cases, puis projet actif, premier prompt au lendemain 09:00, fiche contact proposée.',
                        'url' => self::link(TokenType::Invitation, 'optin-accept'),
                    ],
                    [
                        'quoi' => 'Elle refuse : l’Initiateur·rice reçoit le message avec tact, et l’effacement des coordonnées est daté.',
                        'url' => self::link(TokenType::Invitation, 'optin-refuse'),
                    ],
                    ['quoi' => 'Son espace : réordonner deux questions, inviter un proche, copier le lien WhatsApp, demander la rétractation.', 'url' => rtrim((string) config('app.url'), '/').'/espace'],
                ],
            ],
            [
                'bloc' => '11',
                'titre' => 'Le back-office, et ce qu’il laisse comme trace',
                'etapes' => [
                    ['quoi' => 'Se connecter : la configuration TOTP est forcée au premier accès, puis exigée ensuite.', 'url' => rtrim((string) config('app.url'), '/').'/admin'],
                    ['quoi' => 'Ouvrir une histoire partagée, écouter 5 secondes, corriger un mot : trois entrées d’audit, dont la correction avec son diff.'],
                    ['quoi' => 'Modifier une ligne d’audit à la main dans psql : le trigger refuse. Puis vérifier la chaîne.', 'cmd' => 'audit:verify'],
                    ['quoi' => 'Avec un compte en lecture seule : aucun bouton d’action, et 403 sur une tentative directe.'],
                    ['quoi' => 'Rembourser partiellement une commande.', 'bloque' => 'compte Stripe de test'],
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
                        'quoi' => 'Téléverser le fichier de test EICAR : refus avec message, rien dans le stockage, une ligne dans l’audit. Demande ANTIVIRUS_SCANNER=clamav, donc laradev --clamav.',
                        'url' => self::record('variant-a'),
                    ],
                    ['quoi' => 'Une photo de 800 px : « un peu petite pour l’impression », et elle n’est pas marquée prête pour le livre.'],
                ],
            ],
        ];
    }
}
