<?php

declare(strict_types=1);

namespace App\Filament\Resources\FamilyMembers\Pages;

use App\Filament\Resources\FamilyMembers\FamilyMemberResource;
use Filament\Resources\Pages\ListRecords;

final class ListFamilyMembers extends ListRecords
{
    protected static string $resource = FamilyMemberResource::class;
}
