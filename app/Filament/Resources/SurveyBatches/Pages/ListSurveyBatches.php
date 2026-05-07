<?php

namespace App\Filament\Resources\SurveyBatches\Pages;

use App\Filament\Resources\SurveyBatches\SurveyBatchResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSurveyBatches extends ListRecords
{
    protected static string $resource = SurveyBatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
