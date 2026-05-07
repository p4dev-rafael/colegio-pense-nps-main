<?php

declare(strict_types=1);

namespace App\Events\Survey;

use App\Models\SurveyBatch;
use Illuminate\Foundation\Events\Dispatchable;

final class SurveyBatchActivated
{
    use Dispatchable;

    public function __construct(public readonly SurveyBatch $batch) {}
}
