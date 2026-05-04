<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\UserRole;
use Filament\Facades\Filament;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Unique;

final class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('common.sections.general'))
                    ->schema([
                        TextInput::make('name')
                            ->label(__('users.fields.name'))
                            ->required()
                            ->maxLength(100),
                        TextInput::make('email')
                            ->label(__('users.fields.email'))
                            ->email()
                            ->required()
                            ->maxLength(254)
                            ->unique(
                                table: 'users',
                                column: 'email',
                                ignorable: null,
                                ignoreRecord: true,
                                modifyRuleUsing: fn (Unique $rule): Unique => $rule->whereNull('deleted_at'),
                            ),
                        Select::make('role')
                            ->label(__('users.fields.role'))
                            ->options(UserRole::class)
                            ->default(UserRole::Operator)
                            ->required(),
                        Toggle::make('is_active')
                            ->label(__('users.fields.is_active'))
                            ->default(true),
                    ])
                    ->columns(2),
                Section::make(__('common.sections.access'))
                    ->schema([
                        TextInput::make('password')
                            ->label(__('users.fields.password'))
                            ->password()
                            ->revealable()
                            ->minLength(8)
                            ->confirmed()
                            ->required(fn ($livewire): bool => $livewire instanceof CreateRecord)
                            ->dehydrated(fn (?string $state): bool => filled($state)),
                        TextInput::make('password_confirmation')
                            ->label(__('users.fields.password_confirmation'))
                            ->password()
                            ->revealable()
                            ->required(fn ($livewire): bool => $livewire instanceof CreateRecord)
                            ->dehydrated(false),
                    ])
                    ->columns(2),
                Section::make(__('common.sections.units'))
                    ->schema([
                        CheckboxList::make('units')
                            ->label(__('users.fields.units'))
                            ->relationship('units', 'name')
                            ->columns(2)
                            ->required()
                            ->default(fn (): array => Filament::getTenant() ? [Filament::getTenant()->getKey()] : [])
                            ->rules(['array', 'min:1']),
                    ]),
            ]);
    }
}
