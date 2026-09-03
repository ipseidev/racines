<?php

declare(strict_types=1);

namespace App\Http\Middleware;

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
        ];
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
