<?php

declare(strict_types=1);

namespace App\Filament\Resources\EngineEvents\Pages;

use App\Filament\Resources\EngineEvents\EngineEventResource;
use Filament\Resources\Pages\ListRecords;

final class ListEngineEvents extends ListRecords
{
    protected static string $resource = EngineEventResource::class;
}
