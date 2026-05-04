<?php

declare(strict_types=1);

namespace App\Filament\Resources\Units\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class UnitInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('common.sections.general'))
                    ->schema([
                        TextEntry::make('name')
                            ->label(__('units.fields.name')),
                        TextEntry::make('slug')
                            ->label(__('units.fields.slug')),
                        IconEntry::make('is_active')
                            ->label(__('units.fields.is_active'))
                            ->boolean(),
                        TextEntry::make('created_at')
                            ->label(__('common.fields.created_at'))
                            ->dateTime(),
                    ])
                    ->columns(2),
            ]);
    }
}
