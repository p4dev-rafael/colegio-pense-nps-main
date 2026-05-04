<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('common.sections.general'))
                    ->schema([
                        TextEntry::make('name')
                            ->label(__('users.fields.name')),
                        TextEntry::make('email')
                            ->label(__('users.fields.email')),
                        TextEntry::make('role')
                            ->label(__('users.fields.role'))
                            ->badge(),
                        IconEntry::make('is_active')
                            ->label(__('users.fields.is_active'))
                            ->boolean(),
                        TextEntry::make('units.name')
                            ->label(__('users.fields.units'))
                            ->badge()
                            ->listWithLineBreaks(),
                        TextEntry::make('created_at')
                            ->label(__('common.fields.created_at'))
                            ->dateTime(),
                    ])
                    ->columns(2),
            ]);
    }
}
