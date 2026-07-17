<?php

declare(strict_types=1);

namespace App\Support\Survey\Pdf;

use App\Models\SurveyBatch;
use App\Support\Nps\NpsAggregationResult;

final readonly class SurveyBatchPdfReportData
{
    /**
     * @param  list<array{key: string, label: string, nps_15: ?float}>  $sectionSummaries
     * @param  list<SurveyBatchPdfQuestionAverage>  $questionAverages
     * @param  list<SurveyBatchPdfTeacherAverage>  $teacherAverages
     * @param  list<SurveyBatchPdfFreeTextEntry>  $freeTextComments
     * @param  list<SurveyBatchPdfResponseEntry>  $responses
     */
    public function __construct(
        public SurveyBatch $batch,
        public NpsAggregationResult $aggregation,
        public array $sectionSummaries,
        public array $questionAverages,
        public array $teacherAverages,
        public array $freeTextComments,
        public array $responses,
        public string $generatedAt,
    ) {}
}
