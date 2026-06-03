<?php

declare(strict_types=1);

namespace App\Livewire\Survey;

use App\Actions\Survey\CompleteSurveyResponseAction;
use App\DTOs\SurveyResponseData;
use App\Enums\QuestionType;
use App\Enums\RespondentType;
use App\Enums\SectionType;
use App\Exceptions\Survey\SurveyException;
use App\Models\Enrollment;
use App\Models\SegmentTeacher;
use App\Models\SurveyBatch;
use App\Services\EnrollmentResolverService;
use App\Services\SurveyBatchLinkService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Symfony\Component\HttpFoundation\Response;

#[Layout('layouts.survey')]
final class PublicSurvey extends Component
{
    public string $token = '';

    public ?SurveyBatch $batch = null;

    public string $registrationCode = '';

    public ?string $identificationError = null;

    public bool $identified = false;

    public bool $submitted = false;

    public ?string $submitError = null;

    public ?string $enrollmentId = null;

    public ?string $respondentName = null;

    public string $respondentType = '';

    public ?string $segmentName = null;

    public ?string $unitName = null;

    /**
     * Section answers for non-teacher sections.
     *
     * @var array<string, array<string, mixed>>
     */
    public array $sectionAnswers = [];

    /**
     * Teacher answers (S1) keyed by teacher pivot id.
     *
     * @var array<string, array<string, mixed>>
     */
    public array $teacherAnswers = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    public array $sectionsView = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    public array $teacherSlots = [];

    public function mount(string $token, SurveyBatchLinkService $linkService): void
    {
        $this->token = $token;

        try {
            $this->batch = $linkService->resolveByToken($token);
        } catch (SurveyException) {
            abort(Response::HTTP_NOT_FOUND);
        }
    }

    public function identify(EnrollmentResolverService $resolver): void
    {
        $this->identificationError = null;

        if ($this->batch === null) {
            return;
        }

        if (! $this->batch->isAcceptingResponses()) {
            $this->identificationError = __('survey.errors.batch_not_accepting_responses');

            return;
        }

        $code = trim($this->registrationCode);
        if ($code === '') {
            if (! $this->batch->requires_identification) {
                $this->bootstrapAnonymousForm();

                return;
            }

            $this->identificationError = __('survey.errors.invalid_registration_code');

            return;
        }

        try {
            $enrollment = $resolver->resolveForPublicSurvey(
                registrationCode: $code,
                unitId: $this->batch->unit_id,
            );
        } catch (SurveyException $e) {
            $this->identificationError = $e->userMessage();

            return;
        }

        $this->bootstrapForm($enrollment);
    }

    public function submit(CompleteSurveyResponseAction $action, Request $request): void
    {
        $this->submitError = null;

        if ($this->batch === null || ! $this->identified) {
            return;
        }

        $enrollment = null;
        if ($this->enrollmentId !== null) {
            $enrollment = Enrollment::query()
                ->with(['student', 'segment'])
                ->find($this->enrollmentId);

            if ($enrollment === null) {
                $this->submitError = __('survey.errors.invalid_registration_code');

                return;
            }
        } elseif ($this->batch->requires_identification) {
            return;
        }

        $sections = $this->buildSectionsPayload();

        $missing = $this->findMissingRequired();
        if ($missing !== []) {
            $this->submitError = __('survey.errors.required_question');

            return;
        }

        $data = SurveyResponseData::fromSections(
            sections: $sections,
            ipAddress: $request->ip(),
            userAgent: substr((string) $request->userAgent(), 0, 500),
        );

        try {
            $action->execute($data, $enrollment, $this->batch);
        } catch (SurveyException $e) {
            $this->submitError = $e->userMessage();

            return;
        }

        $this->submitted = true;
    }

    public function render(): View
    {
        return view('livewire.survey.public-survey');
    }

    private function bootstrapForm(Enrollment $enrollment): void
    {
        $this->enrollmentId = $enrollment->id;
        $this->respondentName = $enrollment->student->name;
        $this->segmentName = $enrollment->segment->name;
        $this->unitName = $enrollment->unit->name;

        $type = RespondentType::fromSegmentGroup($enrollment->segment->group);
        $this->respondentType = $type->value;
        $this->respondentName = $type === RespondentType::Guardian
            ? ($enrollment->student->guardian_name ?? $enrollment->student->name)
            : $enrollment->student->name;

        $this->loadTemplate($enrollment);

        $this->identified = true;
    }

    private function bootstrapAnonymousForm(): void
    {
        if ($this->batch === null) {
            return;
        }

        $this->batch->loadMissing('unit');

        $this->enrollmentId = null;
        $this->respondentName = __('survey.public.anonymous_respondent');
        $this->respondentType = RespondentType::Anonymous->value;
        $this->segmentName = __('survey.public.form.segment_not_applicable');
        $this->unitName = $this->batch->unit?->name ?? '—';

        $this->loadTemplate(null);

        $this->identified = true;
    }

