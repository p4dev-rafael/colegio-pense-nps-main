<?php

declare(strict_types=1);

namespace App\Filament\Resources\Teachers\Schemas;

use App\Models\Teacher;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class TeacherInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('common.sections.general'))
                    ->schema([
                        TextEntry::make('name')
                            ->label(__('teachers.fields.name')),
                        TextEntry::make('email')
                            ->label(__('teachers.fields.email'))
                            ->placeholder('—'),
                        IconEntry::make('is_active')
                            ->label(__('teachers.fields.is_active'))
                            ->boolean(),
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
                            ->visible(fn (Teacher $record): bool => $record->trashed()),
                    ]),
            ]);
    }
}
