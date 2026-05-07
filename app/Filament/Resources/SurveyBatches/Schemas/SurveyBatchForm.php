<?php

declare(strict_types=1);

namespace App\Filament\Resources\SurveyBatches\Schemas;

use App\Models\Survey;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class SurveyBatchForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('common.sections.general'))
                    ->schema([
                        Select::make('survey_id')
                            ->label(__('survey_batches.fields.survey_id'))
                            ->options(fn () => Survey::query()
                                ->where('is_active', true)
                                ->orderBy('title')
                                ->pluck('title', 'id')
                                ->all())
                            ->searchable()
                            ->preload()
                            ->required(),
                        TextInput::make('title')
                            ->label(__('survey_batches.fields.title'))
                            ->required()
                            ->maxLength(200),
                        Textarea::make('description')
                            ->label(__('survey_batches.fields.description'))
                            ->rows(3)
                            ->maxLength(2000)
                            ->columnSpanFull(),
                        Toggle::make('requires_identification')
                            ->label(__('survey_batches.fields.requires_identification'))
                            ->helperText(__('survey_batches.helpers.requires_identification'))
                            ->default(true)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make(__('survey_batches.sections.period'))
                    ->schema([
                        DateTimePicker::make('starts_at')
                            ->label(__('survey_batches.fields.starts_at'))
                            ->seconds(false),
                        DateTimePicker::make('ends_at')
                            ->label(__('survey_batches.fields.ends_at'))
                            ->seconds(false)
                            ->after('starts_at'),
                    ])
                    ->columns(2),
            ]);
    }
}
