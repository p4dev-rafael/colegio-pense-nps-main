<?php

declare(strict_types=1);

namespace App\Filament\Resources\Surveys\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class SurveyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('common.sections.general'))
                    ->schema([
                        TextInput::make('title')
                            ->label(__('surveys.fields.title'))
                            ->required()
                            ->maxLength(200),
                        Textarea::make('description')
                            ->label(__('surveys.fields.description'))
                            ->rows(3)
                            ->maxLength(2000),
                        Toggle::make('is_active')
                            ->label(__('surveys.fields.is_active'))
                            ->default(true),
                    ])
                    ->columns(1),
            ]);
    }
}
