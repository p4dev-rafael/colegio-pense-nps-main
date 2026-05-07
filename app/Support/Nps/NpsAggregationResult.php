<?php

declare(strict_types=1);

namespace App\Support\Nps;

/**
 * Aggregate NPS payloads for dashboards (overall + per-section buckets for scales).
 */
final readonly class NpsAggregationResult
{
    /**
     * @param  array<string, NpsBuckets>  $scale15BySection
     */
    public function __construct(
        public int $responsesCount,
        public NpsBuckets $overallScale15,
        public NpsBuckets $overallScale010,
        public array $scale15BySection,
    ) {}

    /**
     * @return list<array{key: string, label: string, nps_15: ?float}>
     */
    public function sectionSummaries(callable $labelResolver): array
    {
        $rows = [];

        foreach ($this->orderedSectionKeysForScale15Breakdown() as $key) {
            $bucket = $this->scale15BySection[$key] ?? new NpsBuckets;
            $rows[] = [
                'key' => $key,
                'label' => $labelResolver($key),
                'nps_15' => $bucket->nps15(),
            ];
        }

        return $rows;
    }

    /**
     * Sections S1–S8 use scale 1–5; section 9 is final NPS scale 0–10 only for overall010.
     *
     * @return list<string>
     */
    public function orderedSectionKeysForScale15Breakdown(): array
    {
        return ['S1', 'S2', 'S3', 'S4', 'S5', 'S6', 'S7', 'S8'];
    }
}
