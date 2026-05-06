<?php

declare(strict_types=1);

namespace App\Filament\Resources\Surveys\Schemas;

use App\Models\Survey;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class SurveyInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('common.sections.general'))
                    ->schema([
                        TextEntry::make('title')
                            ->label(__('surveys.fields.title')),
                        TextEntry::make('description')
                            ->label(__('surveys.fields.description'))
                            ->placeholder('—'),
                        IconEntry::make('is_active')
                            ->label(__('surveys.fields.is_active'))
                            ->boolean(),
                        TextEntry::make('survey_sections_count')
                            ->label(__('surveys.fields.sections_count'))
                            ->state(fn (Survey $record): int => $record->surveySections()->count()),
                        TextEntry::make('created_at')
                            ->label(__('common.fields.created_at'))
                            ->dateTime()
                            ->placeholder('—'),
                        TextEntry::make('updated_at')
                            ->label(__('common.fields.updated_at'))
                            ->dateTime()
                            ->placeholder('—'),
                        TextEntry::make('deleted_at')
                            ->label(__('common.fields.deleted_at'))
                            ->dateTime()
                            ->placeholder('—')
                            ->visible(fn (Survey $record): bool => $record->trashed()),
                    ]),
            ]);
    }
}
