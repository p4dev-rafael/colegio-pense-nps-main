<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RespondentType;
use App\Models\Concerns\HasUuid;
use Database\Factories\SurveyResponseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $survey_batch_id
 * @property string|null $enrollment_id
 * @property string $unit_id
 * @property string|null $segment_id
 * @property RespondentType $respondent_type
 * @property string $respondent_name
 * @property array<string, mixed> $answers
 * @property bool $is_completed
 * @property Carbon|null $completed_at
 * @property string|null $ip_address
 * @property string|null $user_agent
 */
final class SurveyResponse extends Model
{
    /** @use HasFactory<SurveyResponseFactory> */
    use HasFactory, HasUuid, SoftDeletes;

    protected $fillable = [
        'survey_batch_id',
        'enrollment_id',
        'unit_id',
        'segment_id',
        'respondent_type',
        'respondent_name',
        'answers',
        'is_completed',
        'completed_at',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'respondent_type' => RespondentType::class,
            'answers' => 'array',
            'is_completed' => 'boolean',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<SurveyBatch, $this>
     */
    public function surveyBatch(): BelongsTo
    {
        return $this->belongsTo(SurveyBatch::class);
    }

    /**
     * @return BelongsTo<Enrollment, $this>
     */
    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    /**
     * @return BelongsTo<Unit, $this>
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * @return BelongsTo<Segment, $this>
     */
    public function segment(): BelongsTo
    {
        return $this->belongsTo(Segment::class);
    }

    /**
     * @param  Builder<SurveyResponse>  $query
     * @return Builder<SurveyResponse>
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('is_completed', true);
    }

    /**
     * @param  Builder<SurveyResponse>  $query
     * @return Builder<SurveyResponse>
     */
    public function scopeForBatch(Builder $query, string $batchId): Builder
    {
        return $query->where('survey_batch_id', $batchId);
    }

    /**
     * @param  Builder<SurveyResponse>  $query
     * @return Builder<SurveyResponse>
     */
    public function scopeForSegment(Builder $query, string $segmentId): Builder
    {
        return $query->where('segment_id', $segmentId);
    }
}
