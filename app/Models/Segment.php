<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SegmentGroup;
use App\Models\Concerns\HasUuid;
use Database\Factories\SegmentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $id
 * @property string $name
 * @property string $slug
 * @property SegmentGroup $group
 * @property int $sort_order
 * @property bool $is_active
 */
final class Segment extends Model
{
    /** @use HasFactory<SegmentFactory> */
    use HasFactory, HasUuid, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'group',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'group' => SegmentGroup::class,
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsToMany<Subject, $this>
     */
    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'segment_subject')
            ->using(SegmentSubjectPivot::class)
            ->withTimestamps();
    }

    /**
     * @return HasMany<Enrollment, $this>
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    /**
     * @return HasMany<SegmentTeacher, $this>
     */
    public function segmentTeachers(): HasMany
    {
        return $this->hasMany(SegmentTeacher::class);
    }

    /**
     * @param  Builder<Segment>  $query
     * @return Builder<Segment>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<Segment>  $query
     * @return Builder<Segment>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order');
    }

    /**
     * @param  Builder<Segment>  $query
     * @return Builder<Segment>
     */
    public function scopeByGroup(Builder $query, SegmentGroup $group): Builder
    {
        return $query->where('group', $group);
    }
}
