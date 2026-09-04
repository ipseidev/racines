<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Http\Middleware\SecurityHeaders;
use App\Support\Brand;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

final class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            /*
             * Double authentification **obligatoire** (doc 04 §12), et pas
             * « recommandée ». La raison est dans ce que le back-office
             * donne : la voix et les récits intimes de familles entières. Un
             * mot de passe support qui fuit, c'est la fuite de tout.
             *
             * Une application TOTP et des codes de récupération, jamais de
             * SMS : un second facteur qui passe par le réseau téléphonique
             * n'en est pas un — et ce produit sait déjà que le smishing est le
             * risque n°1 de son public.
             *
             * Tant qu'elle n'est pas configurée, aucune page ne s'ouvre :
             * Filament pose lui-même ce contrôle sur chaque page du panneau
             * quand `isRequired` est vrai, après `Authenticate` et donc après
             * le contrôle de permission de `canAccessPanel()`, et il en exempte
             * la page de configuration. On ne demande pas à un client de
             * configurer une application d'authentification pour un panneau
             * qu'il n'ouvrira jamais. Ne pas redoubler ce contrôle dans
             * `authMiddleware` : appliqué à toutes les routes authentifiées, il
             * renvoyait la page de configuration vers elle-même, sans fin, et
             * le premier compte de production n'entrait pas (T-146).
             */
            ->multiFactorAuthentication(
                // Pas de `brandName()` : le fournisseur retombe sur celui du
                // panneau, qui est déjà résolu paresseusement depuis les
                // réglages. Le passer ici forcerait une lecture de la base à
                // chaque construction du panneau, et n'accepte pas de
                // fermeture.
                AppAuthentication::make()->recoverable(),
                isRequired: true,
            )
            ->brandName(fn (): string => Brand::nameSafe())
            ->colors(fn (): array => ['primary' => Color::hex(Brand::primaryColorSafe())])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                // Le panneau n'emprunte pas le groupe « web » : il faut lui
                // donner les en-têtes de sécurité explicitement. La politique
                // de contenu qu'il reçoit est assouplie, Alpine l'exige.
                SecurityHeaders::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
