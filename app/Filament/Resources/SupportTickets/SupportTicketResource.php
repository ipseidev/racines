<?php

declare(strict_types=1);

namespace App\Filament\Resources\SupportTickets;

use App\Actions\ConfirmErasure;
use App\Audit\AuditLog;
use App\Enums\SupportTicketKind;
use App\Enums\SupportTicketStatus;
use App\Exceptions\Domain\ErasureBlocked;
use App\Filament\Resources\SupportTickets\Pages\ListSupportTickets;
use App\Models\SupportTicket;
use App\Models\User;
use App\Support\Options;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * La file de travail du support.
 *
 * Les ouverts d'abord, et par ancienneté : un ticket vieux de trois jours est
 * une famille qui attend depuis trois jours. Le tri par date décroissante,
 * habituel ailleurs, ferait exactement l'inverse de ce qu'on veut.
 *
 * Clore demande une note. C'est ce qui fait qu'un ticket rouvert par la même
 * famille se lit avec l'histoire de ce qui a déjà été tenté, plutôt que de
 * repartir de zéro.
 */
final class SupportTicketResource extends Resource
{
    protected static ?string $model = SupportTicket::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInbox;

    protected static ?int $navigationSort = 2;

    public static function getNavigationLabel(): string
    {
        return __('admin.tickets.title');
    }

    public static function getModelLabel(): string
    {
        return __('admin.tickets.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.tickets.title');
    }

    /**
     * Le compteur de la navigation : le nombre d'ouverts.
     *
     * C'est la seule information dont on a besoin en permanence, et la voir
     * sans cliquer évite qu'un ticket dorme parce que personne n'a pensé à
     * regarder.
     */
    public static function getNavigationBadge(): ?string
    {
        $open = SupportTicket::query()
            ->where('status', SupportTicketStatus::Open->value)
            ->count();

        return $open === 0 ? null : (string) $open;
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
            // Les ouverts d'abord, et les plus vieux en tête.
            ->defaultSort('opened_at', 'asc')
            ->modifyQueryUsing(fn ($query) => $query->orderByRaw(
                'case when status = ? then 0 else 1 end',
                [SupportTicketStatus::Open->value],
            ))
            ->columns([
                TextColumn::make('kind')
                    ->label(__('admin.tickets.kind'))
                    ->formatStateUsing(fn (mixed $state): string => $state instanceof SupportTicketKind
                        ? Options::label($state)
                        : (string) $state)
                    ->badge(),
                TextColumn::make('project.owner.name')
                    ->label(__('admin.tickets.family'))
                    ->searchable(),
                TextColumn::make('status')
                    ->label(__('admin.tickets.status'))
                    ->formatStateUsing(fn (mixed $state): string => $state instanceof SupportTicketStatus
                        ? Options::label($state)
                        : (string) $state)
                    ->badge(),
                TextColumn::make('opened_at')
                    ->label(__('admin.tickets.opened_at'))
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('admin.tickets.status'))
                    ->options(fn (): array => collect(SupportTicketStatus::cases())
                        ->mapWithKeys(fn (SupportTicketStatus $status): array => [
                            $status->value => Options::label($status),
                        ])
                        ->all()),
                SelectFilter::make('kind')
                    ->label(__('admin.tickets.kind'))
                    ->options(fn (): array => collect(SupportTicketKind::cases())
                        ->mapWithKeys(fn (SupportTicketKind $kind): array => [
                            $kind->value => Options::label($kind),
                        ])
                        ->all()),
            ])
            ->recordActions([
                /*
                 * Confirmer un effacement.
                 *
                 * Le seul geste du back-office qui détruise du contenu, et le
                 * seul qu'aucune sauvegarde ne rattrape après quatre-vingt-dix
                 * jours. D'où trois gardes qui se cumulent : le droit
                 * `rgpd.erase`, une confirmation, et un motif écrit — le même
                 * dispositif que le remboursement, pour la même raison. Un
                 * geste irréversible mérite deux paires d'yeux.
                 *
                 * L'action n'efface pas elle-même : elle appelle
                 * `ConfirmErasure`, qui refuse si un livre est à
                 * l'impression et rend une explication à montrer.
                 */
                Action::make('confirm_erasure')
                    ->label(__('admin.tickets.actions.confirm_erasure'))
                    ->color('danger')
                    ->icon(Heroicon::OutlinedTrash)
                    ->visible(fn (SupportTicket $record): bool => $record->isOpen()
                        && $record->kind === SupportTicketKind::ErasureRequested)
                    ->authorize(fn (): bool => self::canErase())
                    ->requiresConfirmation()
                    ->modalDescription(__('admin.tickets.actions.confirm_erasure_help'))
                    ->schema([
                        Textarea::make('note')
                            ->label(__('admin.tickets.actions.erasure_note'))
                            ->helperText(__('admin.tickets.actions.erasure_note_help'))
                            ->required()
                            ->minLength(10)
                            ->maxLength(500),
                    ])
                    ->action(function (SupportTicket $record, array $data): void {
                        $user = auth()->user();

                        if (! $user instanceof User) {
                            return;
                        }

                        try {
                            app(ConfirmErasure::class)->handle($record, $user);
                        } catch (ErasureBlocked $exception) {
                            Notification::make()->danger()
                                ->title(__('admin.tickets.actions.erasure_blocked'))
                                ->body($exception->getMessage())
                                ->persistent()
                                ->send();

                            return;
                        }

                        $record->forceFill([
                            'payload' => array_merge($record->payload ?? [], [
                                'confirmation_note' => $data['note'] ?? null,
                            ]),
                        ])->save();

                        Notification::make()->success()
                            ->title(__('admin.tickets.actions.erasure_done'))
                            ->body(__('admin.tickets.actions.erasure_done_help'))
                            ->send();
                    }),

                Action::make('close')
                    ->label(__('admin.tickets.actions.close'))
                    ->visible(fn (SupportTicket $record): bool => $record->isOpen())
                    ->authorize(fn (): bool => self::canWrite())
                    ->schema([
                        Textarea::make('note')
                            ->label(__('admin.tickets.actions.note'))
                            ->helperText(__('admin.tickets.actions.note_help'))
                            ->required()
                            ->minLength(5)
                            ->maxLength(500),
                    ])
                    ->action(function (SupportTicket $record, array $data): void {
                        $user = auth()->user();

                        $record->forceFill([
                            'status' => SupportTicketStatus::Closed,
                            'closed_at' => now(),
                            'closed_by_user_id' => $user instanceof User ? $user->id : null,
                            'payload' => array_merge($record->payload ?? [], [
                                'closing_note' => $data['note'] ?? null,
                            ]),
                        ])->save();

                        AuditLog::record('closed SupportTicket', $record, [
                            'note' => $data['note'] ?? null,
                        ], $record->project);

                        Notification::make()->success()
                            ->title(__('admin.tickets.actions.done'))
                            ->send();
                    }),
            ]);
    }

    /**
     * `rgpd.erase` et non `support.write`.
     *
     * Effacer les récits d'une famille n'est pas un geste de support de
     * premier niveau : c'est le seul acte du back-office qu'aucune sauvegarde
     * ne rattrape passé quatre-vingt-dix jours.
     */
    private static function canErase(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->can('rgpd.erase');
    }

    /**
     * @return array<string, mixed>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListSupportTickets::route('/'),
        ];
    }

    private static function canWrite(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->can('support.write');
    }
}
