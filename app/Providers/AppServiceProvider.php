<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\FamilyMember;
use App\Models\Narrator;
use App\Models\Project;
use App\Models\Story;
use App\Models\User;
use App\Services\Llm\AnthropicMessages;
use App\Services\Llm\ClaudeStoryRenderer;
use App\Services\Llm\FakeStoryRenderer;
use App\Services\Llm\SdkAnthropicMessages;
use App\Services\Llm\StoryRenderer;
use App\Services\Sms\FakeSmsSender;
use App\Services\Sms\LogSmsSender;
use App\Services\Sms\SmsSender;
use App\Services\Sms\TwilioSmsSender;
use App\Services\Storage\FakeMediaStorage;
use App\Services\Storage\MediaStorage;
use App\Services\Storage\S3MediaStorage;
use App\Services\Transcription\DeepgramProvider;
use App\Services\Transcription\FakeTranscriptionProvider;
use App\Services\Transcription\GladiaProvider;
use App\Services\Transcription\TranscriptionProvider;
use App\Support\Brand;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Laravel\Pennant\Feature;
use RuntimeException;
use Twilio\Rest\Client as TwilioClient;

final class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // La vue racine d'Inertia porte la marque : variables CSS, titre,
        // favicon. Éditables dans l'administration, appliquées sans build.
        View::composer('app', function (ViewContract $view): void {
            $view->with([
                'brandCss' => Brand::cssVariables(),
                'brandName' => Brand::nameSafe(),
                'brandFavicon' => Brand::faviconUrl(),
            ]);
        });

        $this->configureDefaults();

        /*
         * Les écouteurs de `app/Listeners` sont découverts par Laravel d'après
         * le type de leur paramètre : les enregistrer ici en plus les faisait
         * tourner **deux fois**, ce qui émettait deux jetons pour une seule
         * demande de nouveau lien.
         *
         * Écouteurs actifs et leur événement :
         *  - RevokeRecordTokensOnValidation      ← Spatie StateChanged
         *  - SendNewLinkRequestedAlerts          ← App\Events\NewLinkRequested
         *  - ApplyShareDecisionOnTranscriptionReady ← App\Events\TranscriptionReady
         */

        // Les drapeaux de `app/Features` sont découverts par leur classe :
        // `Feature::for($project)->value(ValidationVariant::class)` suffit,
        // sans définition à recopier ici.
        Feature::discover();

        $this->configureRateLimiters();
        $this->configureSmsSender();
        $this->configureMediaStorage();
        $this->configureTranscription();
        $this->configureStoryRenderer();
    }

    /**
     * Limiteurs des routes par jeton et des codes à usage unique.
     *
     * Les clés ne contiennent jamais un jeton en clair : un magasin de cache
     * n'est pas un endroit où l'on dépose des liens porteurs. On y met leur
     * empreinte.
     */
    private function configureRateLimiters(): void
    {
        /*
         * Deux bornes, deux rôles. Celle par **jeton** protège du balayage :
         * vingt requêtes par minute suffisent à une personne qui lit, et pas
         * à un script qui essaie des liens. Celle par **IP** protège
         * l'infrastructure, et c'est celle qui punit le partage de connexion
         * — une maison de retraite, une famille derrière un routeur. Elle est
         * donc réglable, et desserrée hors production, où la suite bout en
         * bout concentre trente navigateurs sur une seule adresse (T-79).
         */
        RateLimiter::for('tokens', fn (Request $request): array => [
            Limit::perMinute(self::tokensPerIp())->by('ip:'.$request->ip()),
            Limit::perMinute(
                (int) config('product.security.rate_limits.tokens_per_token'),
            )->by('token:'.self::tokenFingerprint($request)),
        ]);

        RateLimiter::for('otp-request', fn (Request $request): Limit => Limit::perHour(
            (int) config('product.otp.max_challenges_per_hour', 3),
        )->by('otp-request:'.self::tokenFingerprint($request)));

        RateLimiter::for('otp-verify', fn (Request $request): Limit => Limit::perMinute(10)
            ->by('otp-verify:'.self::tokenFingerprint($request)));

        // Une demande de nouveau lien par heure : au-delà, c'est du bruit pour
        // le support et une gêne pour l'Initiateur·rice.
        RateLimiter::for('new-link', fn (Request $request): Limit => Limit::perHour(1)
            ->by('new-link:'.self::tokenFingerprint($request)));

        /*
         * Entrée de l'espace narrateur (bloc 07). Le limiteur des codes ne
         * convient pas ici : sans jeton dans l'URL, il retomberait sur l'IP
         * seule, et trois demandes par heure et par IP enfermeraient dehors
         * toute une maison de retraite dès le deuxième résident.
         *
         * On borne donc d'abord sur la **coordonnée demandée**, qui est le
         * vrai sujet — trois codes par heure pour un même numéro — puis, plus
         * largement, sur l'IP, pour qu'une rotation d'identifiants ne serve
         * pas d'annuaire.
         */
        RateLimiter::for('space-access', fn (Request $request): array => [
            Limit::perHour(3)->by('space-access:'.self::identifierFingerprint($request)),
            Limit::perHour(20)->by('space-access-ip:'.$request->ip()),
        ]);

        RateLimiter::for('space-verify', fn (Request $request): array => [
            Limit::perMinute(10)->by('space-verify:'.self::identifierFingerprint($request)),
            Limit::perMinute(30)->by('space-verify-ip:'.$request->ip()),
        ]);

        // Les événements du navigateur sont nombreux par nature : une séance
        // agitée en produit plusieurs dizaines. Ce limiteur remplace
        // `tokens` sur cette seule route — les 20 requêtes/minute/jeton qui
        // protègent les pages y seraient trop strictes — et reprend une borne
        // par IP pour ne pas ouvrir un dépotoir.
        RateLimiter::for('client-events', fn (Request $request): array => [
            Limit::perMinute(240)->by('ip:'.$request->ip()),
            Limit::perMinute(120)->by('client-events:'.self::tokenFingerprint($request)),
        ]);
    }

    /**
     * La borne par IP telle qu'elle s'applique ici.
     *
     * Hors production, le facteur dix : l'adresse est partagée par tout ce
     * qui tourne sur la machine, et une borne qui coupe la suite de tests ne
     * mesure rien d'utile. La borne par jeton, elle, reste identique
     * partout : c'est elle qui protège réellement les liens.
     */
    private static function tokensPerIp(): int
    {
        $configured = (int) config('product.security.rate_limits.tokens_per_ip');

        return app()->isProduction() ? $configured : $configured * 10;
    }

    /**
     * Empreinte de la coordonnée saisie : on borne sur le sujet demandé, sans
     * déposer un numéro de téléphone dans un magasin de cache.
     */
    private static function identifierFingerprint(Request $request): string
    {
        $identifier = $request->input('identifier');

        return hash('sha256', is_string($identifier) ? mb_strtolower(trim($identifier)) : '');
    }

    private static function tokenFingerprint(Request $request): string
    {
        $token = $request->route('token');

        return is_string($token) ? hash('sha256', $token) : 'anonymous:'.$request->ip();
    }

    /**
     * Transcription : Gladia par défaut (hébergement UE, T-07), Deepgram en
     * second adaptateur et point de comparaison du banc d'essai, `fake` dans
     * la suite de tests. Un fournisseur inconnu lève.
     */
    private function configureTranscription(): void
    {
        $this->app->singleton(TranscriptionProvider::class, function (): TranscriptionProvider {
            $provider = (string) config('services.asr.provider');

            return match ($provider) {
                'fake' => new FakeTranscriptionProvider,
                'gladia' => new GladiaProvider($this->app->make(HttpFactory::class), (string) config('services.asr.gladia_key')),
                'deepgram' => new DeepgramProvider($this->app->make(HttpFactory::class), (string) config('services.asr.deepgram_key')),
                default => throw new RuntimeException("Unknown ASR provider [{$provider}]."),
            };
        });
    }

    /**
     * Mise au propre : Claude, ou une version simulée dans les tests.
     */
    private function configureStoryRenderer(): void
    {
        $this->app->singleton(AnthropicMessages::class, SdkAnthropicMessages::class);

        $this->app->singleton(StoryRenderer::class, function (): StoryRenderer {
            $provider = (string) config('services.anthropic.provider');

            return match ($provider) {
                'fake' => new FakeStoryRenderer,
                'claude' => new ClaudeStoryRenderer($this->app->make(AnthropicMessages::class)),
                default => throw new RuntimeException("Unknown LLM provider [{$provider}]."),
            };
        });
    }

    /**
     * Stockage des médias : R2 en local comme en production (MinIO émule R2),
     * en mémoire dans la suite de tests, qui n'appelle jamais le réseau.
     *
     * Le pilote est lu dans la configuration et non déduit de
     * l'environnement : en intégration continue, l'application servie au bout
     * en bout tourne avec `APP_ENV=testing`, et une liaison fondée sur
     * `runningUnitTests()` lui donnait un stockage en mémoire.
     */
    private function configureMediaStorage(): void
    {
        $this->app->singleton(MediaStorage::class, function (): MediaStorage {
            $driver = (string) config('services.media.driver');

            return match ($driver) {
                'fake' => new FakeMediaStorage,
                's3' => new S3MediaStorage,
                default => throw new RuntimeException("Unknown media storage driver [{$driver}]."),
            };
        });
    }

    /**
     * `fake` en test, `log` en local, Twilio au bloc 05.
     *
     * Un fournisseur inconnu lève : un repli silencieux vers le journal
     * enverrait les codes dans `storage/logs` au lieu de les envoyer aux
     * narrateurs, sans que personne s'en aperçoive.
     */
    private function configureSmsSender(): void
    {
        $this->app->singleton(SmsSender::class, function (): SmsSender {
            $provider = (string) config('services.sms.provider');

            return match ($provider) {
                'fake' => new FakeSmsSender,
                'log' => new LogSmsSender,
                'twilio' => new TwilioSmsSender(
                    new TwilioClient(
                        (string) config('services.twilio.sid'),
                        (string) config('services.twilio.token'),
                    ),
                    route('webhooks.twilio.status'),
                ),
                default => throw new RuntimeException("Unknown SMS provider [{$provider}]."),
            };
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        // Les relations polymorphes stockent un alias court et stable plutôt
        // qu'un nom de classe : renommer une classe ne réécrit pas la base.
        Relation::morphMap([
            'user' => User::class,
            'project' => Project::class,
            'narrator' => Narrator::class,
            'family_member' => FamilyMember::class,
            'story' => Story::class,
        ]);

        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
