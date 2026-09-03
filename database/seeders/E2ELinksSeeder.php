<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Actions\AddFamilyMember;
use App\Actions\AddNarrator;
use App\Actions\CreateProject;
use App\Actions\ProposeStory;
use App\Actions\ValidateStoryAction;
use App\Engine\Actions\OneTapRegistry;
use App\Engine\Actions\SwitchBiweekly;
use App\Enums\AnswerType;
use App\Enums\Cadence;
use App\Enums\Channel;
use App\Enums\Offer;
use App\Enums\OtpPurpose;
use App\Enums\ProjectStatus;
use App\Enums\QuestionTheme;
use App\Enums\ShareDecision;
use App\Enums\TokenType;
use App\Enums\TranscriptKind;
use App\Enums\ValidatedVia;
use App\Enums\ValidationVariant;
use App\Models\AccessToken;
use App\Models\Invitation;
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
use App\States\Story\Recorded;
use App\States\Story\Shared;
use App\States\Story\ToReview;
use App\States\Story\Transcribed;
use App\Support\ObjectKeys;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

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
    private const OWNER_EMAIL = 'liens@example.test';

    /** @var array<string, string> */
    private const SCENARIOS = [
        'record' => 'Racontez-nous votre premier jour d’école.',
        'guard' => 'Quel objet avez-vous gardé de votre enfance ?',
        'resume' => 'Quel était le métier de votre père ?',
        'denied' => 'Quelle chanson vous rappelle votre jeunesse ?',
        'a11y' => 'Comment était la cuisine de votre enfance ?',
        'budget' => 'Quel voyage vous a le plus marqué ?',
        'expired' => 'Quelle est votre plus belle rencontre ?',
        'revoked' => 'Qu’aimeriez-vous que l’on retienne de vous ?',
        // Bloc 07 : un scénario par variante de validation, plus un retrait.
        'variant-a' => 'Quel jeu aimiez-vous enfant ?',
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

        $owner = User::query()->firstOrCreate(
            ['email' => self::OWNER_EMAIL],
            [
                'name' => 'Banc d’essai',
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
        $project->save();

        app(AddNarrator::class)->handle($project, [
            'first_name' => 'Odette',
            'display_name' => 'Odette',
            'phone_e164' => '+33600000001',
            'birth_year' => 1943,
        ]);

        $project->refresh();

        foreach (self::SCENARIOS as $scenario => $text) {
            $question = Question::query()->firstOrCreate(
                ['slug' => 'e2e-'.$scenario],
                ['text' => $text, 'theme' => QuestionTheme::Childhood],
            );

            // Les scénarios du bloc 07 vivent chacun dans **leur** projet :
            // la variante de validation est un réglage de projet, et deux
            // variantes ne cohabitent pas.
            $host = isset(self::BLOCK_07[$scenario])
                ? $this->projectForScenario($owner, $scenario)
                : $project;

            $story = app(ProposeStory::class)->handle($host, $question);

            $this->prepareStory($story, $scenario);
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

        $this->seedInitiatorSpace();
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
     * transcrite qui ne le porte pas.
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

        if (Project::query()->where('owner_user_id', $initiator->id)->exists()) {
            return;
        }

        $project = app(CreateProject::class)->handle($initiator, Offer::Pilot, []);
        $project->status = ProjectStatus::Active;
        $project->next_prompt_at = now()->addDays(3);
        $project->save();

        app(AddNarrator::class)->handle($project, [
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
        $current = Question::query()->firstOrCreate(
            ['slug' => 'e2e-espace-courante'],
            ['text' => 'Quel était le métier de votre mère ?', 'theme' => QuestionTheme::Childhood],
        );

        app(ProposeStory::class)->handle($project, $current);

        // Et une histoire partagée, qui porte son titre.
        $shared = Question::query()->firstOrCreate(
            ['slug' => 'e2e-espace-partagee'],
            ['text' => 'Où avez-vous grandi ?', 'theme' => QuestionTheme::Childhood],
        );

        $story = app(ProposeStory::class)->handle($project, $shared);
        $this->prepareStory($story, 'withdraw');
        $story->refresh()->forceFill(['title' => 'Le village de mon enfance'])->save();
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
        $project->cadence = Cadence::Weekly;
        $project->save();

        app(AddNarrator::class)->handle($project, [
            'first_name' => 'Odette',
            'display_name' => 'Odette',
            'phone_e164' => '+336000'.substr(md5($scenario), 0, 4),
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
        $project->save();

        app(AddNarrator::class)->handle($project, [
            'first_name' => 'Odette',
            'display_name' => 'Odette',
            'phone_e164' => '+3360001'.substr(md5($scenario), 0, 4),
            'birth_year' => 1943,
        ]);

        $question = Question::query()->firstOrCreate(
            ['slug' => 'e2e-'.$scenario],
            ['text' => 'Quelle odeur vous ramène à votre enfance ?', 'theme' => QuestionTheme::Childhood],
        );

        $story = app(ProposeStory::class)->handle($project->refresh(), $question);
        $recording = Recording::factory()->confirmed()->create(['story_id' => $story->id]);
        $recording->forceFill([
            'derived_mp3_path' => ObjectKeys::recordingDerivative($recording, 'mp3'),
            'duration_seconds' => '124.00',
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
            'can_contribute' => $scenario === 'listen-photo',
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
    private static function silentMp3(): string
    {
        $frame = "\xFF\xFB\x90\x00".str_repeat("\x00", 400);

        return str_repeat($frame, 40);
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
            'can_contribute' => false,
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

    private function projectForScenario(User $owner, string $scenario): Project
    {
        $project = app(CreateProject::class)->handle($owner, Offer::Pilot, []);
        $project->status = ProjectStatus::Active;
        $project->validation_variant = ValidationVariant::from(
            (string) self::BLOCK_07[$scenario]['variant'],
        );
        $project->save();

        app(AddNarrator::class)->handle($project, [
            'first_name' => 'Odette',
            'display_name' => 'Odette',
            'phone_e164' => '+3360000'.substr(md5($scenario), 0, 4),
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

        $recording = Recording::factory()->confirmed()->create(['story_id' => $story->id]);
        $recording->forceFill(['duration_seconds' => '124.00'])->save();

        if (self::BLOCK_07[$scenario]['audio'] ?? false) {
            $recording->forceFill([
                'derived_mp3_path' => ObjectKeys::recordingDerivative($recording, 'mp3'),
            ])->save();

            // Un vrai objet sur le stockage : sans lui, l'URL présignée mène
            // à un 404 et le lecteur de la page famille ne joue rien.
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
        $project->save();

        $narrator = app(AddNarrator::class)->handle($project, [
            'first_name' => 'Odette',
            'display_name' => 'Odette',
            'phone_e164' => $phone,
            'birth_year' => 1943,
        ]);

        $question = Question::query()->firstOrCreate(
            ['slug' => 'e2e-'.$scenario],
            ['text' => 'Quel métier rêviez-vous de faire ?', 'theme' => QuestionTheme::Childhood],
        );

        $story = app(ProposeStory::class)->handle($project->refresh(), $question);
        Recording::factory()->confirmed()->create(['story_id' => $story->id]);
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
    public static function token(string $scenario): string
    {
        return str_pad("demo-{$scenario}-link", 43, 'x');
    }
}
