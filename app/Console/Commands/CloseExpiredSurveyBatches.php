<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\Survey\CloseExpiredSurveyBatchesJob;
use Illuminate\Console\Command;

final class CloseExpiredSurveyBatches extends Command
{
    protected $signature = 'survey:close-expired-batches';

    protected $description = 'Encerra lotes de pesquisa com período expirado.';

    public function handle(): int
    {
        $this->info('Dispatching CloseExpiredSurveyBatchesJob...');

        CloseExpiredSurveyBatchesJob::dispatch();

        return self::SUCCESS;
    }
}
