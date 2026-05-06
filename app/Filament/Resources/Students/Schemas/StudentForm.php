<?php

declare(strict_types=1);

namespace App\Filament\Resources\Students\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class StudentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('common.sections.general'))
                    ->schema([
                        TextInput::make('name')
                            ->label(__('students.fields.name'))
                            ->required()
                            ->maxLength(100),
                        Toggle::make('is_active')
                            ->label(__('students.fields.is_active'))
                            ->default(true),
                    ])
                    ->columns(2),
                Section::make(__('common.sections.guardian'))
                    ->schema([
                        TextInput::make('guardian_name')
                            ->label(__('students.fields.guardian_name'))
                            ->maxLength(100),
                        TextInput::make('guardian_email')
                            ->label(__('students.fields.guardian_email'))
                            ->email()
                            ->maxLength(254),
                        TextInput::make('guardian_phone')
                            ->label(__('students.fields.guardian_phone'))
                            ->maxLength(20)
                            ->tel(),
                    ])
                    ->columns(2),
            ]);
    }
}
