<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Enums\UserRole;
use App\Filament\Widgets\NpsOverviewStatsWidget;
use App\Filament\Widgets\NpsSectionScoresChartWidget;
use App\Models\Segment;
use App\Models\Subject;
use App\Models\SurveyBatch;
use App\Models\Teacher;
use App\Models\User;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use UnitEnum;

final class NpsDashboard extends Page
{
    /**
     * @var array<string, mixed>
     */
    public array $filters = [
        'survey_batch_id' => null,
        'segment_id' => null,
        'subject_id' => null,
        'teacher_id' => null,
    ];

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?int $navigationSort = 10;

    public static function getNavigationLabel(): string
    {
        return __('nps_dashboard.navigation_label');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('navigation.groups.relatorios');
    }

    public function getTitle(): string|Htmlable
    {
        return __('nps_dashboard.title');
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && ($user->role === UserRole::Admin || $user->role === UserRole::Operator);
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                EmbeddedSchema::make('filters'),
                Grid::make(1)
                    ->schema(fn (): array => $this->getWidgetsSchemaComponents([
                        NpsOverviewStatsWidget::class,
                        NpsSectionScoresChartWidget::class,
                    ])),
            ]);
    }

    public function filters(Schema $schema): Schema
    {
        return $schema
            ->statePath('filters')
            ->components([
                Section::make(__('nps_dashboard.filters.section_heading'))
                    ->description(__('nps_dashboard.filters.section_description'))
                    ->schema([
                        Select::make('survey_batch_id')
                            ->label(__('nps_dashboard.filters.survey_batch'))
                            ->nullable()
                            ->native(false)
                            ->searchable()
                            ->preload()
                            ->live(onBlur: false)
                            ->placeholder(__('nps_dashboard.filters.placeholder_all_batches'))
                            ->options(fn (): array => $this->surveyBatchFilterOptions()),
                        Select::make('segment_id')
                            ->label(__('nps_dashboard.filters.segment'))
                            ->nullable()
                            ->native(false)
                            ->searchable()
                            ->preload()
                            ->live(onBlur: false)
                            ->placeholder(__('nps_dashboard.filters.placeholder_all_segments'))
                            ->options(fn (): array => Segment::query()
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all()),
                        Select::make('subject_id')
                            ->label(__('nps_dashboard.filters.subject'))
                            ->nullable()
                            ->native(false)
                            ->searchable()
                            ->preload()
                            ->live(onBlur: false)
                            ->placeholder(__('nps_dashboard.filters.placeholder_all_subjects'))
                            ->options(fn (): array => Subject::query()
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all()),
                        Select::make('teacher_id')
                            ->label(__('nps_dashboard.filters.teacher'))
                            ->nullable()
                            ->native(false)
                            ->searchable()
                            ->preload()
                            ->live(onBlur: false)
                            ->placeholder(__('nps_dashboard.filters.placeholder_all_teachers'))
                            ->options(fn (): array => $this->teacherFilterOptions()),
                    ])
                    ->columns(2),
            ]);
    }

    /**
     * @return array<string, string>
     */
    private function surveyBatchFilterOptions(): array
    {
        $tenant = Filament::getTenant();
        if ($tenant === null) {
            return [];
        }

        return SurveyBatch::query()
            ->where('unit_id', $tenant->getKey())
            ->orderByDesc('starts_at')
            ->limit(250)
            ->get()
            ->mapWithKeys(function (SurveyBatch $batch): array {
                $labelParts = [$batch->title];
                $statusLabel = $batch->status->getLabel();
                $labelParts[] = '('.$statusLabel.')';
                $label = implode(' ', $labelParts);

                return [$batch->getKey() => $label];
            })
            ->all();
    }

    /**
     * @return array<string, string>
     */
    private function teacherFilterOptions(): array
    {
        $tenant = Filament::getTenant();
        if ($tenant === null) {
            return [];
        }

        return Teacher::query()
            ->active()
            ->whereHas('units', fn ($relation) => $relation->where('units.id', $tenant->getKey()))
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }
}
