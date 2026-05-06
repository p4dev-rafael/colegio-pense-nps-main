<?php

declare(strict_types=1);

namespace App\Filament\Resources\Students\Pages;

use App\Actions\Enrollment\ImportStudentsCsvAction;
use App\Filament\Resources\Students\StudentResource;
use App\Models\Unit;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

final class ListStudents extends ListRecords
{
    protected static string $resource = StudentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('importCsv')
                ->label(__('students.actions.import_csv'))
                ->icon(Heroicon::OutlinedArrowUpTray)
                ->modalHeading(__('students.import.modal_title'))
                ->schema([
                    Textarea::make('csv')
                        ->label(__('students.import.csv_label'))
                        ->required()
                        ->rows(12)
                        ->hint(__('students.import.hint')),
                ])
                ->action(function (array $data): void {
                    $tenant = Filament::getTenant();
                    if (! $tenant instanceof Unit) {
                        return;
                    }
                    /** @var ImportStudentsCsvAction $import */
                    $import = app(ImportStudentsCsvAction::class);
                    $results = $import->handle(trim((string) $data['csv']), $tenant);

                    $summary = __('students.import.summary', [
                        'imported' => $results['imported'],
                        'skipped' => $results['skipped'],
                    ]);

                    if ($results['errors'] !== []) {
                        Notification::make()
                            ->title(__('students.import.had_errors_title'))
                            ->body($summary."\n".implode("\n", $results['errors']))
                            ->danger()
                            ->persistent()
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->title($summary)
                        ->success()
                        ->send();
                }),
        ];
    }
}
