<?php

declare(strict_types=1);

namespace App\Filament\Resources\Stories\Pages;

use App\Filament\Concerns\TitlesInFrench;
use App\Filament\Resources\Stories\StoryResource;
use Filament\Resources\Pages\ListRecords;

final class ListStories extends ListRecords
{
    use TitlesInFrench;

    protected static string $resource = StoryResource::class;
}
