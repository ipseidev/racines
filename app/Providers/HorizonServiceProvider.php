<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\HorizonApplicationServiceProvider;

final class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    /**
     * Qui peut voir les files.
     *
     * Le personnel, et lui seul : la file porte des identifiants d'histoires
     * et de projets, et les noms de jobs disent ce qui se passe chez une
     * famille. Aucune liste d'adresses en dur — la permission `admin.access`
     * est déjà la porte du back-office (doc 04 §12).
     */
    protected function gate(): void
    {
        Gate::define('viewHorizon', fn (?User $user): bool => $user?->can('admin.access') ?? false);
    }
}
