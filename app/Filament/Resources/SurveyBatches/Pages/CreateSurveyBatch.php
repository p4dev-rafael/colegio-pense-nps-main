<?php

declare(strict_types=1);

namespace App\Filament\Resources\SurveyBatches\Pages;

use App\Enums\SurveyBatchStatus;
use App\Filament\Resources\SurveyBatches\SurveyBatchResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

final class CreateSurveyBatch extends CreateRecord
{
    protected static string $resource = SurveyBatchResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['status'] = SurveyBatchStatus::Draft->value;
        $data['created_by'] = Auth::id();

        return $data;
    }
}
