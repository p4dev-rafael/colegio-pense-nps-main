<?php

declare(strict_types=1);

namespace App\Filament\Resources\SurveyResponses\Schemas;

use App\Models\SurveyResponse;
use App\Support\Survey\SurveyResponseAnswersDisplay;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

final class SurveyResponseInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('survey_responses.sections.identification'))
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('surveyBatch.title')
                            ->label(__('survey_responses.fields.survey_batch_id')),
                        TextEntry::make('respondent_name')
                            ->label(__('survey_responses.fields.respondent_name')),
                        TextEntry::make('respondent_type')
                            ->label(__('survey_responses.fields.respondent_type'))
                            ->badge(),
                        TextEntry::make('enrollment.registration_code')
                            ->label(__('survey_responses.fields.registration_code'))
                            ->placeholder('—'),
                        TextEntry::make('segment.name')
                            ->label(__('survey_responses.fields.segment_id'))
                            ->placeholder('—'),
                        IconEntry::make('is_completed')
                            ->label(__('survey_responses.fields.is_completed'))
                            ->boolean(),
                        TextEntry::make('completed_at')
                            ->label(__('survey_responses.fields.completed_at'))
                            ->dateTime()
                            ->columnSpanFull(),
                        TextEntry::make('ip_address')
                            ->label(__('survey_responses.fields.ip_address')),
                        TextEntry::make('user_agent')
                            ->label(__('survey_responses.fields.user_agent'))
                            ->columnSpan(2),
                    ])
                    ->columns(3),
                Section::make(__('survey_responses.sections.answers'))
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('answers')
                            ->label(__('survey_responses.fields.answers'))
                            ->columnSpanFull()
                            ->state(fn(SurveyResponse $record): HtmlString => SurveyResponseAnswersDisplay::toHtml($record))
                            ->html()
                            ->prose(),
                    ]),
            ]);
    }
}
