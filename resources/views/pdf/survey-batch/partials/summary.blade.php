@php
    $batch = $report->batch;
    $nps15 = $report->aggregation->overallScale15->nps15();
    $nps010 = $report->aggregation->overallScale010->nps010();
@endphp

<header class="report-header">
    <p class="report-header__eyebrow">{{ __('survey_batches.pdf.title') }}</p>
    <h1 class="report-header__title">{{ $batch->title }}</h1>
    <div class="report-header__meta">
        <p><strong>{{ __('survey_batches.fields.unit_id') }}:</strong> {{ $batch->unit?->name ?? '—' }}</p>
        <p><strong>{{ __('survey_batches.fields.survey_id') }}:</strong> {{ $batch->survey?->title ?? '—' }}</p>
        <p><strong>{{ __('survey_batches.fields.status') }}:</strong> {{ $batch->status?->getLabel() ?? '—' }}</p>
        <p><strong>{{ __('survey_batches.pdf.generated_at') }}:</strong> {{ $report->generatedAt }}</p>
        @if ($batch->starts_at || $batch->ends_at)
            <p><strong>{{ __('survey_batches.sections.period') }}:</strong>
                {{ $batch->starts_at?->translatedFormat('d/m/Y') ?? '—' }}
                —
                {{ $batch->ends_at?->translatedFormat('d/m/Y') ?? '—' }}
            </p>
        @endif
    </div>
</header>

<section>
    <h2 class="section-heading">{{ __('survey_batches.pdf.summary') }}</h2>

    <div class="kpi-grid">
        <div class="kpi">
            <p class="kpi__label">{{ __('survey_batches.pdf.responses_count') }}</p>
            <p class="kpi__value">{{ $report->aggregation->responsesCount }}</p>
        </div>
        <div class="kpi">
            <p class="kpi__label">{{ __('survey_batches.pdf.nps_scale_15') }}</p>
            <p class="kpi__value @if($nps15 === null) kpi__value--muted @endif">
                {{ $nps15 !== null ? sprintf('%+.1f', $nps15) : '—' }}
            </p>
        </div>
        <div class="kpi">
            <p class="kpi__label">{{ __('survey_batches.pdf.nps_scale_010') }}</p>
            <p class="kpi__value @if($nps010 === null) kpi__value--muted @endif">
                {{ $nps010 !== null ? sprintf('%+.1f', $nps010) : '—' }}
            </p>
        </div>
    </div>

    <div class="two-col">
        <div class="card card--accent">
            <h3 class="card__title">{{ __('survey_batches.pdf.nps_scale_15') }}</h3>
            @include('pdf.survey-batch.partials.charts.nps-composition', [
                'buckets' => $report->aggregation->overallScale15,
                'showNeutrals' => false,
            ])
        </div>
        <div class="card card--accent">
            <h3 class="card__title">{{ __('survey_batches.pdf.nps_scale_010') }}</h3>
            @include('pdf.survey-batch.partials.charts.nps-composition', [
                'buckets' => $report->aggregation->overallScale010,
                'showNeutrals' => true,
            ])
        </div>
    </div>

    @if (count($report->sectionSummaries) > 0)
        <div class="card" style="margin-top: 10px;">
            <h3 class="card__title">{{ __('survey_batches.pdf.section_nps') }}</h3>
            @include('pdf.survey-batch.partials.charts.section-bars', [
                'sections' => $report->sectionSummaries,
            ])
        </div>
    @endif

    @if (count($report->questionAverages) > 0)
        <div class="card">
            <h3 class="card__title">{{ __('survey_batches.pdf.question_averages') }}</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>{{ __('survey_batches.fields.survey_id') }}</th>
                        <th>{{ __('survey_batches.pdf.question') }}</th>
                        <th class="num">{{ __('survey_batches.pdf.average') }}</th>
                        <th class="num">n</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($report->questionAverages as $row)
                        <tr>
                            <td>{{ $row->sectionTitle }}</td>
                            <td>{{ $row->questionText }}</td>
                            <td class="num">{{ $row->average !== null ? number_format($row->average, 2, ',', '.') : '—' }}</td>
                            <td class="num">{{ $row->count }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if (count($report->teacherAverages) > 0)
        <div class="card">
            <h3 class="card__title">{{ __('survey_batches.pdf.teacher_averages') }}</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>{{ __('survey_batches.pdf.teacher') }}</th>
                        <th>{{ __('survey_batches.pdf.question') }}</th>
                        <th class="num">{{ __('survey_batches.pdf.average') }}</th>
                        <th class="num">n</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($report->teacherAverages as $row)
                        <tr>
                            <td>{{ $row->teacherName }}</td>
                            <td>{{ $row->questionText }}</td>
                            <td class="num">{{ $row->average !== null ? number_format($row->average, 2, ',', '.') : '—' }}</td>
                            <td class="num">{{ $row->count }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if (count($report->freeTextComments) > 0)
        <div class="card">
            <h3 class="card__title">{{ __('survey_batches.pdf.free_text_comments') }}</h3>
            @foreach ($report->freeTextComments as $comment)
                <div class="comment">
                    <p class="comment__meta">
                        <strong>{{ $comment->questionText }}</strong>
                        · {{ $comment->respondentLabel }}
                    </p>
                    <p class="comment__text">{{ $comment->text }}</p>
                </div>
            @endforeach
        </div>
    @endif

    @if ($report->aggregation->responsesCount === 0)
        <p class="empty-state">{{ __('survey_batches.pdf.no_responses') }}</p>
    @endif
</section>

@if (count($report->responses) > 0)
    <p class="page-break section-heading">{{ __('survey_batches.pdf.responses') }}</p>
@endif
