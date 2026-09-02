<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\FamilyMember;
use App\Models\Narrator;
use App\Models\Project;
use App\Models\Story;
use App\Models\User;
use App\Support\Brand;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

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
