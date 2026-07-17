<?php

declare(strict_types=1);

namespace App\Filament\Resources\SurveyBatches\Pages;

use App\Actions\Survey\ExportSurveyBatchPdfAction;
use App\Filament\Resources\SurveyBatches\SurveyBatchResource;
use App\Models\SurveyBatch;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ViewSurveyBatch extends ViewRecord
{
    protected static string $resource = SurveyBatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportPdf')
                ->label(__('survey_batches.actions.export_pdf'))
                ->icon(Heroicon::OutlinedDocumentArrowDown)
                ->authorize(fn (SurveyBatch $record): bool => auth()->user()?->can('view', $record) === true)
                ->action(fn (SurveyBatch $record): BinaryFileResponse => app(ExportSurveyBatchPdfAction::class)->execute($record)),
            EditAction::make(),
        ];
    }
}
