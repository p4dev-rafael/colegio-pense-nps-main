<?php

declare(strict_types=1);

namespace App\Filament\Resources\SurveyResponses\Pages;

use App\Filament\Resources\SurveyResponses\SurveyResponseResource;
use Filament\Resources\Pages\ViewRecord;

final class ViewSurveyResponse extends ViewRecord
{
    protected static string $resource = SurveyResponseResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
