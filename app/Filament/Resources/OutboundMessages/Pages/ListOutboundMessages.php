<?php

declare(strict_types=1);

namespace App\Filament\Resources\OutboundMessages\Pages;

use App\Filament\Resources\OutboundMessages\OutboundMessageResource;
use Filament\Resources\Pages\ListRecords;

final class ListOutboundMessages extends ListRecords
{
    protected static string $resource = OutboundMessageResource::class;
}
