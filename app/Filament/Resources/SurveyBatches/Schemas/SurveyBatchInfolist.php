<?php

declare(strict_types=1);

namespace App\Filament\Resources\SurveyBatches\Schemas;

use App\Models\SurveyBatch;
use App\Services\SurveyBatchLinkService;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class SurveyBatchInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('common.sections.general'))
                    ->schema([
                        TextEntry::make('title')
                            ->label(__('survey_batches.fields.title')),
                        TextEntry::make('survey.title')
                            ->label(__('survey_batches.fields.survey_id')),
                        TextEntry::make('status')
                            ->label(__('survey_batches.fields.status'))
                            ->badge(),
                        TextEntry::make('description')
                            ->label(__('survey_batches.fields.description'))
                            ->columnSpanFull(),
                        IconEntry::make('requires_identification')
                            ->label(__('survey_batches.fields.requires_identification'))
                            ->boolean(),
                    ])
                    ->columns(3),
                Section::make(__('survey_batches.sections.period'))
                    ->schema([
                        TextEntry::make('starts_at')
                            ->label(__('survey_batches.fields.starts_at'))
                            ->dateTime(),
                        TextEntry::make('ends_at')
                            ->label(__('survey_batches.fields.ends_at'))
                            ->dateTime(),
                        TextEntry::make('public_url')
                            ->label(__('survey_batches.fields.public_url'))
                            ->state(fn (SurveyBatch $record): string => $record->public_token !== null
                                ? app(SurveyBatchLinkService::class)->generatePublicUrl($record)
                                : __('survey_batches.messages.link_unavailable'))
                            ->copyable(fn (SurveyBatch $record): bool => $record->public_token !== null)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make(__('survey_batches.sections.audit'))
                    ->schema([
                        TextEntry::make('activated_at')
                            ->label(__('survey_batches.fields.activated_at'))
                            ->dateTime(),
                        TextEntry::make('closed_at')
                            ->label(__('survey_batches.fields.closed_at'))
                            ->dateTime(),
                        TextEntry::make('createdBy.name')
                            ->label(__('survey_batches.fields.created_by')),
                        TextEntry::make('survey_responses_count')
                            ->label(__('survey_batches.fields.responses_count'))
                            ->state(fn (SurveyBatch $record): int => $record->surveyResponses()->count()),
                    ])
                    ->columns(2),
            ]);
    }
}
