<header class="report-header">
    <p class="report-header__eyebrow">{{ __('survey_batches.pdf.response') }} #{{ $index }}</p>
    <h1 class="report-header__title">{{ $response->respondentLabel }}</h1>
    <div class="response-meta">
        @if ($response->respondentTypeLabel)
            <p><strong>{{ __('survey_batches.pdf.respondent_type') }}:</strong> {{ $response->respondentTypeLabel }}</p>
        @endif
        @if ($response->segmentName)
            <p><strong>{{ __('survey_batches.pdf.segment') }}:</strong> {{ $response->segmentName }}</p>
        @endif
        @if ($response->registrationCode)
            <p><strong>{{ __('survey_batches.pdf.registration') }}:</strong> {{ $response->registrationCode }}</p>
        @endif
        @if ($response->completedAt)
            <p><strong>{{ __('survey_batches.pdf.completed_at') }}:</strong> {{ $response->completedAt }}</p>
        @endif
    </div>
</header>

@foreach ($response->sections as $section)
    <section class="card">
        <h3 class="card__title">{{ $section->title }}</h3>
        @if ($section->description)
            <p style="color: var(--muted); font-size: 8.5pt; margin-bottom: 8px;">{{ $section->description }}</p>
        @endif

        @foreach ($section->questions as $question)
            <div class="qa-row @if($question->isFreeText) qa-row--text @endif">
                <p class="qa-row__label">{{ $question->label }}</p>
                <p class="qa-row__value">{{ $question->value }}</p>
            </div>
        @endforeach

        @foreach ($section->teachers as $teacher)
            <div class="teacher-card">
                <h4 class="teacher-card__name">{{ $teacher->name }}</h4>
                @foreach ($teacher->questions as $question)
                    <div class="qa-row @if($question->isFreeText) qa-row--text @endif">
                        <p class="qa-row__label">{{ $question->label }}</p>
                        <p class="qa-row__value">{{ $question->value }}</p>
                    </div>
                @endforeach
            </div>
        @endforeach
    </section>
@endforeach
