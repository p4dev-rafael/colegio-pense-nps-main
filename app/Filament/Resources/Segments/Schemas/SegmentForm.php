<?php

declare(strict_types=1);

namespace App\Filament\Resources\Segments\Schemas;

use App\Enums\SegmentGroup;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

final class SegmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('common.sections.general'))
                    ->schema([
                        TextInput::make('name')
                            ->label(__('segments.fields.name'))
                            ->required()
                            ->maxLength(50)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (?string $state, callable $set): void {
                                if (filled($state)) {
                                    $set('slug', Str::slug($state));
                                }
                            }),
                        TextInput::make('slug')
                            ->label(__('segments.fields.slug'))
                            ->required()
                            ->maxLength(100)
                            ->unique(
                                table: 'segments',
                                column: 'slug',
                                ignorable: null,
                                ignoreRecord: true,
                                modifyRuleUsing: fn (\Illuminate\Validation\Rules\Unique $rule) => $rule->whereNull('deleted_at'),
                            ),
                        Select::make('group')
                            ->label(__('segments.fields.group'))
                            ->options(SegmentGroup::class)
                            ->required(),
                        TextInput::make('sort_order')
                            ->label(__('segments.fields.sort_order'))
                            ->required()
                            ->numeric()
                            ->default(0),
                        Toggle::make('is_active')
                            ->label(__('segments.fields.is_active'))
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }
}
