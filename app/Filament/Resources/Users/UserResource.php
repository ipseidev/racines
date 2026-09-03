<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users;

use App\Actions\ChangeUserRole;
use App\Audit\AuditLog;
use App\Enums\UserRole;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * Les comptes, et leurs rôles. Réservé à l'administration.
 *
 * Le rôle n'est **jamais** assignable en masse (bloc 01) : il se pose
 * explicitement, et le poser déclenche la traduction en permissions fines.
 * C'est pourquoi cette page passe par une action dédiée plutôt que par un
 * formulaire d'édition — un `update()` sur un champ protégé ne ferait rien, en
 * silence.
 *
 * La colonne « double authentification » est là pour la revue d'accès
 * trimestrielle du doc 04 §12 : un compte du personnel sans second facteur ne
 * peut rien ouvrir, mais il vaut mieux le voir que de le découvrir.
 */
final class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static ?int $navigationSort = 14;

    public static function getNavigationLabel(): string
    {
        return __('admin.users.title');
    }

    public static function getModelLabel(): string
    {
        return __('admin.users.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.users.title');
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        // Les rôles ne se distribuent pas depuis le support.
        return $user instanceof User && $user->can('brand.manage');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->label(__('admin.users.name'))
                    ->searchable(),
                TextColumn::make('email')
                    ->label(__('admin.users.email'))
                    ->searchable(),
                TextColumn::make('role')
                    ->label(__('admin.users.role'))
                    ->formatStateUsing(fn (mixed $state): string => __(
                        $state instanceof UserRole ? $state->label() : 'admin.roles.'.(string) $state,
                    ))
                    ->badge(),
                IconColumn::make('two_factor_confirmed_at')
                    ->label(__('admin.users.mfa'))
                    ->boolean()
                    ->getStateUsing(fn (User $record): bool => $record->getAppAuthenticationSecret() !== null),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->label(__('admin.users.role'))
                    ->options(fn (): array => collect(UserRole::cases())
                        ->mapWithKeys(fn (UserRole $role): array => [$role->value => __($role->label())])
                        ->all()),
            ])
            ->recordActions([
                Action::make('role')
                    ->label(__('admin.users.actions.role'))
                    ->authorize(fn (): bool => self::canAccess())
                    ->schema([
                        Select::make('role')
                            ->label(__('admin.users.role'))
                            ->options(fn (): array => collect(UserRole::cases())
                                ->mapWithKeys(fn (UserRole $role): array => [$role->value => __($role->label())])
                                ->all())
                            ->required(),
                    ])
                    ->action(function (User $record, array $data): void {
                        // Explicitement, jamais par assignation de masse : la
                        // colonne est protégée, et un `update()` ne ferait
                        // rien, en silence. L'action le fait, et rend le rôle
                        // précédent pour que le journal puisse dire ce qui a
                        // changé.
                        $before = app(ChangeUserRole::class)
                            ->handle($record, UserRole::from((string) $data['role']));

                        AuditLog::record('changed UserRole', $record, [
                            'from' => $before->value,
                            'to' => $record->refresh()->role->value,
                        ]);

                        Notification::make()->success()
                            ->title(__('admin.users.actions.done'))
                            ->send();
                    }),
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
        ];
    }
}
