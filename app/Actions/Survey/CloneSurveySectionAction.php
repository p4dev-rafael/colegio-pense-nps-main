<?php

declare(strict_types=1);

namespace App\Actions\Survey;

use App\Models\SurveyQuestion;
use App\Models\SurveySection;
use App\Support\Survey\SurveyQuestionCodeGenerator;
use Illuminate\Support\Facades\DB;

final class CloneSurveySectionAction
{
    public function execute(SurveySection $source, string $title): SurveySection
    {
        $source->load([
            'surveyQuestions' => fn ($query) => $query->ordered(),
        ]);

        return DB::transaction(function () use ($source, $title): SurveySection {
            SurveySection::query()
                ->withTrashed()
                ->where('survey_id', $source->survey_id)
                ->where('sort_order', '>', $source->sort_order)
                ->increment('sort_order');

            $clonedSection = SurveySection::query()->create([
                'survey_id' => $source->survey_id,
                'title' => $title,
                'description' => $source->description,
                'type' => $source->type,
                'sort_order' => $source->sort_order + 1,
                'is_active' => $source->is_active,
            ]);

            $codeGenerator = new SurveyQuestionCodeGenerator;

            foreach ($source->surveyQuestions as $question) {
                SurveyQuestion::query()->create([
                    'survey_section_id' => $clonedSection->id,
                    'code' => $codeGenerator->nextCode(),
                    'text' => $question->text,
                    'type' => $question->type,
                    'is_required' => $question->is_required,
                    'sort_order' => $question->sort_order,
                    'is_active' => $question->is_active,
                ]);
            }

            return $clonedSection->load([
                'surveyQuestions' => fn ($query) => $query->ordered(),
            ]);
        });
    }
}
