<?php

declare(strict_types=1);

namespace App\Filament\Resources\Students\RelationManagers;

use App\Models\Unit;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

final class EnrollmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'enrollments';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('students.relation.enrollments_title');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('common.sections.general'))
                    ->schema([
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

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('registration_code')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]))
            ->columns([
                TextColumn::make('unit.name')
                    ->label(__('enrollments.fields.unit_id')),
                TextColumn::make('segment.name')
                    ->label(__('enrollments.fields.segment_id')),
                TextColumn::make('registration_code')
                    ->label(__('enrollments.fields.registration_code'))
                    ->searchable(),
                TextColumn::make('year')
                    ->label(__('enrollments.fields.year'))
                    ->numeric()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label(__('enrollments.fields.is_active'))
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label(__('common.fields.created_at'))
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $tenant = Filament::getTenant();
                        if ($tenant instanceof Unit) {
                            $data['unit_id'] = $tenant->getKey();
                        }

                        return $data;
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
