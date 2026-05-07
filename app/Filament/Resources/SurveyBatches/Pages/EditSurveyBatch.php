<?php

namespace App\Filament\Resources\SurveyBatches\Pages;

use App\Filament\Resources\SurveyBatches\SurveyBatchResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditSurveyBatch extends EditRecord
{
    protected static string $resource = SurveyBatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
