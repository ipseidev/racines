<?php

declare(strict_types=1);

namespace App\Filament\Resources\Cohorts\Pages;

use App\Filament\Concerns\TitlesInFrench;
use App\Filament\Resources\Cohorts\CohortResource;
use Filament\Resources\Pages\ListRecords;

final class ListCohorts extends ListRecords
{
    use TitlesInFrench;

    protected static string $resource = CohortResource::class;
}
