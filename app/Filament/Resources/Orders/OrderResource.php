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
use Closure;
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
                        /*
                         * En **euros**, comme partout ailleurs dans le
                         * panneau.
                         *
                         * Le champ demandait des centimes, avec « 89,00 € »
                         * affiché dans la ligne juste derrière. Le libellé le
                         * disait, et vingt centimes sont quand même partis là
                         * où vingt euros étaient voulus (T-190) : sur le seul
                         * geste du back-office qui déplace de l'argent, une
                         * unité qui n'est celle de nulle part ailleurs est un
                         * piège, pas une information.
                         *
                         * La virgule est acceptée : c'est ce que tape une
                         * personne en France, et la refuser ferait ressaisir
                         * un montant sous la pression d'un client au
                         * téléphone. La conversion en centiemes entiers se
                         * fait ici, à la frontière ; `IssueRefund` n'a jamais
                         * connu que des centimes.
                         */
                        TextInput::make('amount')
                            ->label(__('admin.orders.actions.amount'))
                            ->helperText(__('admin.orders.actions.amount_help'))
                            ->suffix('€')
                            ->required()
                            ->default(fn (Order $record): string => self::euros(
                                $record->total_cents - $record->refunded_cents,
                            ))
                            ->rule('regex:/^\\d+([.,]\\d{1,2})?$/')
                            ->rules([
                                fn (Order $record): Closure => function (string $attribute, mixed $value, Closure $fail) use ($record): void {
                                    $remaining = $record->total_cents - $record->refunded_cents;
                                    $cents = self::cents((string) $value);

                                    if ($cents < 1 || $cents > $remaining) {
                                        $fail(__('admin.orders.actions.amount_out_of_range', [
                                            'max' => self::euros($remaining),
                                        ]));
                                    }
                                },
                            ]),
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
                                self::cents((string) $data['amount']),
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

    /** Des centimes entiers vers ce qui s'affiche : « 89.00 ». */
    private static function euros(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }

    /**
     * Ce qui a été tapé vers des centimes entiers.
     *
     * `round` et non un transtypage : `(int) (12.34 * 100)` rend 1233 sur une
     * bonne partie des machines, et un remboursement d'un centime de moins
     * est une réclamation.
     */
    private static function cents(string $amount): int
    {
        return (int) round(((float) str_replace([' ', ',', "\u{a0}"], ['', '.', ''], $amount)) * 100);
    }

    private static function canRefund(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->can('refunds.issue');
    }
}
