<?php

declare(strict_types=1);

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function (): void {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('survey:close-expired-batches')
    ->everyFiveMinutes()
    ->timezone('America/Sao_Paulo')
    ->withoutOverlapping()
    ->onOneServer()
    ->appendOutputTo(storage_path('logs/close-expired-batches.log'));
