<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Actions\AcceptInvitation;
use App\Actions\AddFamilyMember;
use App\Actions\AddNarrator;
use App\Actions\AttachPhoto;
use App\Actions\CreateProject;
use App\Actions\ProposeStory;
use App\Actions\RecordConsent;
use App\Actions\ScheduleNextPrompt;
use App\Actions\ValidateStoryAction;
use App\Engine\Actions\OneTapRegistry;
use App\Engine\Actions\SwitchBiweekly;
use App\Enums\AnswerType;
use App\Enums\Cadence;
use App\Enums\Channel;
use App\Enums\ConsentChannel;
use App\Enums\Offer;
use App\Enums\OtpPurpose;
use App\Enums\ProjectStatus;
use App\Enums\QuestionTheme;
use App\Enums\ShareDecision;
use App\Enums\Sku;
use App\Enums\TokenType;
use App\Enums\TranscriptKind;
use App\Enums\ValidatedVia;
use App\Enums\ValidationVariant;
use App\Models\AccessToken;
use App\Models\Invitation;
use App\Models\Narrator;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OtpChallenge;
use App\Models\Project;
use App\Models\Question;
use App\Models\Recording;
use App\Models\Story;
use App\Models\Transcript;
use App\Models\User;
use App\Services\Storage\MediaStorage;
use App\Services\Tokens\OtpService;
use App\Services\Tokens\TokenService;
use App\Settings\PilotSettings;
use App\States\Story\Recorded;
use App\States\Story\Shared;
use App\States\Story\ToReview;
use App\States\Story\Transcribed;
use App\Support\ObjectKeys;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Liens d'enregistrement à valeur connue, pour la démonstration locale et
 * pour la suite bout en bout.
 *
 * **Un lien par scénario, sur sa propre histoire.** C'est le point important :
 * les tests Playwright tournent en parallèle, et un lien partagé faisait
 * qu'un test enregistrait l'histoire que le suivant s'attendait à trouver
 * vierge. L'isolation vit ici, pas dans un `--workers=1` qui masquerait le
 * problème.
 *
 * Ces liens n'existent que dans une base semée par ce seeder, qui refuse de
 * tourner en production. Deux d'entre eux sont morts par construction.
 */
final class E2ELinksSeeder extends Seeder
{
    public const OWNER_EMAIL = 'liens@example.test';

    /** @var array<string, string> */
    private const SCENARIOS = [
        'record' => 'Racontez-nous votre premier jour d’école.',
        'guard' => 'Quel objet avez-vous gardé de votre enfance ?',
        'resume' => 'Quel était le métier de votre père ?',
        'denied' => 'Quelle chanson vous rappelle votre jeunesse ?',
        'a11y' => 'Comment était la cuisine de votre enfance ?',
        'budget' => 'Quel voyage vous a le plus marqué ?',
        // T-210 : le même parcours, caméra ouverte.
        'video' => 'À quoi ressemblait votre maison d’enfance ?',
        // T-251 : une question posée **avec** une image, puis avec plusieurs.
        // La famille tend la photo et demande ; c'est le cas qui n'avait
        // jamais été vu à l'écran, faute d'affichage.
        'photo-question' => 'Racontez-nous cette photo.',
        'photos-question' => 'Que se passait-il ce jour-là ?',
        'expired' => 'Quelle est votre plus belle rencontre ?',
        'revoked' => 'Qu’aimeriez-vous que l’on retienne de vous ?',
        // Bloc 07 : un scénario par variante de validation, plus un retrait.
        'variant-a' => 'Quel jeu aimiez-vous enfant ?',
        // La variante A a deux chemins : décider tout de suite, ou remettre à
        // la relecture. Deux liens, donc, sur deux histoires — le second
        // n'était jouable qu'après avoir consommé le premier.
        'variant-a-later' => 'Quel était votre jouet préféré ?',
        // Trois liens pour la variante B, et non un seul : la suite tourne en
        // parallèle, et un test qui corrige le texte ne doit pas travailler
        // sur l'histoire qu'un autre vient de partager (leçon de T-59).
        'variant-b' => 'Quelle odeur vous ramène à votre enfance ?',
        'variant-b-edit' => 'Quel plat vous rappelle votre mère ?',
        'variant-b-share' => 'Quelle fête aimiez-vous le plus ?',
        'withdraw' => 'Quel conseil donneriez-vous à vos petits-enfants ?',
    ];

    /**
     * Les scénarios du bloc 07 demandent un décor plus riche qu'un lien : une
     * variante de validation, un état d'histoire, parfois des transcriptions.
     *
     * @var array<string, array<string, mixed>>
     */
    private const BLOCK_07 = [
        // Variante A : l'histoire vient d'être enregistrée, les trois choix
        // s'affichent après la confirmation. Le lien reste `proposed` pour
        // que la suite puisse enregistrer pour de vrai.
        'variant-a' => ['variant' => 'immediate'],
        // Apparié à la famille : « décider plus tard » se juge au bout de la
        // chaîne — la notification arrive, la relecture permet de corriger,
        // et alors seulement l'histoire apparaît côté famille.
        'variant-a-later' => ['variant' => 'immediate', 'family' => true],
        // Variante B : le texte est prêt, la relecture attend.
        //
        // `family` apparie un lien d'écoute **sur le projet du scénario**. Sans
        // lui, « elle décide, et la famille voit ou ne voit pas » ne se vérifie
        // avec aucun lien du décor : les liens d'écoute du bloc 08 vivent
        // chacun dans leur propre projet, et la variante de validation étant un
        // réglage de projet, ces scénarios ne peuvent pas les rejoindre.
        'variant-b' => ['variant' => 'deferred', 'reach' => 'to_review', 'transcripts' => true, 'family' => true],
        'variant-b-edit' => ['variant' => 'deferred', 'reach' => 'to_review', 'transcripts' => true],
        'variant-b-share' => ['variant' => 'deferred', 'reach' => 'to_review', 'transcripts' => true, 'family' => true],
        // Un récit partagé, qu'on va masquer depuis son propre lien. Il porte
        // ses transcriptions et son audio : c'est le seul scénario où la
        // famille doit voir quelque chose **avant** que la personne agisse.
        'withdraw' => ['variant' => 'immediate', 'reach' => 'shared', 'transcripts' => true, 'audio' => true, 'family' => true],
    ];

