<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SectionType;
use App\Models\Concerns\HasUuid;
use Database\Factories\SurveySectionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $id
 * @property string $survey_id
 * @property string $title
 * @property string|null $description
 * @property SectionType $type
 * @property int $sort_order
 * @property bool $is_active
 */
final class SurveySection extends Model
{
    /** @use HasFactory<SurveySectionFactory> */
    use HasFactory, HasUuid, SoftDeletes;

    protected $fillable = [
        'survey_id',
        'title',
        'description',
        'type',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'type' => SectionType::class,
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Survey, $this>
     */
    public function survey(): BelongsTo
    {
        return $this->belongsTo(Survey::class);
    }

    /**
     * @return HasMany<SurveyQuestion, $this>
     */
    public function surveyQuestions(): HasMany
    {
        return $this->hasMany(SurveyQuestion::class);
    }

    /**
     * @param  Builder<SurveySection>  $query
     * @return Builder<SurveySection>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order');
    }

    /**
     * @param  Builder<SurveySection>  $query
     * @return Builder<SurveySection>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
