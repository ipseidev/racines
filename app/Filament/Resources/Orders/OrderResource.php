<?php

declare(strict_types=1);

namespace App\Filament\Resources\Orders;

use App\Actions\IssueRefund;
use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Models\Order;
use App\Models\User;
use App\Support\Options;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Throwable;

/**
 * Les commandes, et le remboursement.
 *
 * Le remboursement est réservé à `refunds.issue` — que le support n'a pas.
 * Ce n'est pas de la méfiance envers l'équipe : c'est que rembourser est le
 * seul geste du back-office qui déplace de l'argent, et qu'un geste
 * irréversible mérite deux paires d'yeux.
 *
 * Le montant est prérempli avec ce qui reste remboursable, et le motif est
 * obligatoire.
 */
final class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?int $navigationSort = 6;

    public static function getNavigationLabel(): string
    {
        return __('admin.orders.title');
    }

    public static function getModelLabel(): string
    {
        return __('admin.orders.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.orders.title');
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
            ->defaultSort('paid_at', 'desc')
            ->columns([
                TextColumn::make('user.email')
                    ->label(__('admin.orders.buyer'))
                    ->searchable(),
                TextColumn::make('status')
                    ->label(__('admin.orders.status'))
                    ->formatStateUsing(fn (mixed $state): string => $state instanceof OrderStatus
                        ? Options::label($state)
                        : (string) $state)
                    ->badge(),
                TextColumn::make('total_cents')
                    ->label(__('admin.orders.total'))
                    ->money('EUR', divideBy: 100)
                    ->sortable(),
                TextColumn::make('refunded_cents')
                    ->label(__('admin.orders.refunded'))
                    ->money('EUR', divideBy: 100),
                TextColumn::make('withdrawal_deadline_at')
                    ->label(__('admin.orders.withdrawal_deadline'))
                    ->date('d/m/Y'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('admin.orders.status'))
                    ->options(fn (): array => collect(OrderStatus::cases())
                        ->mapWithKeys(fn (OrderStatus $status): array => [
                            $status->value => Options::label($status),
                        ])
                        ->all()),
            ])
            ->recordActions([
                Action::make('refund')
                    ->label(__('admin.orders.actions.refund'))
                    ->color('danger')
                    ->visible(fn (Order $record): bool => $record->status->isPaid()
                        && $record->refunded_cents < $record->total_cents)
                    // Le support ne rembourse pas : un geste irréversible qui
                    // déplace de l'argent mérite deux paires d'yeux.
                    ->authorize(fn (): bool => self::canRefund())
                    ->modalDescription(__('admin.orders.actions.refund_help'))
                    ->schema([
                        TextInput::make('amount_cents')
                            ->label(__('admin.orders.actions.amount'))
                            ->helperText(__('admin.orders.actions.amount_help'))
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->default(fn (Order $record): int => $record->total_cents - $record->refunded_cents)
                            ->maxValue(fn (Order $record): int => $record->total_cents - $record->refunded_cents),
                        Textarea::make('reason')
                            ->label(__('admin.orders.actions.reason'))
                            ->required()
                            ->minLength(10)
                            ->maxLength(500),
                    ])
                    ->action(function (Order $record, array $data): void {
                        try {
                            app(IssueRefund::class)->handle(
                                $record,
                                (int) $data['amount_cents'],
                                (string) $data['reason'],
                            );
                        } catch (Throwable $exception) {
                            Notification::make()->danger()
                                ->title(__('admin.orders.actions.failed'))
                                ->body($exception->getMessage())
                                ->send();

                            return;
                        }

                        // L'état de la commande vient du webhook, pas d'ici :
                        // on annonce donc une demande transmise, et non un
                        // remboursement acquis.
                        Notification::make()->success()
                            ->title(__('admin.orders.actions.done'))
                            ->body(__('admin.orders.actions.done_help'))
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
            'index' => ListOrders::route('/'),
        ];
    }

    private static function canRefund(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->can('refunds.issue');
    }
}
