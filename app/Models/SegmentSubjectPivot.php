<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Relations\Pivot;

final class SegmentSubjectPivot extends Pivot
{
    use HasUuid;

    protected $table = 'segment_subject';
}
