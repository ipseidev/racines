<?php

declare(strict_types=1);

namespace App\Filament\Resources\Projects\Pages;

use App\Filament\Concerns\TitlesInFrench;
use App\Filament\Resources\Projects\ProjectResource;
use Filament\Resources\Pages\ListRecords;

final class ListProjects extends ListRecords
{
    use TitlesInFrench;

    protected static string $resource = ProjectResource::class;
}
