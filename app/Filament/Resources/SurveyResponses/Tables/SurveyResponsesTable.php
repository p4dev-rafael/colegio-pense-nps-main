<?php

declare(strict_types=1);

namespace App\Filament\Resources\SurveyResponses\Tables;

use App\Enums\RespondentType;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

final class SurveyResponsesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('surveyBatch.title')
                    ->label(__('survey_responses.fields.survey_batch_id'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('respondent_name')
                    ->label(__('survey_responses.fields.respondent_name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('enrollment.registration_code')
                    ->label(__('survey_responses.fields.registration_code'))
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('segment.name')
                    ->label(__('survey_responses.fields.segment_id')),
                TextColumn::make('respondent_type')
                    ->label(__('survey_responses.fields.respondent_type'))
                    ->badge(),
                IconColumn::make('is_completed')
                    ->label(__('survey_responses.fields.is_completed'))
                    ->boolean(),
                TextColumn::make('completed_at')
                    ->label(__('survey_responses.fields.completed_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('completed_at', 'desc')
            ->filters([
                SelectFilter::make('respondent_type')
                    ->label(__('survey_responses.fields.respondent_type'))
                    ->options(RespondentType::class),
                TernaryFilter::make('is_completed')
                    ->label(__('survey_responses.fields.is_completed')),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([]);
    }
}
