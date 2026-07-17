<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\Survey\SurveyBatchActivated;
use App\Events\Survey\SurveyBatchClosed;
use App\Events\Survey\SurveyResponseCompleted;
use App\Listeners\Survey\LogBatchActivation;
use App\Listeners\Survey\LogBatchClosure;
use App\Listeners\Survey\LogResponseCompletion;
use App\Models\Teacher;
use App\Observers\TeacherObserver;
use App\Support\Pdf\BrowsershotConfigurator;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Spatie\Browsershot\Browsershot;
use Spatie\LaravelPdf\Facades\Pdf;

final class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Teacher::observe(TeacherObserver::class);

        Event::listen(SurveyBatchActivated::class, LogBatchActivation::class);
        Event::listen(SurveyBatchClosed::class, LogBatchClosure::class);
        Event::listen(SurveyResponseCompleted::class, LogResponseCompletion::class);

        $this->configureBrowsershot();
    }

    private function configureBrowsershot(): void
    {
        Pdf::default()->withBrowsershot(function (Browsershot $browsershot): void {
            app(BrowsershotConfigurator::class)->configure($browsershot);
        });
    }
}
