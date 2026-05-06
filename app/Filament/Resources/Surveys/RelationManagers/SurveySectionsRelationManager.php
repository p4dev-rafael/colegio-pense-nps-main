<?php

declare(strict_types=1);

namespace App\Filament\Resources\Surveys\RelationManagers;

use App\Enums\QuestionType;
use App\Enums\SectionType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

final class SurveySectionsRelationManager extends RelationManager
{
    protected static string $relationship = 'surveySections';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('surveys.sections.survey_sections_title');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('common.sections.general'))
                    ->schema([
                        TextInput::make('title')
                            ->label(__('surveys.section_fields.title'))
                            ->required()
                            ->maxLength(100),
                        Select::make('type')
                            ->label(__('surveys.section_fields.type'))
                            ->options(SectionType::class)
                            ->required(),
                        TextInput::make('sort_order')
                            ->label(__('surveys.section_fields.sort_order'))
                            ->numeric()
                            ->required()
                            ->default(0),
                        Toggle::make('is_active')
                            ->label(__('surveys.section_fields.is_active'))
                            ->default(true),
                        Textarea::make('description')
                            ->label(__('surveys.section_fields.description'))
                            ->rows(2)
                            ->maxLength(2000)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make(__('common.sections.questions'))
                    ->schema([
                        Repeater::make('surveyQuestions')
                            ->relationship()
                            ->label('')
                            ->orderColumn('sort_order')
                            ->reorderable()
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => isset($state['code'])
                                ? sprintf('%s — %s', $state['code'], (string) ($state['text'] ?? ''))
                                : null,
                            )
                            ->addActionLabel(__('surveys.sections.survey_questions_title'))
                            ->schema([
                                TextInput::make('code')
                                    ->label(__('surveys.question_fields.code'))
                                    ->required()
                                    ->maxLength(30)
                                    ->unique(
                                        table: 'survey_questions',
                                        column: 'code',
                                        ignoreRecord: true,
                                        modifyRuleUsing: fn (\Illuminate\Validation\Rules\Unique $rule) => $rule->whereNull('deleted_at'),
                                    ),
                                Select::make('type')
                                    ->label(__('surveys.question_fields.type'))
                                    ->options(QuestionType::class)
                                    ->required()
                                    ->default(QuestionType::Scale1to5->value),
                                Textarea::make('text')
                                    ->label(__('surveys.question_fields.text'))
                                    ->required()
                                    ->rows(2)
                                    ->maxLength(2000)
                                    ->columnSpanFull(),
                                TextInput::make('sort_order')
                                    ->label(__('surveys.question_fields.sort_order'))
                                    ->numeric()
                                    ->required()
                                    ->default(0),
                                Toggle::make('is_required')
                                    ->label(__('surveys.question_fields.is_required'))
                                    ->default(true),
                                Toggle::make('is_active')
                                    ->label(__('surveys.question_fields.is_active'))
                                    ->default(true),
                            ])
                            ->columns(2),
                    ])
                    ->collapsible(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]))
            ->columns([
                TextColumn::make('sort_order')
                    ->label(__('surveys.section_fields.sort_order'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('title')
                    ->label(__('surveys.section_fields.title'))
                    ->searchable(),
                TextColumn::make('type')
                    ->label(__('surveys.section_fields.type'))
                    ->badge(),
                TextColumn::make('survey_questions_count')
                    ->label(__('surveys.section_fields.questions_count'))
                    ->counts('surveyQuestions'),
                IconColumn::make('is_active')
                    ->label(__('surveys.section_fields.is_active'))
                    ->boolean(),
            ])
            ->defaultSort('sort_order')
            ->filters([
                SelectFilter::make('type')
                    ->label(__('surveys.section_fields.type'))
                    ->options(SectionType::class),
                TrashedFilter::make(),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
                ForceDeleteAction::make(),
                RestoreAction::make(),
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
