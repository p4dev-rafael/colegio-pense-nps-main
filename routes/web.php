<?php

declare(strict_types=1);

use App\Livewire\Survey\PublicSurvey;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['throttle:60,1'])->group(function (): void {
    Route::get('/survey/{token}', PublicSurvey::class)
        ->name('survey.show');
});
