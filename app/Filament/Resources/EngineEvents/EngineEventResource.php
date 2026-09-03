<?php

declare(strict_types=1);

namespace App\Filament\Resources\EngineEvents;

use App\Filament\Resources\EngineEvents\Pages\ListEngineEvents;
use App\Models\EngineEvent;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Ce que le moteur de complétion a fait, et à qui il l'a dit.
 *
 * En lecture seule, sans exception : réémettre une règle à la main créerait un
 * doublon que l'idempotence du moteur est justement faite pour empêcher. Si
 * une relance doit partir, elle part par le moteur — ou pas du tout.
 *
 * Les lignes « supprimées » — celles qu'une règle voulait envoyer mais que le
 * plafond quotidien a retenues — sont ici aussi. C'est ce qui permet de savoir
 * si le plafond protège ou s'il étouffe.
 */
final class EngineEventResource extends Resource
{
    protected static ?string $model = EngineEvent::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCpuChip;

    protected static ?int $navigationSort = 9;

    public static function getNavigationGroup(): string
    {
        // Une méthode et non une propriété : un défaut de propriété
        // ne peut pas appeler `__()`.
        return __('admin.groups.registers');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.engine.title');
    }

    public static function getModelLabel(): string
    {
        return __('admin.engine.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.engine.title');
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
            ->defaultSort('fired_at', 'desc')
            ->columns([
                TextColumn::make('rule_id')
                    ->label(__('admin.engine.rule'))
                    ->badge(),
                TextColumn::make('project.owner.name')
                    ->label(__('admin.engine.family'))
                    ->searchable(),
                TextColumn::make('fired_at')
                    ->label(__('admin.engine.fired_at'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('outcome')
                    ->label(__('admin.engine.outcome')),
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListEngineEvents::route('/'),
        ];
    }
}
