<?php

declare(strict_types=1);

namespace App\Filament\Resources\SurveyResponses\Pages;

use App\Filament\Resources\SurveyResponses\SurveyResponseResource;
use Filament\Resources\Pages\ListRecords;

final class ListSurveyResponses extends ListRecords
{
    protected static string $resource = SurveyResponseResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
