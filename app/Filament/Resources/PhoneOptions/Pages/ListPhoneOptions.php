<?php

declare(strict_types=1);

namespace App\Filament\Resources\PhoneOptions\Pages;

use App\Filament\Resources\PhoneOptions\PhoneOptionResource;
use Filament\Resources\Pages\ListRecords;

final class ListPhoneOptions extends ListRecords
{
    protected static string $resource = PhoneOptionResource::class;
}
