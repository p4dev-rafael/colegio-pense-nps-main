<?php

declare(strict_types=1);

namespace App\Filament\Resources\Units\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class UnitForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('common.sections.general'))
                    ->schema([
                        TextInput::make('name')
                            ->label(__('units.fields.name'))
                            ->required()
                            ->maxLength(100),
                        TextInput::make('slug')
                            ->label(__('units.fields.slug'))
                            ->required()
                            ->maxLength(100)
                            ->unique(ignoreRecord: true)
                            ->alphaDash(),
                        Toggle::make('is_active')
                            ->label(__('units.fields.is_active'))
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }
}
