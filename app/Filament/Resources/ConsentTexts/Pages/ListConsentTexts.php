<?php

declare(strict_types=1);

namespace App\Filament\Resources\ConsentTexts\Pages;

use App\Filament\Resources\ConsentTexts\ConsentTextResource;
use Filament\Resources\Pages\ListRecords;

final class ListConsentTexts extends ListRecords
{
    protected static string $resource = ConsentTextResource::class;
}
