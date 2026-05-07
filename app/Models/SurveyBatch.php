<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SurveyBatchStatus;
use App\Models\Concerns\HasUuid;
use Database\Factories\SurveyBatchFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $unit_id
 * @property string $survey_id
 * @property string $title
 * @property string|null $description
 * @property bool $requires_identification
 * @property SurveyBatchStatus $status
 * @property string|null $public_token
 * @property Carbon|null $starts_at
 * @property Carbon|null $ends_at
 * @property Carbon|null $activated_at
 * @property Carbon|null $closed_at
 * @property string|null $created_by
 */
final class SurveyBatch extends Model
{
    /** @use HasFactory<SurveyBatchFactory> */
    use HasFactory, HasUuid, SoftDeletes;

    protected $fillable = [
        'unit_id',
        'survey_id',
        'title',
        'description',
        'requires_identification',
        'status',
        'public_token',
        'starts_at',
        'ends_at',
        'activated_at',
        'closed_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => SurveyBatchStatus::class,
            'requires_identification' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'activated_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Unit, $this>
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * @return BelongsTo<Survey, $this>
     */
    public function survey(): BelongsTo
    {
        return $this->belongsTo(Survey::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<SurveyResponse, $this>
     */
    public function surveyResponses(): HasMany
    {
        return $this->hasMany(SurveyResponse::class);
    }

    /**
     * @param  Builder<SurveyBatch>  $query
     * @return Builder<SurveyBatch>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', SurveyBatchStatus::Active);
    }

    /**
     * @param  Builder<SurveyBatch>  $query
     * @return Builder<SurveyBatch>
     */
    public function scopeExpired(Builder $query): Builder
    {
        return $query
            ->where('status', SurveyBatchStatus::Active)
            ->whereNotNull('ends_at')
            ->where('ends_at', '<', now());
    }

    /**
     * @param  Builder<SurveyBatch>  $query
     * @return Builder<SurveyBatch>
     */
    public function scopeAcceptingResponses(Builder $query): Builder
    {
        $now = now();

        return $query
            ->where('status', SurveyBatchStatus::Active)
            ->where(function (Builder $inner) use ($now): void {
                $inner->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
            })
            ->where(function (Builder $inner) use ($now): void {
                $inner->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
            });
    }

    public function isAcceptingResponses(): bool
    {
        if ($this->status !== SurveyBatchStatus::Active) {
            return false;
        }

        $now = now();

        if ($this->starts_at !== null && $this->starts_at->greaterThan($now)) {
            return false;
        }

        if ($this->ends_at !== null && $this->ends_at->lessThan($now)) {
            return false;
        }

        return true;
    }
}
