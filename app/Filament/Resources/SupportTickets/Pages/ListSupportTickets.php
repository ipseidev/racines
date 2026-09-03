<?php

declare(strict_types=1);

namespace App\Filament\Resources\SupportTickets\Pages;

use App\Filament\Concerns\TitlesInFrench;
use App\Filament\Resources\SupportTickets\SupportTicketResource;
use Filament\Resources\Pages\ListRecords;

final class ListSupportTickets extends ListRecords
{
    use TitlesInFrench;

    protected static string $resource = SupportTicketResource::class;
}
