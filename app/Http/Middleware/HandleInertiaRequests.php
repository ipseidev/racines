<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Analytics\Measured;
use App\Settings\PilotSettings;
use App\Support\Brand;
use App\Support\Translations;
use Illuminate\Http\Request;
use Inertia\Middleware;

final class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'brand' => Brand::toInertia(),
            // Le mode et les prix sont partagés parce qu'ils décident de ce
            // que **plusieurs** pages annoncent : l'accueil, le tunnel, le
            // pied de page. Les passer page par page finirait par produire
            // deux prix différents sur deux écrans du même parcours.
            'pilot' => self::pilot(),
            'i18n' => Translations::forRequest($request),
            'locale' => app()->getLocale(),
            // Messages d'une action réussie. Les pages narrateur et famille
            // n'ont pas de barre de notifications : elles affichent ce
            // message à l'endroit où l'action a été demandée.
            'flash' => [
                'status' => $request->session()->get('status'),
            ],
            'auth' => [
                'user' => $request->user(),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',

            /*
             * La clé de mesure d'audience, **et seulement hors des pages à
             * jeton** (bloc 15).
             *
             * Elle est retirée de la réponse plutôt que laissée au front avec
             * la consigne de ne pas s'en servir : une clé absente ne peut pas
             * être utilisée par erreur, et la page d'un narrateur ne porte
             * alors littéralement rien qui permette de le mesurer.
             */
            'analytics' => self::analytics($request),

            /*
             * L'identifiant de mesure du site marchand, sous la même règle et
             * pour la même raison (bloc 15).
             *
             * Deux props plutôt qu'une : les deux mesures ne s'allument pas
             * ensemble. `ANALYTICS_DRIVER=log` en local avec Google Analytics
             * en production est le cas courant, et une prop unique forcerait
             * le front à démêler deux absences différentes.
             */
            'googleAnalytics' => self::googleAnalytics($request),
        ];
    }

    /**
     * De quoi démarrer la mesure d'audience, ou rien.
     *
     * Les pages à jeton reçoivent `null` : un narrateur n'a pas de compte,
     * n'a rien accepté, et ne sait pas ce qu'est un traceur. `Measured` porte
     * la liste des espaces concernés — un espace ajouté sans y être inscrit
     * serait mesuré, ce qu'un test interdit.
     *
     * @return array{key: string, host: string}|null
     */
    private static function analytics(Request $request): ?array
    {
        $key = (string) config('services.posthog.key');

        if ($key === '' || (string) config('services.posthog.driver') !== 'posthog') {
            return null;
        }

        if (! Measured::allows($request)) {
            return null;
        }

        return ['key' => $key, 'host' => (string) config('services.posthog.host')];
    }

    /**
     * De quoi démarrer Google Analytics, ou rien.
     *
     * La même garde que ci-dessus, littéralement la même fonction : la page
     * d'un narrateur ne porte aucun identifiant de mesure, d'aucun
     * fournisseur. Et le même verrou qu'ailleurs (T-61) : `GA_ENABLED`
     * décide, jamais la présence de l'identifiant — sinon les visites d'un
     * décor de développement partiraient dans la propriété du site.
     *
     * @return array{measurementId: string}|null
     */
    private static function googleAnalytics(Request $request): ?array
    {
        $id = (string) config('services.google_analytics.measurement_id');

        if ($id === '' || config('services.google_analytics.enabled') !== true) {
            return null;
        }

        if (! Measured::allows($request)) {
            return null;
        }

        return ['measurementId' => $id];
    }

    /**
     * Les réglages du pilote visibles du public.
     *
     * Les prix sont en centimes, comme en base : la mise en forme est un
     * problème d'affichage, et le front la fait avec la locale du visiteur.
     *
     * @return array<string, mixed>
     */
    private static function pilot(): array
    {
        $pilot = app(PilotSettings::class);

        return [
            'mode' => $pilot->mode,
            'pilotPriceCents' => $pilot->pilot_price_cents,
            'extraCopyPriceCents' => $pilot->extra_copy_price_cents,
            'phoneOptionPriceCents' => $pilot->phone_option_price_cents,
            'legalValidated' => $pilot->legalValidated(),
        ];
    }
}
