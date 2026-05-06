<?php

declare(strict_types=1);

namespace App\Filament\Resources\Subjects\Schemas;

use App\Models\Subject;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class SubjectInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('common.sections.general'))
                    ->schema([
                        TextEntry::make('name')
                            ->label(__('subjects.fields.name')),
                        TextEntry::make('slug')
                            ->label(__('subjects.fields.slug')),
                        IconEntry::make('is_active')
                            ->label(__('subjects.fields.is_active'))
                            ->boolean(),
                        TextEntry::make('sort_order')
                            ->label(__('subjects.fields.sort_order')),
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
                            ->visible(fn (Subject $record): bool => $record->trashed()),
                    ]),
            ]);
    }
}
