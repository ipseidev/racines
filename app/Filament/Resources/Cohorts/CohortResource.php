<?php

declare(strict_types=1);

namespace App\Filament\Resources\Cohorts;

use App\Filament\Resources\Cohorts\Pages\ListCohorts;
use App\Models\Cohort;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Les cohortes du pilote.
 *
 * Elles servent à comparer : une cohorte est le seul moyen honnête de savoir
 * si un changement a amélioré quelque chose, ou si la saison a simplement
 * changé.
 */
final class CohortResource extends Resource
{
    protected static ?string $model = Cohort::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleGroup;

    protected static ?int $navigationSort = 13;

    public static function getNavigationGroup(): string
    {
        // Une méthode et non une propriété : un défaut de propriété
        // ne peut pas appeler `__()`.
        return __('admin.groups.reference');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.cohorts.title');
    }

    public static function getModelLabel(): string
    {
        return __('admin.cohorts.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.cohorts.title');
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
            ->defaultSort('started_at', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->label(__('admin.cohorts.name'))
                    ->searchable(),
                TextColumn::make('phase')
                    ->label(__('admin.cohorts.phase'))
                    ->badge(),
                TextColumn::make('started_at')
                    ->label(__('admin.cohorts.started_at'))
                    ->date('d/m/Y')
                    ->sortable(),
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListCohorts::route('/'),
        ];
    }
}
