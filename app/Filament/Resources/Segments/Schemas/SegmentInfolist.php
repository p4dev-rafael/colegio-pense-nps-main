<?php

declare(strict_types=1);

namespace App\Filament\Resources\Segments\Schemas;

use App\Models\Segment;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class SegmentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('common.sections.general'))
                    ->schema([
                        TextEntry::make('name')
                            ->label(__('segments.fields.name')),
                        TextEntry::make('slug')
                            ->label(__('segments.fields.slug')),
                        TextEntry::make('group')
                            ->label(__('segments.fields.group'))
                            ->badge(),
                        TextEntry::make('sort_order')
                            ->label(__('segments.fields.sort_order')),
                        IconEntry::make('is_active')
                            ->label(__('segments.fields.is_active'))
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
                            ->visible(fn (Segment $record): bool => $record->trashed()),
                    ]),
            ]);
    }
}
