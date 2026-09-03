<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\StoresDatesWithOffset;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthenticationRecovery;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Cashier\Billable;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property UserRole $role
 * @property string $locale
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
final class User extends Authenticatable implements FilamentUser, HasAppAuthentication, HasAppAuthenticationRecovery, MustVerifyEmail, PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use Billable, HasFactory, HasRoles, Notifiable, PasskeyAuthenticatable, StoresDatesWithOffset, TwoFactorAuthenticatable;

    /**
     * Seul le personnel accède au back-office (doc 04 §12).
     */
    public function isStaff(): bool
    {
        return $this->role->isStaff();
    }

    /**
     * L'accès au panneau se décide sur une permission, pas sur un nom de rôle :
     * retirer `admin.access` à un compte suffit à le sortir du back-office.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->can('admin.access');
    }

    /*
    |--------------------------------------------------------------------------
    | Double authentification du back-office
    |--------------------------------------------------------------------------
    |
    | Filament et Fortify savent l'un et l'autre stocker un secret TOTP. On
    | n'en garde **qu'un**, sur les colonnes de Fortify, et Filament vient
    | lire là. Deux magasins finiraient par diverger, et le jour où ils
    | divergent quelqu'un reste dehors avec un code valide en main.
    |
    | Le secret est chiffré par le chiffreur de l'application, comme le fait
    | Fortify : une fuite de base ne donne pas les seconds facteurs.
    |
    */

    public function getAppAuthenticationSecret(): ?string
    {
        $secret = $this->two_factor_secret;

        return $secret === null ? null : decrypt($secret);
    }

    public function saveAppAuthenticationSecret(?string $secret): void
    {
        $this->forceFill([
            'two_factor_secret' => $secret === null ? null : encrypt($secret),
            // Confirmé au moment où le secret est enregistré : Filament ne
            // valide un code qu'après l'avoir fait vérifier à l'écran, donc
            // arriver ici signifie que le facteur fonctionne.
            'two_factor_confirmed_at' => $secret === null ? null : now(),
        ])->save();
    }

    public function getAppAuthenticationHolderName(): string
    {
        // Le courriel et non le nom : c'est ce qui s'affiche dans
        // l'application d'authentification, et un « admin » anonyme au milieu
        // de douze comptes ne se retrouve pas.
        return $this->email;
    }

    /**
     * @return array<string>|null
     */
    public function getAppAuthenticationRecoveryCodes(): ?array
    {
        $codes = $this->two_factor_recovery_codes;

        if ($codes === null) {
            return null;
        }

        $decoded = json_decode(decrypt($codes), true);

        return is_array($decoded) ? array_values(array_map(strval(...), $decoded)) : null;
    }

    /**
     * @param  array<string>|null  $codes
     */
    public function saveAppAuthenticationRecoveryCodes(?array $codes): void
    {
        $this->forceFill([
            'two_factor_recovery_codes' => $codes === null
                ? null
                : encrypt((string) json_encode(array_values($codes))),
        ])->save();
    }

    /**
     * Traduit `users.role` en rôle spatie, seule source des permissions fines.
     *
     * Si le rôle n'existe pas encore en base — seeder non joué —, on n'assigne
     * rien : le compte reste dehors. Échouer fermé plutôt qu'ouvert.
     */
    public function syncBackOfficeRole(): void
    {
        if (! $this->role->isStaff()) {
            $this->syncRoles([]);

            return;
        }

        $exists = Role::query()
            ->where('name', $this->role->value)
            ->where('guard_name', $this->getDefaultGuardName())
            ->exists();

        if ($exists) {
            $this->syncRoles([$this->role->value]);
        }
    }

    /**
     * Le rôle n'est jamais assignable en masse : une inscription publique ne
     * doit pas pouvoir se déclarer administratrice. Il est posé ici, avant
     * l'insertion, pour que l'instance en mémoire soit toujours cohérente.
     */
    protected static function booted(): void
    {
        self::creating(function (self $user): void {
            $user->role ??= UserRole::default();
            $user->locale ??= config('app.locale');
        });

        self::saved(function (self $user): void {
            if ($user->wasRecentlyCreated || $user->wasChanged('role')) {
                $user->syncBackOfficeRole();
            }
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'role' => UserRole::class,
        ];
    }
}