    private function loadTemplate(?Enrollment $enrollment): void
    {
        $survey = $this->batch?->survey()->with([
            'surveySections' => fn ($q) => $q
                ->where('is_active', true)
                ->orderBy('sort_order'),
            'surveySections.surveyQuestions' => fn ($q) => $q
                ->where('is_active', true)
                ->orderBy('sort_order'),
        ])->first();

        if ($survey === null) {
            $this->sectionsView = [];

            return;
        }

        $sectionsView = [];
        $sectionAnswers = [];

        foreach ($survey->surveySections as $section) {
            $sectionKey = sprintf('S%d', $section->sort_order);

            $questions = [];
            foreach ($section->surveyQuestions as $question) {
                $questions[] = [
                    'id' => $question->id,
                    'code' => $question->code,
                    'text' => $question->text,
                    'type' => $question->type->value,
                    'is_required' => $question->is_required,
                ];
                $sectionAnswers[$sectionKey][$question->code] = '';
            }

            $sectionsView[] = [
                'id' => $section->id,
                'key' => $sectionKey,
                'title' => $section->title,
                'description' => $section->description,
                'type' => $section->type->value,
                'sort_order' => $section->sort_order,
                'questions' => $questions,
            ];
        }

        $this->sectionsView = $sectionsView;
        $this->sectionAnswers = $sectionAnswers;

        $this->teacherSlots = $this->usesPerTeacherEvaluation() && $enrollment !== null
            ? $this->buildTeacherSlots($enrollment)
            : [];
        $this->teacherAnswers = $this->initializeTeacherAnswers();
    }

    private function usesPerTeacherEvaluation(): bool
    {
        return $this->batch?->requires_identification ?? true;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildTeacherSlots(Enrollment $enrollment): array
    {
        $teachersSection = collect($this->sectionsView)
            ->firstWhere('type', SectionType::Teachers->value);

        if ($teachersSection === null) {
            return [];
        }

        $links = SegmentTeacher::query()
            ->with(['teacher', 'subject'])
            ->where('unit_id', $enrollment->unit_id)
            ->where('segment_id', $enrollment->segment_id)
            ->whereHas('teacher', fn ($q) => $q->where('is_active', true))
            ->get();

        $slots = [];
        foreach ($links as $link) {
            $slots[] = [
                'segment_teacher_id' => $link->id,
                'teacher_id' => $link->teacher_id,
                'subject_id' => $link->subject_id,
                'teacher_name' => $link->teacher?->name ?? '—',
                'subject_name' => $link->subject?->name,
            ];
        }

        return $slots;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function initializeTeacherAnswers(): array
    {
        $teachersSection = collect($this->sectionsView)
            ->firstWhere('type', SectionType::Teachers->value);

        if ($teachersSection === null) {
            return [];
        }

        $answers = [];
        foreach ($this->teacherSlots as $slot) {
            $entry = [];
            foreach ($teachersSection['questions'] as $question) {
                $entry[$question['code']] = '';
            }
            $answers[$slot['segment_teacher_id']] = $entry;
        }

        return $answers;
    }

    /**
     * Builds the sections payload for the answers JSON.
     *
     * @return array<string, array<string, mixed>>
     */
    private function buildSectionsPayload(): array
    {
        $payload = [];

        foreach ($this->sectionsView as $section) {
            $sectionKey = $section['key'];
            $isTeachers = $section['type'] === SectionType::Teachers->value
                && $this->usesPerTeacherEvaluation();

            if ($isTeachers) {
                $teachers = [];
                foreach ($this->teacherSlots as $slot) {
                    $entries = $this->teacherAnswers[$slot['segment_teacher_id']] ?? [];
                    $questions = [];
                    foreach ($section['questions'] as $question) {
                        $value = $entries[$question['code']] ?? null;
                        $questions[$question['code']] = ['value' => $this->castAnswer($question['type'], $value)];
                    }

                    $teachers[$slot['teacher_id']] = [
                        'subject_id' => $slot['subject_id'],
                        'teacher_name' => $slot['teacher_name'],
                        'questions' => $questions,
                    ];
                }

                $payload[$sectionKey] = ['teachers' => $teachers];

                continue;
            }

            $questions = [];
            foreach ($section['questions'] as $question) {
                $value = $this->sectionAnswers[$sectionKey][$question['code']] ?? null;
                $questions[$question['code']] = ['value' => $this->castAnswer($question['type'], $value)];
            }

            $payload[$sectionKey] = ['questions' => $questions];
        }

        return $payload;
    }

    /**
     * @return list<string>
     */
    private function findMissingRequired(): array
    {
        $missing = [];

        foreach ($this->sectionsView as $section) {
            $sectionKey = $section['key'];
            $isTeachers = $section['type'] === SectionType::Teachers->value
                && $this->usesPerTeacherEvaluation();

            foreach ($section['questions'] as $question) {
                if (! $question['is_required']) {
                    continue;
                }

                if ($isTeachers) {
                    foreach ($this->teacherSlots as $slot) {
                        $value = $this->teacherAnswers[$slot['segment_teacher_id']][$question['code']] ?? null;
                        if ($this->isEmptyValue($question['type'], $value)) {
                            $missing[] = sprintf('%s.%s.%s', $sectionKey, $slot['segment_teacher_id'], $question['code']);
                        }
                    }

                    continue;
                }

                $value = $this->sectionAnswers[$sectionKey][$question['code']] ?? null;
                if ($this->isEmptyValue($question['type'], $value)) {
                    $missing[] = sprintf('%s.%s', $sectionKey, $question['code']);
                }
            }
        }

        return $missing;
    }

    private function castAnswer(string $type, mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        return match ($type) {
            QuestionType::Scale1to5->value => $value === 'nsa' ? 'nsa' : (int) $value,
            QuestionType::Scale0to10->value => (int) $value,
            QuestionType::FreeText->value => (string) $value,
            default => $value,
        };
    }

    private function isEmptyValue(string $type, mixed $value): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        if ($type === QuestionType::FreeText->value) {
            return trim((string) $value) === '';
        }

        return false;
    }
}
