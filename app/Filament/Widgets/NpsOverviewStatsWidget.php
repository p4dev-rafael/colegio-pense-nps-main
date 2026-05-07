<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\DTOs\NpsDashboardFiltersData;
use App\Models\Unit;
use App\Services\NpsAggregationService;
use App\Support\Nps\NpsAggregationResult;
use App\Support\Nps\NpsBuckets;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

final class NpsOverviewStatsWidget extends StatsOverviewWidget
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
        return __('nps_dashboard.widgets.overview.heading');
    }

    protected function getStats(): array
    {
        $result = $this->resolveAggregation();
        $n15 = $result->overallScale15->nps15();
        $n10 = $result->overallScale010->nps010();

        return [
            Stat::make(__('nps_dashboard.widgets.overview.completed_responses'), (string) $result->responsesCount)
                ->icon('heroicon-o-users')
                ->color('gray'),
            Stat::make(__('nps_dashboard.widgets.overview.nps_scale_15'), $n15 !== null ? sprintf('%+.1f', $n15) : '—')
                ->description(__('nps_dashboard.widgets.overview.scale_15_help'))
                ->icon('heroicon-o-star')
                ->color($n15 !== null && $n15 >= 0 ? 'success' : 'danger'),
            Stat::make(__('nps_dashboard.widgets.overview.nps_scale_010'), $n10 !== null ? sprintf('%+.1f', $n10) : '—')
                ->description(__('nps_dashboard.widgets.overview.scale_010_help'))
                ->icon('heroicon-o-chart-bar')
                ->color($n10 !== null && $n10 >= 0 ? 'success' : 'danger'),
        ];
    }

    public function updatedPageFilters(): void
    {
        $this->cachedStats = null;
    }

    private function resolveAggregation(): NpsAggregationResult
    {
        $tenant = Filament::getTenant();
        if (! $tenant instanceof Unit) {
            return new NpsAggregationResult(
                responsesCount: 0,
                overallScale15: new NpsBuckets,
                overallScale010: new NpsBuckets,
                scale15BySection: [],
            );
        }

        $filters = NpsDashboardFiltersData::fromLivewireFilters($this->pageFilters);

        return app(NpsAggregationService::class)->aggregate($tenant, $filters);
    }
}
