<?php

declare(strict_types=1);

namespace App\Filament\Resources\Students\Schemas;

use App\Models\Student;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class StudentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('common.sections.general'))
                    ->schema([
                        TextEntry::make('name')
                            ->label(__('students.fields.name')),
                        IconEntry::make('is_active')
                            ->label(__('students.fields.is_active'))
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
                            ->visible(fn (Student $record): bool => $record->trashed()),
                    ]),
                Section::make(__('common.sections.guardian'))
                    ->schema([
                        TextEntry::make('guardian_name')
                            ->label(__('students.fields.guardian_name'))
                            ->placeholder('—'),
                        TextEntry::make('guardian_email')
                            ->label(__('students.fields.guardian_email'))
                            ->placeholder('—'),
                        TextEntry::make('guardian_phone')
                            ->label(__('students.fields.guardian_phone'))
                            ->placeholder('—'),
                    ]),
            ]);
    }
}
