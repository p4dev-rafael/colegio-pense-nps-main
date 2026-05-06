<?php

declare(strict_types=1);

namespace App\Filament\Resources\Teachers\RelationManagers;

use App\Models\Segment;
use App\Models\Unit;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

final class SegmentTeachersRelationManager extends RelationManager
{
    protected static string $relationship = 'segmentTeachers';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('teachers.relation.segment_teachers_title');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('segment_id')
                    ->label(__('teachers.fields.segment_id'))
                    ->relationship('segment', 'name')
                    ->searchable()
                    ->preload()
                    ->live()
                    ->required(),
                Select::make('subject_id')
                    ->label(__('teachers.fields.subject_id'))
                    ->relationship(
                        name: 'subject',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn (Builder $query): Builder => $query->where('is_active', true)->orderBy('name'),
                    )
                    ->visible(function (Get $get): bool {
                        $segment = Segment::query()->find($get('segment_id'));

                        return $segment?->group->expectsSubjectTeachers() ?? false;
                    })
                    ->required(function (Get $get): bool {
                        $segment = Segment::query()->find($get('segment_id'));

                        return $segment?->group->expectsSubjectTeachers() ?? false;
                    }),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('unit.name')
                    ->label(__('enrollments.fields.unit_id')),
                TextColumn::make('segment.name')
                    ->label(__('teachers.fields.segment_id')),
                TextColumn::make('subject.name')
                    ->label(__('teachers.fields.subject_id'))
                    ->placeholder('—'),
                TextColumn::make('created_at')
                    ->label(__('common.fields.created_at'))
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                ]),
            ]);
    }
}
