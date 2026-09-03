<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Engine\CompletionReport;
use App\Models\Cohort;
use App\Models\User;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Schemas\Schema;

/**
 * Le rapport du moteur de complétion, dans le back-office.
 *
 * Le même calcul que `engine:report` — il vit dans `CompletionReport`, lu
 * ici et là. C'est le tableau qu'on regarde en revue de pilote pour répondre
 * à une seule question : le moteur aide-t-il, ou ne fait-il que du bruit ?
 *
 * Le filtre par cohorte n'est pas un raffinement : sans lui, on ne peut pas
 * distinguer « le changement a marché » de « la saison a changé ».
 */
final class EngineReport extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected string $view = 'filament.pages.engine-report';

    protected static ?int $navigationSort = 20;

    public int $days = 30;

    public ?string $cohortId = null;

    public static function getNavigationLabel(): string
    {
        return __('admin.engine_report.title');
    }

    public function getTitle(): string
    {
        return __('admin.engine_report.title');
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->can('support.read');
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->statePath('')
            ->components([
                Select::make('days')
                    ->label(__('admin.engine_report.days'))
                    ->options([
                        7 => __('admin.engine_report.last_week'),
                        30 => __('admin.engine_report.last_month'),
                        90 => __('admin.engine_report.last_quarter'),
                    ])
                    ->selectablePlaceholder(false)
                    ->live(),
                Select::make('cohortId')
                    ->label(__('admin.engine_report.cohort'))
                    ->options(fn (): array => Cohort::query()->pluck('name', 'id')->all())
                    ->placeholder(__('admin.engine_report.all_cohorts'))
                    ->live(),
            ])
            ->columns(2);
    }

    /**
     * @return list<array{
     *     rule: string,
     *     label: string,
     *     fired: int,
     *     resumed: int,
     *     rate: float|null,
     *     median_hours: float|null,
     * }>
     */
    public function rows(): array
    {
        return array_map(
            fn (array $row): array => [
                ...$row,
                'label' => __('admin.engine.rules.'.$row['rule']),
            ],
            CompletionReport::rows($this->days, $this->cohortId),
        );
    }
}
