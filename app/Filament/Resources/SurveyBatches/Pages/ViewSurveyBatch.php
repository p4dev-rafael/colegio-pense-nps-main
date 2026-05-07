<?php

namespace App\Filament\Resources\SurveyBatches\Pages;

use App\Filament\Resources\SurveyBatches\SurveyBatchResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewSurveyBatch extends ViewRecord
{
    protected static string $resource = SurveyBatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
