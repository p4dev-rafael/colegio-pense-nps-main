<?php

declare(strict_types=1);

namespace App\Actions\Survey;

use App\Models\Survey;
use App\Models\SurveyQuestion;
use App\Models\SurveySection;
use App\Support\Survey\SurveyQuestionCodeGenerator;
use Illuminate\Support\Facades\DB;

final class CloneSurveyAction
{
    public function execute(Survey $source, string $title): Survey
    {
        $source->load([
            'surveySections' => fn ($query) => $query->ordered(),
            'surveySections.surveyQuestions' => fn ($query) => $query->ordered(),
        ]);

        return DB::transaction(function () use ($source, $title): Survey {
            $clone = Survey::query()->create([
                'title' => $title,
                'description' => $source->description,
                'is_active' => $source->is_active,
            ]);

            $codeGenerator = new SurveyQuestionCodeGenerator;

            foreach ($source->surveySections as $section) {
                $clonedSection = SurveySection::query()->create([
                    'survey_id' => $clone->id,
                    'title' => $section->title,
                    'description' => $section->description,
                    'type' => $section->type,
                    'sort_order' => $section->sort_order,
                    'is_active' => $section->is_active,
                ]);

                foreach ($section->surveyQuestions as $question) {
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
            }

            return $clone->load(['surveySections.surveyQuestions']);
        });
    }
}
