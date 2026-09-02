<?php

declare(strict_types=1);

namespace App\Providers;

use App\Listeners\RevokeRecordTokensOnValidation;
use App\Models\FamilyMember;
use App\Models\Narrator;
use App\Models\Project;
use App\Models\Story;
use App\Models\User;
use App\Services\Sms\FakeSmsSender;
use App\Services\Sms\LogSmsSender;
use App\Services\Sms\SmsSender;
use App\Support\Brand;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use RuntimeException;
use Spatie\ModelStates\Events\StateChanged;

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

        // Un jeton d'enregistrement meurt avec la validation de son histoire.
        Event::listen(StateChanged::class, RevokeRecordTokensOnValidation::class);

        $this->configureRateLimiters();
        $this->configureSmsSender();
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
        RateLimiter::for('tokens', fn (Request $request): array => [
            Limit::perMinute(60)->by('ip:'.$request->ip()),
            Limit::perMinute(20)->by('token:'.self::tokenFingerprint($request)),
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
    }

    private static function tokenFingerprint(Request $request): string
    {
        $token = $request->route('token');

        return is_string($token) ? hash('sha256', $token) : 'anonymous:'.$request->ip();
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
