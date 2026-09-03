<?php

declare(strict_types=1);

namespace App\Filament\Resources\FamilyMembers;

use App\Actions\ReissueFamilyLink;
use App\Actions\RemoveFamilyMember;
use App\Audit\AuditLog;
use App\Filament\Resources\FamilyMembers\Pages\ListFamilyMembers;
use App\Models\FamilyMember;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Les proches qui écoutent, et leur lien.
 *
 * Le lien se **réémet**, il ne se relit pas : les jetons sont stockés hachés,
 * et un lien en clair n'existe qu'entre son émission et son envoi (bloc 03).
 * Réémettre révoque le précédent, ce qui est exactement le geste utile quand
 * quelqu'un dit « mon lien ne marche plus ».
 *
 * Retirer un accès pose un `removed_at` et révoque le jeton — la ligne reste.
 * Savoir qu'une personne a eu accès fait partie de ce qu'on doit pouvoir
 * répondre plus tard.
 */
final class FamilyMemberResource extends Resource
{
    protected static ?string $model = FamilyMember::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?int $navigationSort = 4;

    public static function getNavigationLabel(): string
    {
        return __('admin.family.title');
    }

    public static function getModelLabel(): string
    {
        return __('admin.family.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.family.title');
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
                TextColumn::make('display_name')
                    ->label(__('admin.family.name'))
                    ->searchable(),
                TextColumn::make('project.owner.name')
                    ->label(__('admin.family.family'))
                    ->searchable(),
                TextColumn::make('relationship')
                    ->label(__('admin.family.relationship'))
                    ->placeholder('—'),
                IconColumn::make('can_contribute')
                    ->label(__('admin.family.can_contribute'))
                    ->boolean(),
                TextColumn::make('first_seen_at')
                    ->label(__('admin.family.first_seen_at'))
                    ->dateTime('d/m/Y')
                    ->placeholder(__('admin.family.never_opened')),
                IconColumn::make('removed_at')
                    ->label(__('admin.family.removed'))
                    ->boolean()
                    ->getStateUsing(fn (FamilyMember $record): bool => $record->removed_at !== null),
            ])
            ->recordActions([
                Action::make('reissue')
                    ->label(__('admin.family.actions.reissue'))
                    ->visible(fn (FamilyMember $record): bool => $record->removed_at === null)
                    ->authorize(fn (): bool => self::canReissue())
                    ->requiresConfirmation()
                    ->modalDescription(__('admin.family.actions.reissue_help'))
                    ->action(function (FamilyMember $record): void {
                        app(ReissueFamilyLink::class)->handle($record);

                        // Le lien en clair ne part pas au journal : il n'y a
                        // aucune raison de le conserver, et une seule de ne
                        // pas le faire.
                        AuditLog::record('reissued ListenLink', $record, [], $record->project);

                        Notification::make()->success()
                            ->title(__('admin.family.actions.reissued'))
                            ->body(__('admin.family.actions.reissued_help'))
                            ->send();
                    }),

                Action::make('remove')
                    ->label(__('admin.family.actions.remove'))
                    ->color('danger')
                    ->visible(fn (FamilyMember $record): bool => $record->removed_at === null)
                    ->authorize(fn (): bool => self::canReissue())
                    ->requiresConfirmation()
                    ->action(function (FamilyMember $record): void {
                        // Retiré, pas supprimé, et le jeton révoqué dans le
                        // même geste : l'action porte les deux, parce qu'une
                        // seconde copie aurait oublié la révocation.
                        app(RemoveFamilyMember::class)->handle($record, 'removed_by_support');

                        AuditLog::record('removed FamilyMember', $record, [], $record->project);

                        Notification::make()->success()
                            ->title(__('admin.family.actions.removed'))
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
            'index' => ListFamilyMembers::route('/'),
        ];
    }

    private static function canReissue(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->can('tokens.reissue');
    }
}
