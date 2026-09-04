<?php

declare(strict_types=1);

namespace App\Filament\Resources\Leads\Pages;

use App\Filament\Concerns\TitlesInFrench;
use App\Filament\Resources\Leads\LeadResource;
use Filament\Resources\Pages\ListRecords;

final class ListLeads extends ListRecords
{
    use TitlesInFrench;

    protected static string $resource = LeadResource::class;
}
