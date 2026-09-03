<?php

declare(strict_types=1);

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Concerns\TitlesInFrench;
use App\Filament\Resources\Orders\OrderResource;
use Filament\Resources\Pages\ListRecords;

final class ListOrders extends ListRecords
{
    use TitlesInFrench;

    protected static string $resource = OrderResource::class;
}
