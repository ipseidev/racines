<?php

declare(strict_types=1);

namespace App\Filament\Resources\Narrators\Pages;

use App\Filament\Concerns\TitlesInFrench;
use App\Filament\Resources\Narrators\NarratorResource;
use Filament\Resources\Pages\ListRecords;

final class ListNarrators extends ListRecords
{
    use TitlesInFrench;

    protected static string $resource = NarratorResource::class;
}
