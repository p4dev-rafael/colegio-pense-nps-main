<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasUuid;
use App\Services\SegmentTeacherProvisioningService;
use Database\Factories\SegmentTeacherFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\App;
use Illuminate\Validation\ValidationException;

/**
 * @property string $id
 * @property string $unit_id
 * @property string $segment_id
 * @property string $teacher_id
 * @property string|null $subject_id
 */
final class SegmentTeacher extends Model
{
    /** @use HasFactory<SegmentTeacherFactory> */
    use HasFactory, HasUuid;

    protected $table = 'segment_teachers';

    protected $fillable = [
        'unit_id',
        'segment_id',
        'teacher_id',
        'subject_id',
    ];

    protected static function booted(): void
    {
        static::saving(function (SegmentTeacher $segmentTeacher): void {
            /** @var SegmentTeacherProvisioningService $provisioning */
            $provisioning = App::make(SegmentTeacherProvisioningService::class);
            $provisioning->assertTeacherBelongsToUnit($segmentTeacher->teacher_id, $segmentTeacher->unit_id);

            $segment = Segment::query()->find($segmentTeacher->segment_id);
            if ($segment instanceof Segment && $segment->group->expectsSubjectTeachers() && $segmentTeacher->subject_id === null) {
                throw ValidationException::withMessages([
                    'subject_id' => __('teachers.messages.subject_required_for_segment'),
                ]);
            }

            if ($segment instanceof Segment && ! $segment->group->expectsSubjectTeachers() && $segmentTeacher->subject_id !== null) {
                throw ValidationException::withMessages([
                    'subject_id' => __('teachers.messages.subject_forbidden_for_segment'),
                ]);
            }

            $duplicateExists = SegmentTeacher::query()
                ->where('unit_id', $segmentTeacher->unit_id)
                ->where('segment_id', $segmentTeacher->segment_id)
                ->where('teacher_id', $segmentTeacher->teacher_id)
                ->when($segmentTeacher->subject_id === null,
                    fn (Builder $q): Builder => $q->whereNull('subject_id'),
                    fn (Builder $q): Builder => $q->where('subject_id', $segmentTeacher->subject_id)
                )
                ->when($segmentTeacher->exists,
                    fn (Builder $q): Builder => $q->whereKeyNot($segmentTeacher->getKey())
                )
                ->exists();

            if ($duplicateExists) {
                throw ValidationException::withMessages([
                    'teacher_id' => __('teachers.messages.duplicate_segment_assignment'),
                ]);
            }
        });
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
     * @return BelongsTo<Teacher, $this>
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    /**
     * @return BelongsTo<Subject, $this>
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * @param  Builder<SegmentTeacher>  $query
     * @return Builder<SegmentTeacher>
     */
    public function scopeForUnit(Builder $query, string $unitId): Builder
    {
        return $query->where('unit_id', $unitId);
    }

    /**
     * @param  Builder<SegmentTeacher>  $query
     * @return Builder<SegmentTeacher>
     */
    public function scopeForSegment(Builder $query, string $segmentId): Builder
    {
        return $query->where('segment_id', $segmentId);
    }

    /**
     * @param  Builder<SegmentTeacher>  $query
     * @return Builder<SegmentTeacher>
     */
    public function scopeForSubject(Builder $query, string $subjectId): Builder
    {
        return $query->where('subject_id', $subjectId);
    }
}