    /**
     * Coordonnées et code connus de la suite, pour l'espace narrateur.
     *
     * Trois coordonnées, et non une : trois demandes de code par heure et par
     * coordonnée est la bonne règle produit, c'est donc la suite qui doit
     * avoir de quoi jouer chaque scénario sans se marcher dessus.
     *
     * @var array<string, string>
     */
    public const SPACE_NARRATORS = [
        'space' => '+33600000042',
        'space-code' => '+33600000043',
        'space-wrong' => '+33600000044',
        'space-act' => '+33600000045',
        'space-del' => '+33600000046',
        'space-read' => '+33600000047',
    ];

    public const SPACE_CODE = '424242';

    /**
     * Liens d'écoute à valeur connue (bloc 08). Un par scénario, pour la même
     * raison que partout ailleurs : la suite tourne en parallèle, et un test
     * qui réagit changerait ce que le voisin s'attend à lire.
     *
     * @var list<string>
     */
    public const FAMILY_LINKS = ['listen', 'listen-react', 'listen-a11y', 'listen-photo'];

    /** @var list<string> */
    public const ONE_TAP_LINKS = ['onetap', 'onetap-use', 'onetap-read'];

    /**
     * Liens d'invitation à valeur connue (bloc 10).
     *
     * Un par scénario, et pour une raison plus forte qu'ailleurs : l'opt-in
     * est **définitif**. Un lien partagé entre deux tests fait échouer le
     * second sur l'écran « vous avez déjà répondu », et pour une fois le
     * produit a raison.
     *
     * Trois liens et non deux : `optin-accept` accepte pour de bon,
     * `optin-accept-partial` ne coche qu'une case et doit trouver la page
     * intacte, `optin-refuse` décline. En parallèle les deux premiers
     * passaient par chance ; à un seul ouvrier — comme en intégration
     * continue — le premier consommait le lien du second (écart T-111).
     *
     * @var list<string>
     */
    public const INVITATION_LINKS = ['optin-accept', 'optin-accept-partial', 'optin-refuse'];

    /**
     * Le compte de l'Initiateur·rice pour la suite bout en bout.
     *
     * Séparé du compte propriétaire des autres décors : `InitiatorProject`
     * prend le projet **le plus récent** d'une personne, et le compte
     * `liens@example.test` en possède une douzaine. Un espace dont on ne sait
     * pas quel projet il affiche ne se teste pas.
     */
    public const INITIATOR_EMAIL = 'espace@example.test';

    /** @var array<string, array<string, mixed>> */
    private const LINK_STATE = [
        'expired' => ['expires_at' => '-1 day'],
        'revoked' => ['revoked_at' => '-1 hour'],
    ];

    /**
     * Le secret TOTP du compte d'administration, pour la suite bout en bout.
     *
     * Un secret partagé, connu de quiconque lit le dépôt — et c'est pour cela
     * qu'il vit **ici** et non dans `AdminUserSeeder` : ce seeder refuse de
     * tourner en production. La double authentification du back-office est
     * obligatoire depuis le bloc 11, et un test qui ne saurait pas produire de
     * code ne pourrait la franchir qu'en la désactivant. Une garde désactivée
     * en test est une garde qu'on ne teste pas.
     */
    public const E2E_TOTP_SECRET = 'JBSWY3DPEHPK3PXPJBSWY3DPEHPK3PXP';

