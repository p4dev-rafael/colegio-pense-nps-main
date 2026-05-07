<?php

declare(strict_types=1);

namespace App\Jobs\Survey;

use App\Actions\Survey\CloseBatchAction;
use App\Enums\SurveyBatchStatus;
use App\Models\SurveyBatch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

final class CloseExpiredSurveyBatchesJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [10, 30, 60];

    public function __construct()
    {
        $this->onQueue('default');
    }

    public function uniqueId(): string
    {
        return 'survey:close-expired-batches';
    }

    public function handle(CloseBatchAction $closeBatch): void
    {
        SurveyBatch::query()
            ->expired()
            ->each(function (SurveyBatch $batch) use ($closeBatch): void {
                if ($batch->status !== SurveyBatchStatus::Active) {
                    return;
                }

                try {
                    $closeBatch->execute($batch, isAutomatic: true);
                } catch (Throwable $e) {
                    Log::warning('survey.batch.close_expired_failed', [
                        'batch_id' => $batch->id,
                        'message' => $e->getMessage(),
                    ]);
                }
            });
    }
}
