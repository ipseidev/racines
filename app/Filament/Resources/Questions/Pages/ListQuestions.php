<?php

declare(strict_types=1);

namespace App\Filament\Resources\Questions\Pages;

use App\Filament\Concerns\TitlesInFrench;
use App\Filament\Resources\Questions\QuestionResource;
use Filament\Resources\Pages\ListRecords;

final class ListQuestions extends ListRecords
{
    use TitlesInFrench;

    protected static string $resource = QuestionResource::class;
}