    public function run(): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('Ce seeder ne doit pas tourner en production.');
        }

        $this->seedAdminSecondFactor();

        // Le décor comprend les compteurs de limitation de débit, qui vivent
        // dans le cache et survivent à `migrate:fresh`. Sans ce vidage, le
        // scénario « demander un nouveau lien » — une demande par heure et par
        // jeton, et c'est la bonne règle produit — ne passe qu'une fois par
        // heure sur la machine, et échoue au deuxième appel de la suite.
        Cache::flush();

        // L'espace Initiateur·rice se sème **avant** la garde du banc d'essai :
        // il a la sienne, et relancer ce seeder sur une base déjà semée doit
        // pouvoir le compléter (sa commande, arrivée après coup) sans
        // `migrate:fresh`, qui effacerait aussi les réglages de la machine.
        $this->seedInitiatorSpace();

        $owner = User::query()->firstOrCreate(
            ['email' => self::OWNER_EMAIL],
            [
                // Un nom de personne, et non « Banc d'essai » : depuis
                // T-251, le prénom du déposant se lit à l'écran sous la
                // photo qui pose la question — « Envoyée par Banc » n'aurait
                // rien montré de ce qu'on veut vérifier.
                'name' => 'Claire Dubois',
                'password' => Hash::make((string) config('product.seeding.admin_password')),
                'email_verified_at' => now(),
            ],
        );

        $project = Project::query()->where('owner_user_id', $owner->id)->first();

        if ($project instanceof Project) {
            return;
        }

        $project = app(CreateProject::class)->handle($owner, Offer::Pilot, []);
        $project->status = ProjectStatus::Active;
        $project->accepted_at = now()->subDays(60);
        // Le partage permanent est le sixième accord du « J'accepte »
        // (T-250) : un projet accepté le porte, et l'écran de fin annonce au
        // lieu de demander. Sans cette date, la démonstration montrerait un
        // état que le produit n'a plus.
        $project->declared_sharing_at = now()->subDays(60);
        $project->save();

        $this->consentingNarrator($project, [
            'first_name' => 'Odette',
            'display_name' => 'Odette',
            'phone_e164' => '+33600000001',
            'birth_year' => 1943,
        ]);

        $project->refresh();

        foreach (self::SCENARIOS as $scenario => $text) {
            $question = self::question('e2e-'.$scenario, $text);

            // Les scénarios du bloc 07 vivent chacun dans **leur** projet :
            // la variante de validation est un réglage de projet, et deux
            // variantes ne cohabitent pas.
            $host = isset(self::BLOCK_07[$scenario])
                ? $this->projectForScenario($owner, $scenario)
                : $project;

            $story = app(ProposeStory::class)->handle($host, $question);

            $this->prepareStory($story, $scenario);
            $this->seedPromptPhotos($story, $owner, $scenario);
            $this->seedLink($story, $scenario);

            if (self::BLOCK_07[$scenario]['family'] ?? false) {
                $this->seedPairedFamilyLink($host, $owner, $scenario);
            }
        }

        foreach (self::SPACE_NARRATORS as $scenario => $phone) {
            $this->seedNarratorSpace($owner, $scenario, $phone);
        }

        foreach (self::FAMILY_LINKS as $scenario) {
            $this->seedFamilyLink($owner, $scenario);
        }

        // Un lien par test, comme partout : le second scénario consomme le
        // sien, et un lien partagé ferait échouer les voisins.
        foreach (self::ONE_TAP_LINKS as $scenario) {
            $this->seedOneTapLink($owner, $scenario);
        }

        foreach (self::INVITATION_LINKS as $scenario) {
            $this->seedInvitationLink($owner, $scenario);
        }
    }

    /**
     * Une question du décor, hors corpus.
     *
     * Elle n'existe que pour porter l'histoire d'un scénario. Active, elle
     * rejoindrait le corpus de **toutes** les familles : la page « Les
     * questions » de Camille s'ouvrait sur vingt-six questions de test, dont
     * six fois la même, et le moteur aurait pu en poser une pour de vrai.
     */
    private static function question(string $slug, string $text): Question
    {
        return Question::query()->firstOrCreate(
            ['slug' => $slug],
            ['text' => $text, 'theme' => QuestionTheme::Childhood, 'is_active' => false],
        );
    }

    /**
     * Le second facteur du compte d'administration, à valeur connue.
     */
    private function seedAdminSecondFactor(): void
    {
        $admin = User::query()
            ->where('email', (string) config('product.seeding.admin_email'))
            ->first();

        $admin?->saveAppAuthenticationSecret(self::E2E_TOTP_SECRET);
    }

    /**
     * Un cadeau en attente de réponse (bloc 10).
     *
     * Le narrateur est joignable par courriel : la suite n'a pas de téléphone,
     * et le canal ne change rien à ce que la page d'opt-in demande.
     */
    private function seedInvitationLink(User $owner, string $scenario): void
    {
        $project = app(CreateProject::class)->handle($owner, Offer::Pilot, []);
        $project->status = ProjectStatus::AwaitingAcceptance;
        $project->gift_message = 'J’aimerais garder tes histoires, maman.';
        $project->gift_sent_at = now()->subHours(2);
        $project->save();

        $narrator = app(AddNarrator::class)->handle($project, [
            'first_name' => 'Odette',
            'display_name' => 'Odette',
            'email' => "odette+{$scenario}@example.test",
            'preferred_channel' => Channel::Email,
            'birth_year' => 1943,
        ]);

        // Le narrateur n'a pas encore accepté : `AddNarrator` pose
        // `opted_in_at`, et un décor qui le garderait ferait passer la page
        // pour déjà répondue.
        $narrator->forceFill(['opted_in_at' => null])->save();

        $token = new AccessToken([
            'type' => TokenType::Invitation,
            'scope' => ['opt_in'],
            'expires_at' => now()->addDays(30),
            'single_use' => TokenType::Invitation->isSingleUse(),
        ]);

        $token->token_hash = TokenService::hash(self::token($scenario));
        $token->subject()->associate($project->refresh());
        $token->issuedTo()->associate($narrator);
        $token->save();

        $invitation = new Invitation([
            'channel' => Channel::Email,
            'attempt' => 1,
            'sent_at' => now()->subHours(2),
        ]);

        $invitation->project()->associate($project);
        $invitation->narrator()->associate($narrator);
        $invitation->token()->associate($token);
        $invitation->save();
    }

    /**
     * L'espace de l'Initiateur·rice, avec de quoi montrer la garde de
     * visibilité : une histoire partagée qui porte son titre, une histoire
     * transcrite qui ne le porte pas. Et sa commande, pour que la page
     * « Ma commande » ait quelque chose à rétracter.
     */
    private function seedInitiatorSpace(): void
    {
        $initiator = User::query()->firstOrCreate(
            ['email' => self::INITIATOR_EMAIL],
            [
                'name' => 'Camille',
                'password' => Hash::make((string) config('product.seeding.admin_password')),
                'email_verified_at' => now(),
            ],
        );

        $project = Project::query()->where('owner_user_id', $initiator->id)->first();

        if (! $project instanceof Project) {
            $project = $this->initiatorProject($initiator);
        }

        $this->seedInitiatorOrder($initiator, $project);
    }

    /**
     * La commande de l'Initiateur·rice, payée il y a trois jours.
     *
     * Le point 5 du checkpoint du bloc 10 finit par « demander la
     * rétractation » : sans commande payée dont le délai court encore, la page
     * est vide et l'étape n'existe pas. Trois jours après le paiement, il reste
     * onze jours de délai légal — de quoi jouer le checkpoint sans se presser.
     * Le prix est celui des réglages, comme dans le tunnel : un décor à 49 €
     * quand le produit se vend 89 € raconterait une autre histoire.
     */
    private function seedInitiatorOrder(User $initiator, Project $project): void
    {
        if (Order::query()->where('project_id', $project->id)->exists()) {
            return;
        }

        $price = app(PilotSettings::class)->pilot_price_cents;
        $paidAt = now()->subDays(3);

        $order = Order::factory()
            ->paid()
            ->for($initiator)
            ->for($project)
            ->create([
                'subtotal_cents' => $price,
                'total_cents' => $price,
                'paid_at' => $paidAt,
                'withdrawal_deadline_at' => Order::withdrawalDeadlineFrom($paidAt),
            ]);

        OrderItem::factory()->for($order)->ofSku(Sku::Pilot, $price)->create();
    }

    /**
     * Le projet de Camille : Odette raconte, une question est en cours, une
     * histoire est partagée.
     */
    private function initiatorProject(User $initiator): Project
    {
        /*
         * L'offre **vendue**, pas `Offer::Pilot` en dur.
         *
         * Le décor montrait douze semaines de collecte quel que soit le mode
         * du tunnel : on regardait « semaine 9 sur 12 » sur un produit qui en
         * vend cinquante-deux. Un décor qui ment sur la durée du contrat
         * n'est pas un décor, c'est un piège.
         */
        $project = app(CreateProject::class)->handle(
            $initiator,
            app(PilotSettings::class)->offer(),
            [],
        );
        $project->status = ProjectStatus::Active;
        $project->accepted_at = now()->subDays(60);
        $project->save();

        /*
         * La fenêtre de collecte, comme l'acceptation la poserait.
         *
         * Le décor sautait cette étape : un projet « en cours » sans
         * `collection_started_at`, ce qu'aucun vrai projet n'est. Le tableau
         * de bord ne pouvait donc pas dire « semaine 9 sur 12 » — il n'avait
         * pas de quoi, et c'est précisément ce qu'on vient vérifier à l'œil.
         */
        $project->startCollection(now()->subDays(60));

        // Le prochain envoi est celui que le produit calculerait : le jour et
        // le créneau du projet, pas « dans trois jours à l'heure qu'il est ».
        app(ScheduleNextPrompt::class)->apply($project);

        $this->consentingNarrator($project, [
            'first_name' => 'Odette',
            'display_name' => 'Odette',
            'phone_e164' => '+33600000099',
            'birth_year' => 1943,
        ]);

        app(AddFamilyMember::class)->handle($project->refresh(), $initiator, [
            'display_name' => $initiator->name,
            'email' => $initiator->email,
        ]);

        // Une question en cours : sans elle, « copier le lien de cette
        // semaine » n'a rien à réémettre.
        $current = self::question('e2e-espace-courante', 'Quel était le métier de votre mère ?');

        app(ProposeStory::class)->handle($project, $current);

        // Et une histoire partagée, qui porte son titre.
        $shared = self::question('e2e-espace-partagee', 'Où avez-vous grandi ?');

        $story = app(ProposeStory::class)->handle($project, $shared);
        $this->prepareStory($story, 'withdraw');
        $story->refresh()->forceFill(['title' => 'Le village de mon enfance'])->save();

        return $project->refresh();
    }

    /**
     * Un lien d'action en un tap, à valeur connue (bloc 09).
     *
     * Le scénario de l'alerte J+21 : l'Initiateur·rice touche « passer à une
     * question toutes les deux semaines », et le rythme change.
     */
    private function seedOneTapLink(User $owner, string $scenario): void
    {
        $project = app(CreateProject::class)->handle($owner, Offer::Pilot, []);
        $project->status = ProjectStatus::Active;
        $project->accepted_at = now()->subDays(60);
        $project->cadence = Cadence::Weekly;
        $project->save();

        $this->consentingNarrator($project, [
            'first_name' => 'Odette',
            'display_name' => 'Odette',
            'phone_e164' => '+336000'.self::digits($scenario),
            'birth_year' => 1943,
        ]);

        $token = new AccessToken([
            'type' => TokenType::Action,
            'scope' => OneTapRegistry::scopeFor(SwitchBiweekly::name()),
            'expires_at' => now()->addDays(30),
            // Le décor construit la ligne à la main : sans ce drapeau, le
            // lien resterait rejouable, et le décor ne ressemblerait plus au
            // produit — c'est exactement ce qu'un décor ne doit pas faire.
            'single_use' => TokenType::Action->isSingleUse(),
        ]);

        $token->token_hash = TokenService::hash(self::token($scenario));
        $token->subject()->associate($project->refresh());
        $token->save();
    }

    /**
     * Un projet avec une histoire partagée, transcrite et audible, et un
     * proche dont le lien d'écoute a une valeur connue.
     */
    private function seedFamilyLink(User $owner, string $scenario): void
    {
        $project = app(CreateProject::class)->handle($owner, Offer::Pilot, []);
        $project->status = ProjectStatus::Active;
        $project->accepted_at = now()->subDays(60);
        $project->save();

        $this->consentingNarrator($project, [
            'first_name' => 'Odette',
            'display_name' => 'Odette',
            'phone_e164' => '+3360001'.self::digits($scenario),
            'birth_year' => 1943,
        ]);

        $question = self::question('e2e-'.$scenario, 'Quelle odeur vous ramène à votre enfance ?');

        $story = app(ProposeStory::class)->handle($project->refresh(), $question);
        $recording = Recording::factory()->confirmed()->create(['story_id' => $story->id]);
        $recording->forceFill([
            'derived_mp3_path' => ObjectKeys::recordingDerivative($recording, 'mp3'),
            // La durée annoncée est celle du fichier réellement semé : un
            // lecteur qui afficherait deux minutes sur quarante-cinq secondes
            // d'audio mentirait à qui écoute.
            'duration_seconds' => self::silentMp3Seconds(),
        ])->save();

        // Un vrai objet sur le stockage : sans lui, l'URL présignée mène à un
        // 404 et le lecteur audio ne joue rien.
        app(MediaStorage::class)->put(
            (string) $recording->derived_mp3_path,
            self::silentMp3(),
            'audio/mpeg',
        );

        $story->state->transitionTo(Recorded::class, AnswerType::Audio);
        $story->state->transitionTo(Transcribed::class);
        $this->seedTranscripts($story, $recording);
        $this->share($story);

        $member = app(AddFamilyMember::class)->handle($project, $owner, [
            'display_name' => 'Marie',
            'email' => 'marie-'.$scenario.'@example.test',
            // Un seul scénario porte le droit de contribuer : le bloc 12
            // vérifie aussi l'**absence** du bouton pour les autres, et un
            // décor où tout le monde contribue ne prouverait que la moitié.
            'can_ask' => $scenario === 'listen-photo',
        ]);

        $token = new AccessToken([
            'type' => TokenType::ListenProject,
            'scope' => ['listen', 'react'],
            'expires_at' => now()->addMonths(12),
        ]);

        $token->token_hash = TokenService::hash(self::token($scenario));
        $token->subject()->associate($member);
        $token->issuedTo()->associate($member);
        $token->save();
    }

    /**
     * Un MP3 minuscule et silencieux, décodable par le navigateur.
     *
     * Une seule trame MPEG-1 layer III : assez pour que `loadedmetadata`
     * arrive et que la lecture démarre, ce qui est tout ce que le bout en
     * bout demande.
     */
    /**
     * Un MP3 réellement silencieux, et réellement lisible.
     *
     * La version précédente annonçait 128 kbit/s dans son en-tête — ce qui
     * impose des trames de 417 octets — mais n'en écrivait que 404. Aucun
     * décodeur ne peut lire cela : le navigateur rendait
     * `MEDIA_ERR_SRC_NOT_SUPPORTED`, le compteur du lecteur restait à zéro, et
     * comme la progression d'écoute vient du lecteur, il n'y avait ni seuil de
     * trente secondes franchi ni écoute enregistrée. La moitié du bloc 08
     * devenait injouable (écart T-131).
     *
     * On écrit donc des trames conformes : MPEG-1 Layer III, 32 kbit/s,
     * 44,1 kHz, mono, sans CRC. La taille d'une trame se déduit de son
     * en-tête — `floor(144 × 32000 / 44100)` = 104 octets — et sa durée du
     * nombre d'échantillons d'une trame Layer III, 1152, divisé par la
     * fréquence. Quarante-cinq secondes pèsent ainsi 180 Ko, ce qui laisse
     * franchir le seuil sans alourdir le semis.
     *
     * Fabriqué en PHP et non par `ffmpeg` : le conteneur en a un,
     * l'intégration continue non, et un décor qui ne se sème que sur la
     * machine du développeur n'est pas un décor.
     */
    private const MP3_FRAME_BYTES = 104;

    private const MP3_FRAME_SECONDS = 1152 / 44100;

    /** La durée exacte du fichier semé, en secondes. */
    public static function silentMp3Seconds(int $seconds = 45): string
    {
        $frames = (int) ceil($seconds / self::MP3_FRAME_SECONDS);

        return number_format($frames * self::MP3_FRAME_SECONDS, 2, '.', '');
    }

    public static function silentMp3(int $seconds = 45): string
    {
        $frame = "\xFF\xFB\x10\xC4".str_repeat("\x00", self::MP3_FRAME_BYTES - 4);
        $frames = (int) ceil($seconds / self::MP3_FRAME_SECONDS);

        return str_repeat($frame, $frames);
    }

    /**
     * Un projet dédié, avec sa variante et son narrateur.
     */
    /**
     * Un lien d'écoute sur le projet d'un scénario du bloc 07.
     *
     * Nommé `{scénario}-famille`, pour qu'on lise d'un coup d'œil ce qu'il
     * observe. C'est l'autre moitié de chaque vérification du bloc : la
     * personne décide sur son lien, et la famille voit — ou ne voit plus —
     * sur celui-ci.
     */
    private function seedPairedFamilyLink(Project $project, User $owner, string $scenario): void
    {
        $member = app(AddFamilyMember::class)->handle($project, $owner, [
            'display_name' => 'Marie',
            'email' => "marie-{$scenario}-famille@example.test",
            'can_ask' => false,
        ]);

        $token = new AccessToken([
            'type' => TokenType::ListenProject,
            'scope' => ['listen', 'react'],
            'expires_at' => now()->addMonths(12),
        ]);

        $token->token_hash = TokenService::hash(self::token($scenario.'-famille'));
        $token->subject()->associate($member);
        $token->issuedTo()->associate($member);
        $token->save();
    }

    /**
     * Une narratrice de projet actif, avec les consentements qu'elle aurait.
     *
     * En production, les cinq consentements de l'opt-in sont posés ensemble à
     * l'acceptation : une personne qui reçoit des questions les a forcément.
     * Le décor les créait par `AddNarrator` seul, donc sans aucun — et
     * `RenderFluide` refusant de rendre un texte sans `ai_rendering`, **toute
     * chaîne réellement jouée en local s'arrêtait au mot à mot**, en silence,
     * avec un simple `fluide.skipped_no_consent` dans le journal. La page de
     * relecture affichait « Alors euh je me souviens… » au lieu du texte mis
     * au propre, et la conclusion évidente devant l'écran était « le rendu IA
     * est cassé ».
     *
     * Les consentements passent par `RecordConsent`, comme partout ailleurs :
     * un décor qui insérerait ses lignes à la main ne prouverait pas que le
     * chemin réel fonctionne.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function consentingNarrator(Project $project, array $attributes): Narrator
    {
        $narrator = app(AddNarrator::class)->handle($project, $attributes);

        foreach (AcceptInvitation::CONSENTS as $kind) {
            app(RecordConsent::class)->handle(
                $narrator,
                $project,
                $kind,
                ConsentChannel::Web,
            );
        }

        return $narrator;
    }

    /**
     * Les photos qui **posent** la question (T-251).
     *
     * Déposées par l'Initiateur·rice sur une histoire encore en PROPOSÉE,
     * c'est-à-dire exactement le geste que le tableau de bord permet déjà :
     * `AttachPhoto` y pose `is_prompt`, et la page d'enregistrement les
     * montre. On passe par l'action réelle plutôt que d'écrire en base — une
     * démonstration qui court-circuite le chemin de production finit par
     * montrer un état que le produit ne sait pas produire.
     */
    private function seedPromptPhotos(Story $story, User $owner, string $scenario): void
    {
        $lots = [
            'photo-question' => [['La maison de Saint-Léon, été 1951', 0]],
            'photos-question' => [
                ['Le repas sous le tilleul', 1],
                ['Les cousins, au bord de l’eau', 2],
                [null, 3],
            ],
        ];

        foreach ($lots[$scenario] ?? [] as [$caption, $teinte]) {
            $fichier = $this->fakeJpeg($teinte);

            try {
                app(AttachPhoto::class)->handle($story, $fichier, $owner, $caption);
            } catch (Throwable $exception) {
                // Une démonstration qui n'a pas pu joindre une photo reste
                // une démonstration : le reste du décor vaut mieux que rien.
                $this->command->warn(
                    "Photo de démonstration non jointe ({$scenario}) : ".$exception->getMessage(),
                );
            } finally {
                @unlink($fichier->getPathname());
            }
        }
    }

    /**
     * Une image plausible, fabriquée sur place.
     *
     * Pas de fichier binaire versionné dans le dépôt pour un décor : il
     * grossit le clone pour tout le monde et se périme sans que personne ne
     * le remarque. GD suffit — le produit en dépend déjà, `Sanitizer` ne
     * fonctionne pas sans.
     *
     * Ce n'est pas un aplat de couleur mais une petite scène — ciel, horizon,
     * maison, arbres, teinte ancienne. La raison n'est pas l'esthétique :
     * une vignette unie ne dit pas si le cadrage tient, si la photo se
     * reconnaît à 88 px, ni si l'écran reste lisible avec une vraie image
     * dedans. Un décor qui ne ressemble pas à ce qu'on affichera ne permet
     * de juger de rien.
     */
    private function fakeJpeg(int $graine): UploadedFile
    {
        $largeur = 1200;
        $hauteur = 900;
        $image = imagecreatetruecolor($largeur, $hauteur);

        // Les canaux sont bornés : l'arithmétique des dégradés ci-dessous
        // peut sortir de l'intervalle, et `imagecolorallocate` le refuse.
        $borne = fn (int $c): int => max(0, min(255, $c));
        $teinte = fn (int $r, int $v, int $b): int => (int) imagecolorallocate(
            $image,
            $borne($r),
            $borne($v),
            $borne($b),
        );

        // Un ciel dégradé, du plus clair en haut à l'horizon.
        $horizon = (int) ($hauteur * 0.62);

        for ($y = 0; $y < $horizon; $y++) {
            $t = $y / $horizon;
            imageline($image, 0, $y, $largeur, $y, $teinte(
                (int) (236 - 18 * $t),
                (int) (224 - 22 * $t),
                (int) (201 - 26 * $t),
            ));
        }

        // La terre, un peu plus sourde d'une image à l'autre.
        $sol = [[188, 174, 146], [176, 166, 140], [198, 180, 150], [170, 163, 138]][$graine % 4];

        for ($y = $horizon; $y < $hauteur; $y++) {
            $t = ($y - $horizon) / max(1, $hauteur - $horizon);
            imageline($image, 0, $y, $largeur, $y, $teinte(
                (int) ($sol[0] - 40 * $t),
                (int) ($sol[1] - 42 * $t),
                (int) ($sol[2] - 38 * $t),
            ));
        }

        // Une maison, décalée selon la graine, et son toit.
        $x = (int) ($largeur * (0.24 + 0.12 * ($graine % 3)));
        $mur = $teinte(206, 194, 173);
        $toit = $teinte(122, 74, 56);
        imagefilledrectangle($image, $x, $horizon - 200, $x + 300, $horizon + 40, $mur);
        imagefilledpolygon($image, [$x - 30, $horizon - 200, $x + 150, $horizon - 320, $x + 330, $horizon - 200], $toit);
        imagefilledrectangle($image, $x + 120, $horizon - 90, $x + 180, $horizon + 40, $teinte(96, 78, 62));

        // Deux arbres, pour que l'horizon ne soit pas une ligne nue.
        foreach ([[0.72, 130], [0.86, 96]] as [$ratio, $rayon]) {
            $ax = (int) ($largeur * $ratio);
            imagefilledrectangle($image, $ax - 12, $horizon - 60, $ax + 12, $horizon + 30, $teinte(102, 84, 64));
            imagefilledellipse($image, $ax, $horizon - 110, $rayon, (int) ($rayon * 0.9), $teinte(124, 154, 142));
        }

        // Le grain et le jaunissement d'un tirage ancien.
        imagefilter($image, IMG_FILTER_COLORIZE, 18, 8, -14);

        $chemin = tempnam(sys_get_temp_dir(), 'demo-photo-').'.jpg';
        imagejpeg($image, $chemin, 82);
        imagedestroy($image);

        return new UploadedFile($chemin, 'souvenir.jpg', 'image/jpeg', null, true);
    }

    private function projectForScenario(User $owner, string $scenario): Project
    {
        $project = app(CreateProject::class)->handle($owner, Offer::Pilot, []);
        $project->status = ProjectStatus::Active;
        $project->accepted_at = now()->subDays(60);
        $project->validation_variant = ValidationVariant::from(
            (string) self::BLOCK_07[$scenario]['variant'],
        );

        /*
         * La déclaration n'est posée que sur les scénarios en variante A.
         *
         * Depuis T-250, tout projet accepté la porte — mais la poser aussi
         * sur la variante B viderait son banc d'essai : la déclaration passe
         * **avant** la variante dans `ApplyShareDecision`, et plus aucune
         * relecture ne serait demandée. Les scénarios `variant-b` existent
         * pour éprouver ce chemin-là ; ils gardent donc un projet d'avant.
         */
        if ($project->validation_variant === ValidationVariant::Immediate) {
            $project->declared_sharing_at = now()->subDays(60);
        }

        $project->save();

        $this->consentingNarrator($project, [
            'first_name' => 'Odette',
            'display_name' => 'Odette',
            'phone_e164' => '+3360000'.self::digits($scenario),
            'birth_year' => 1943,
        ]);

        return $project->refresh();
    }

    /**
     * Amène l'histoire à l'état que le scénario attend, transcriptions
     * comprises. On passe par les transitions, jamais par une écriture
     * directe de `state` : le test doit rencontrer le produit, pas un décor
     * qui lui ressemble.
     */
    private function prepareStory(Story $story, string $scenario): void
    {
        $target = self::BLOCK_07[$scenario]['reach'] ?? null;

        if ($target === null) {
            return;
        }

        $recording = $this->seedRecording($story);

        if (self::BLOCK_07[$scenario]['audio'] ?? false) {
            $recording->forceFill([
                'derived_mp3_path' => ObjectKeys::recordingDerivative($recording, 'mp3'),
            ])->save();

            // Le dérivé aussi : c'est lui que la page famille sert.
            app(MediaStorage::class)->put(
                (string) $recording->derived_mp3_path,
                self::silentMp3(),
                'audio/mpeg',
            );
        }

        $story->state->transitionTo(Recorded::class, AnswerType::Audio);
        $story->state->transitionTo(Transcribed::class);

        if (self::BLOCK_07[$scenario]['transcripts'] ?? false) {
            $this->seedTranscripts($story, $recording);
        }

        // Sans `default` : si un scénario réclame un état non traité,
        // l'analyse statique le dit, et à défaut le semis échoue tout de
        // suite plutôt que de bâtir un décor incomplet en silence.
        match ($target) {
            'to_review' => $story->state->transitionTo(ToReview::class),
            'shared' => $this->share($story),
        };
    }

    private function share(Story $story): void
    {
        $story->share_decision = ShareDecision::Share;
        $story->share_decided_at = now();
        $story->save();

        app(ValidateStoryAction::class)->handle($story, ValidatedVia::RecordingEnd);
        $story->state->transitionTo(Shared::class);
    }

    private function seedTranscripts(Story $story, Recording $recording): void
    {
        foreach ([
            [TranscriptKind::Verbatim, 'gladia', 'Alors euh je me souviens de l’odeur du pain, voilà quoi, chez ma grand-mère.'],
            [TranscriptKind::Fluide, 'claude', 'Je me souviens de l’odeur du pain chez ma grand-mère.'],
        ] as [$kind, $provider, $text]) {
            $transcript = new Transcript([
                'kind' => $kind,
                'version' => 1,
                'provider' => $provider,
                'language' => 'fr',
                'text' => $text,
            ]);

            $transcript->story()->associate($story);
            $transcript->recording()->associate($recording);
            $transcript->save();
        }

        $story->title = 'L’odeur du pain';
        $story->save();
    }

    /**
     * L'espace narrateur : une coordonnée connue, un défi en attente dont le
     * code est connu, et un lien direct.
     *
     * Le code est **semé**, pas exposé par une route de test : une route qui
     * révèle des codes à usage unique est exactement le genre d'affordance
     * qui finit activée quelque part (décision T-78).
     */
    private function seedNarratorSpace(User $owner, string $scenario, string $phone): void
    {
        $project = app(CreateProject::class)->handle($owner, Offer::Pilot, []);
        $project->status = ProjectStatus::Active;
        $project->accepted_at = now()->subDays(60);
        $project->save();

        $narrator = $this->consentingNarrator($project, [
            'first_name' => 'Odette',
            'display_name' => 'Odette',
            'phone_e164' => $phone,
            'birth_year' => 1943,
        ]);

        $question = self::question('e2e-'.$scenario, 'Quel métier rêviez-vous de faire ?');

        $story = app(ProposeStory::class)->handle($project->refresh(), $question);
        $this->seedRecording($story);
        $story->state->transitionTo(Recorded::class, AnswerType::Audio);
        $story->state->transitionTo(Transcribed::class);
        $this->share($story);

        $challengeId = (string) Str::uuid7();

        $challenge = new OtpChallenge([
            'purpose' => OtpPurpose::NarratorSpace,
            'channel' => Channel::Sms,
            'sent_to_masked' => OtpService::mask($phone),
            'expires_at' => now()->addYear(),
        ]);

        $challenge->id = $challengeId;
        $challenge->narrator_id = $narrator->id;
        $challenge->code_hash = OtpService::hashCode(self::SPACE_CODE, $challengeId);
        $challenge->save();

        $token = new AccessToken([
            'type' => TokenType::NarratorSpace,
            'scope' => ['read', 'withdraw'],
            'expires_at' => now()->addDays(30),
        ]);

        $token->token_hash = TokenService::hash(self::token($scenario));
        $token->subject()->associate($narrator);
        $token->save();
    }

    /**
     * La ligne est insérée sans `TokenService::issue()`, qui tire un jeton
     * aléatoire par définition. Même parti que les états de `StoryFactory` :
     * un seeder construit un décor.
     */
    private function seedLink(Story $story, string $scenario): void
    {
        $token = new AccessToken([
            'type' => TokenType::Record,
            'scope' => ['record', 'decide_share'],
            'expires_at' => now()->addDays(30),
        ]);

        $token->token_hash = TokenService::hash(self::token($scenario));
        $token->subject()->associate($story);

        foreach (self::LINK_STATE[$scenario] ?? [] as $column => $offset) {
            $token->{$column} = now()->modify((string) $offset);
        }

        $token->save();
    }

    /** Valeur connue d'un lien, complétée à 43 caractères. */
    /**
     * Quatre chiffres stables tirés du nom d'un scénario.
     *
     * `md5()` était utilisé ici, et ses chiffres hexadécimaux vont jusqu'à
     * `f` : un numéro sur deux portait une lettre. En local le journal
     * l'accepte et le défaut reste invisible ; Twilio le refuse, et ce refus
     * serait tombé au premier envoi réel du bloc 05 — sur le checkpoint qui
     * attend justement de voir un SMS partir.
     */
    private static function digits(string $scenario): string
    {
        return str_pad((string) (crc32($scenario) % 10000), 4, '0', STR_PAD_LEFT);
    }

    /**
     * Pourquoi chaque projet actif du décor porte une date d'acceptation.
     *
     * Un projet `active` que personne n'a accepté n'existe pas dans le
     * produit, et les deux règles de silence du moteur exigent cette date : un
     * narrateur qui n'a jamais accepté relève d'`invitation_not_accepted`, pas
     * d'une relance. Sans elle, ces règles se **taisent sans rien dire**, et
     * il a fallu écrire `demo:moteur` puis le déboguer pour s'en apercevoir
     * (T-153). `DecorConsistencyTest` échoue si un semis l'oublie.
     */
    /**
     * Un enregistrement confirmé **et** son objet sur le stockage.
     *
     * Trois endroits créaient des enregistrements, et un seul téléversait :
     * partout ailleurs le chemin désignait le vide, et le bouton « Écouter »
     * du back-office rendait un `NoSuchKey` qu'on prenait pour un défaut du
     * produit (T-183). Un seul point d'entrée ferme la porte.
     */
    private function seedRecording(Story $story): Recording
    {
        $recording = Recording::factory()->confirmed()->create(['story_id' => $story->id]);
        $recording->forceFill(['duration_seconds' => self::silentMp3Seconds()])->save();

        app(MediaStorage::class)->put(
            (string) $recording->original_path,
            self::silentMp3(),
            'audio/mpeg',
        );

        return $recording;
    }

    public static function token(string $scenario): string
    {
        return str_pad("demo-{$scenario}-link", 43, 'x');
    }

    /**
     * Le sujet que porte le lien d'un scénario.
     *
     * La recherche vit ici et non chez l'appelant : `token_hash` ne se lit que
     * dans le service de jetons et les trois fichiers qui le masquent — une
     * garde le vérifie, et elle a raison, puisqu'une empreinte calculée un peu
     * partout est la façon dont un jeton finit par fuir. Ce semis possède déjà
     * la correspondance entre un scénario et son lien ; il est donc le bon
     * endroit pour rendre ce que ce lien désigne.
     *
     * Rend `null` quand la base n'est pas semée, ou pas migrée : la feuille
     * des vérifications doit rester imprimable, c'est précisément l'état où
     * l'on en a le plus besoin.
     */
    public static function subjectOf(string $scenario): ?Model
    {
        try {
            return AccessToken::query()
                ->where('token_hash', TokenService::hash(self::token($scenario)))
                ->first()
                ?->subject;
        } catch (QueryException) {
            return null;
        }
    }
}
