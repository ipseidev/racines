<?php

declare(strict_types=1);

namespace App\Filament\Resources\Transcripts\Pages;

use App\Filament\Resources\Transcripts\TranscriptResource;
use Filament\Resources\Pages\ListRecords;

final class ListTranscripts extends ListRecords
{
    protected static string $resource = TranscriptResource::class;
}
