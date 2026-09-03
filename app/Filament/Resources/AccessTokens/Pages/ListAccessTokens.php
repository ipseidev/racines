<?php

declare(strict_types=1);

namespace App\Filament\Resources\AccessTokens\Pages;

use App\Filament\Resources\AccessTokens\AccessTokenResource;
use Filament\Resources\Pages\ListRecords;

final class ListAccessTokens extends ListRecords
{
    protected static string $resource = AccessTokenResource::class;
}
