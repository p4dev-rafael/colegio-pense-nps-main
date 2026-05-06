<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\QuestionType;
use App\Models\Concerns\HasUuid;
use Database\Factories\SurveyQuestionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $id
 * @property string $survey_section_id
 * @property string $code
 * @property string $text
 * @property QuestionType $type
 * @property bool $is_required
 * @property int $sort_order
 * @property bool $is_active
 */
final class SurveyQuestion extends Model
{
    /** @use HasFactory<SurveyQuestionFactory> */
    use HasFactory, HasUuid, SoftDeletes;

    protected $fillable = [
        'survey_section_id',
        'code',
        'text',
        'type',
        'is_required',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'type' => QuestionType::class,
            'is_required' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<SurveySection, $this>
     */
    public function surveySection(): BelongsTo
    {
        return $this->belongsTo(SurveySection::class);
    }

    /**
     * @param  Builder<SurveyQuestion>  $query
     * @return Builder<SurveyQuestion>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order');
    }

    /**
     * @param  Builder<SurveyQuestion>  $query
     * @return Builder<SurveyQuestion>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
