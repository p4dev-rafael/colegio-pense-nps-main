<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\SegmentTeacher;
use App\Models\Teacher;

/**
 * Soft-deleted teachers remain in DB; segment_teachers rows must be removed explicitly (DTA mitigation).
 */
final class TeacherObserver
{
    public function deleting(Teacher $teacher): void
    {
        SegmentTeacher::query()->where('teacher_id', $teacher->id)->delete();
    }
}
