<?php

declare(strict_types=1);

namespace App\Filament\Resources\Questions\Pages;

use App\Filament\Resources\Questions\QuestionResource;
use Filament\Resources\Pages\ListRecords;

final class ListQuestions extends ListRecords
{
    protected static string $resource = QuestionResource::class;
}
