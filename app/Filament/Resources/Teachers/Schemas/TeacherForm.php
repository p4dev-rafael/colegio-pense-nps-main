<?php

declare(strict_types=1);

namespace App\Filament\Resources\Teachers\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class TeacherForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('common.sections.general'))
                    ->schema([
                        TextInput::make('name')
                            ->label(__('teachers.fields.name'))
                            ->required()
                            ->maxLength(100),
                        TextInput::make('email')
                            ->label(__('teachers.fields.email'))
                            ->email()
                            ->maxLength(254)
                            ->nullable()
                            ->unique(
                                table: 'teachers',
                                column: 'email',
                                ignorable: null,
                                ignoreRecord: true,
                                modifyRuleUsing: fn (\Illuminate\Validation\Rules\Unique $rule) => $rule->whereNull('deleted_at'),
                            ),
                        Toggle::make('is_active')
                            ->label(__('teachers.fields.is_active'))
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }
}
