<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\DTOs\NpsDashboardFiltersData;
use App\Models\SurveySection;
use App\Models\Unit;
use App\Services\NpsAggregationService;
use Filament\Facades\Filament;
use Filament\Widgets\ChartWidget;

final class NpsSectionScoresChartWidget extends ChartWidget
{
    protected static bool $isDiscovered = false;

    protected ?string $pollingInterval = '90s';

    protected int|string|array $columnSpan = 'full';

    /**
     * @var array<string, mixed>
     */
    public array $pageFilters = [];

    public function getHeading(): ?string
    {
        return __('nps_dashboard.widgets.sections_chart.heading');
    }

    public function getDescription(): ?string
    {
        return __('nps_dashboard.widgets.sections_chart.description');
    }

    public function updatedPageFilters(): void
    {
        $this->cachedData = null;
    }

    protected function getType(): string
    {
        return 'bar';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getData(): array
    {
        $tenant = Filament::getTenant();
        if (! $tenant instanceof Unit) {
            return ['labels' => [], 'datasets' => []];
        }

        $filters = NpsDashboardFiltersData::fromLivewireFilters($this->pageFilters);
        $result = app(NpsAggregationService::class)->aggregate($tenant, $filters);
        $titles = $this->sectionTitleMap();

        $summaries = $result->sectionSummaries(fn (string $key): string => $titles[$key] ?? $key);

        $labels = array_map(fn (array $row): string => (string) $row['label'], $summaries);
        $values = array_map(
            fn (array $row): float => $row['nps_15'] !== null ? round((float) $row['nps_15'], 1) : 0.0,
            $summaries,
        );

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => __('nps_dashboard.widgets.sections_chart.dataset'),
                    'data' => $values,
                    'borderColor' => '#2563EB',
                    'backgroundColor' => 'rgba(37, 99, 235, 0.42)',
                ],
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function sectionTitleMap(): array
    {
        return SurveySection::query()
            ->whereHas('survey', fn ($q) => $q->where('is_active', true))
            ->orderBy('sort_order')
            ->get()
            ->mapWithKeys(fn (SurveySection $section): array => [
                sprintf('S%d', $section->sort_order) => $section->title,
            ])
            ->all();
    }
}
