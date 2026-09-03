<?php

declare(strict_types=1);

namespace App\Filament\Resources\AccessTokens;

use App\Audit\AuditLog;
use App\Enums\TokenType;
use App\Filament\Resources\AccessTokens\Pages\ListAccessTokens;
use App\Models\AccessToken;
use App\Models\User;
use App\Services\Tokens\TokenService;
use App\Support\Options;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * Les liens émis, et leur révocation.
 *
 * On ne voit jamais un lien : la colonne stockée est une **empreinte**, et
 * c'est tout l'intérêt. Un back-office qui afficherait les liens en clair
 * serait un back-office dont la fuite donnerait accès à toutes les familles.
 * Ce qu'on voit ici, c'est le type, la date, l'usage et l'état — de quoi
 * comprendre ce qui s'est passé sans pouvoir agir à la place de quelqu'un.
 *
 * La seule action est la **révocation**, et elle ne demande rien d'autre
 * qu'une confirmation : elle ferme une porte, elle n'en ouvre aucune.
 */
final class AccessTokenResource extends Resource
{
    protected static ?string $model = AccessToken::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static ?int $navigationSort = 7;

    public static function getNavigationLabel(): string
    {
        return __('admin.tokens.title');
    }

    public static function getModelLabel(): string
    {
        return __('admin.tokens.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.tokens.title');
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->can('support.read');
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
                TextColumn::make('type')
                    ->label(__('admin.tokens.type'))
                    ->formatStateUsing(fn (mixed $state): string => $state instanceof TokenType
                        ? Options::label($state)
                        : (string) $state)
                    ->badge(),
                TextColumn::make('subject_type')
                    ->label(__('admin.tokens.subject'))
                    ->formatStateUsing(fn (mixed $state): string => class_basename((string) $state)),
                TextColumn::make('created_at')
                    ->label(__('admin.tokens.created_at'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('expires_at')
                    ->label(__('admin.tokens.expires_at'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('use_count')
                    ->label(__('admin.tokens.use_count')),
                IconColumn::make('revoked_at')
                    ->label(__('admin.tokens.revoked'))
                    ->boolean()
                    ->getStateUsing(fn (AccessToken $record): bool => $record->revoked_at !== null),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label(__('admin.tokens.type'))
                    ->options(fn (): array => collect(TokenType::cases())
                        ->mapWithKeys(fn (TokenType $type): array => [
                            $type->value => Options::label($type),
                        ])
                        ->all()),
            ])
            ->recordActions([
                Action::make('revoke')
                    ->label(__('admin.tokens.actions.revoke'))
                    ->color('danger')
                    ->visible(fn (AccessToken $record): bool => $record->revoked_at === null)
                    ->authorize(fn (): bool => self::canWrite())
                    ->requiresConfirmation()
                    ->modalDescription(__('admin.tokens.actions.revoke_help'))
                    ->action(function (AccessToken $record): void {
                        // Par le service, jamais à la main : c'est lui qui
                        // journalise et qui refuse de révoquer deux fois.
                        app(TokenService::class)->revoke($record, 'revoked_by_support');

                        AuditLog::record('revoked AccessToken', $record, [
                            'type' => $record->type->value,
                        ]);

                        Notification::make()->success()
                            ->title(__('admin.tokens.actions.done'))
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
            'index' => ListAccessTokens::route('/'),
        ];
    }

    private static function canWrite(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->can('tokens.reissue');
    }
}
