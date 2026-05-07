<?php

declare(strict_types=1);

namespace App\Filament\Resources\SurveyBatches;

use App\Filament\Resources\SurveyBatches\Pages\CreateSurveyBatch;
use App\Filament\Resources\SurveyBatches\Pages\EditSurveyBatch;
use App\Filament\Resources\SurveyBatches\Pages\ListSurveyBatches;
use App\Filament\Resources\SurveyBatches\Pages\ViewSurveyBatch;
use App\Filament\Resources\SurveyBatches\RelationManagers\SurveyResponsesRelationManager;
use App\Filament\Resources\SurveyBatches\Schemas\SurveyBatchForm;
use App\Filament\Resources\SurveyBatches\Schemas\SurveyBatchInfolist;
use App\Filament\Resources\SurveyBatches\Tables\SurveyBatchesTable;
use App\Models\SurveyBatch;
use BackedEnum;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

final class SurveyBatchResource extends Resource
{
    protected static ?string $model = SurveyBatch::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?int $navigationSort = 20;

    protected static ?string $tenantOwnershipRelationshipName = 'unit';

    protected static ?string $recordTitleAttribute = 'title';

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.groups.pesquisas');
    }

    public static function getModelLabel(): string
    {
        return __('survey_batches.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('survey_batches.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return SurveyBatchForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return SurveyBatchInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SurveyBatchesTable::configure($table);
    }

    /**
     * @return array<class-string<RelationManager>>
     */
    public static function getRelations(): array
    {
        return [
            SurveyResponsesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSurveyBatches::route('/'),
            'create' => CreateSurveyBatch::route('/create'),
            'view' => ViewSurveyBatch::route('/{record}'),
            'edit' => EditSurveyBatch::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
