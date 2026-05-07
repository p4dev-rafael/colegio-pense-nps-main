<?php

declare(strict_types=1);

namespace App\Filament\Resources\SurveyBatches\Tables;

use App\Actions\Survey\ActivateBatchAction as ActivateAction;
use App\Actions\Survey\CloseBatchAction as CloseAction;
use App\Enums\SurveyBatchStatus;
use App\Exceptions\Survey\SurveyException;
use App\Models\SurveyBatch;
use App\Services\SurveyBatchLinkService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

final class SurveyBatchesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label(__('survey_batches.fields.title'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('survey.title')
                    ->label(__('survey_batches.fields.survey_id'))
                    ->toggleable(),
                TextColumn::make('status')
                    ->label(__('survey_batches.fields.status'))
                    ->badge()
                    ->sortable(),
                IconColumn::make('requires_identification')
                    ->label(__('survey_batches.fields.requires_identification'))
                    ->boolean()
                    ->toggleable(),
                TextColumn::make('starts_at')
                    ->label(__('survey_batches.fields.starts_at'))
                    ->dateTime()
                    ->toggleable(),
                TextColumn::make('ends_at')
                    ->label(__('survey_batches.fields.ends_at'))
                    ->dateTime()
                    ->toggleable(),
                TextColumn::make('survey_responses_count')
                    ->label(__('survey_batches.fields.responses_count'))
                    ->counts('surveyResponses'),
                TextColumn::make('created_at')
                    ->label(__('common.fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label(__('survey_batches.fields.status'))
                    ->options(SurveyBatchStatus::class),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn (SurveyBatch $record): bool => $record->status === SurveyBatchStatus::Draft),
                Action::make('activate')
                    ->label(__('survey_batches.actions.activate'))
                    ->icon(Heroicon::OutlinedPlay)
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (SurveyBatch $record): bool => in_array($record->status, [SurveyBatchStatus::Draft, SurveyBatchStatus::Closed], strict: true))
                    ->authorize(fn (SurveyBatch $record): bool => $record->status === SurveyBatchStatus::Draft
                        ? Auth::user()?->can('update', $record) === true
                        : Auth::user()?->can('reopen', $record) === true)
                    ->action(function (SurveyBatch $record): void {
                        try {
                            app(ActivateAction::class)->execute($record, Auth::user());

                            Notification::make()
                                ->title(__('survey_batches.messages.activated'))
                                ->success()
                                ->send();
                        } catch (SurveyException $e) {
                            Notification::make()
                                ->title($e->userMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Action::make('close')
                    ->label(__('survey_batches.actions.close'))
                    ->icon(Heroicon::OutlinedLockClosed)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (SurveyBatch $record): bool => $record->status === SurveyBatchStatus::Active)
                    ->authorize(fn (SurveyBatch $record): bool => Auth::user()?->can('update', $record) === true)
                    ->action(function (SurveyBatch $record): void {
                        try {
                            app(CloseAction::class)->execute($record, Auth::user());

                            Notification::make()
                                ->title(__('survey_batches.messages.closed'))
                                ->success()
                                ->send();
                        } catch (SurveyException $e) {
                            Notification::make()
                                ->title($e->userMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Action::make('copy_link')
                    ->label(__('survey_batches.actions.copy_link'))
                    ->icon(Heroicon::OutlinedClipboardDocument)
                    ->color('info')
                    ->visible(fn (SurveyBatch $record): bool => $record->public_token !== null)
                    ->action(function (SurveyBatch $record): void {
                        $url = app(SurveyBatchLinkService::class)->generatePublicUrl($record);

                        Notification::make()
                            ->title(__('survey_batches.messages.link_copied'))
                            ->body($url)
                            ->success()
                            ->send();
                    }),
                DeleteAction::make()
                    ->visible(fn (SurveyBatch $record): bool => $record->status === SurveyBatchStatus::Draft),
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
