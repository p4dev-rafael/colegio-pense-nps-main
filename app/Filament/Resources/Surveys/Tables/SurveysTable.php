<?php

declare(strict_types=1);

namespace App\Filament\Resources\Surveys\Tables;

use App\Actions\Survey\CloneSurveyAction;
use App\Filament\Resources\Surveys\SurveyResource;
use App\Models\Survey;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

final class SurveysTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label(__('surveys.fields.title'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('survey_sections_count')
                    ->label(__('surveys.fields.sections_count'))
                    ->counts('surveySections'),
                IconColumn::make('is_active')
                    ->label(__('surveys.fields.is_active'))
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label(__('common.fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                ActionGroup::make([
                    EditAction::make(),
                    Action::make('clone')
                        ->label(__('surveys.actions.clone'))
                        ->icon(Heroicon::OutlinedDocumentDuplicate)
                        ->authorize(fn(Survey $record): bool => Auth::user()?->can('create', Survey::class) === true)
                        ->form([
                            TextInput::make('title')
                                ->label(__('surveys.fields.title'))
                                ->required()
                                ->maxLength(200)
                                ->default(fn(Survey $record): string => __('surveys.messages.clone_default_title', ['title' => $record->title])),
                        ])
                        ->action(function (Survey $record, array $data) {
                            $clone = app(CloneSurveyAction::class)->execute($record, $data['title']);

                            Notification::make()
                                ->title(__('surveys.messages.cloned'))
                                ->success()
                                ->send();

                            return redirect(SurveyResource::getUrl('edit', ['record' => $clone]));
                        }),
                    DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
