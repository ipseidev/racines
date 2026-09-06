<?php

declare(strict_types=1);

namespace App\Filament\Resources\Books\Pages;

use App\Filament\Concerns\TitlesInFrench;
use App\Filament\Resources\Books\BookResource;
use Filament\Resources\Pages\ListRecords;

final class ListBooks extends ListRecords
{
    use TitlesInFrench;

    protected static string $resource = BookResource::class;
}
