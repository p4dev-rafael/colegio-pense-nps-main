<?php

declare(strict_types=1);

namespace App\Filament\Resources\Enrollments\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

final class EnrollmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('common.sections.general'))
                    ->schema([
                        Select::make('student_id')
                            ->label(__('enrollments.fields.student_id'))
                            ->relationship(
                                name: 'student',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn (Builder $query): Builder => $query->where('is_active', true)->orderBy('name'),
                            )
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('segment_id')
                            ->label(__('enrollments.fields.segment_id'))
                            ->relationship(
                                name: 'segment',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn (Builder $query): Builder => $query->where('is_active', true)->orderBy('sort_order'),
                            )
                            ->searchable()
                            ->preload()
                            ->required(),
                        TextInput::make('registration_code')
                            ->label(__('enrollments.fields.registration_code'))
                            ->required()
                            ->maxLength(30),
                        TextInput::make('year')
                            ->label(__('enrollments.fields.year'))
                            ->required()
                            ->numeric()
                            ->default(now()->year),
                        Toggle::make('is_active')
                            ->label(__('enrollments.fields.is_active'))
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }
}
