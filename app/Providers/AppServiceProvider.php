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
            $nodeBinary = config('services.browsershot.node_binary')
                ?: (is_executable('/usr/bin/node') ? '/usr/bin/node' : null);

            $npmBinary = config('services.browsershot.npm_binary')
                ?: (is_executable('/usr/bin/npm') ? '/usr/bin/npm' : null);

            $chromePath = config('services.browsershot.chrome_path')
                ?: $this->resolveChromePath();

            if (is_string($nodeBinary) && $nodeBinary !== '') {
                $browsershot->setNodeBinary($nodeBinary);
            }

            if (is_string($npmBinary) && $npmBinary !== '') {
                $browsershot->setNpmBinary($npmBinary);
            }

            if (is_string($chromePath) && $chromePath !== '') {
                $browsershot->setChromePath($chromePath);
            }

            if (config('services.browsershot.no_sandbox')) {
                $browsershot->noSandbox();
            }

            $browsershot->addChromiumArguments([
                'disable-dev-shm-usage',
                'disable-gpu',
            ]);
        });
    }

    private function resolveChromePath(): ?string
    {
        foreach ([
            '/usr/lib/chromium/chromium',
            '/usr/bin/chromium-browser',
            '/usr/bin/chromium',
            '/usr/bin/google-chrome',
        ] as $path) {
            $resolved = realpath($path);

            if (is_string($resolved) && is_executable($resolved)) {
                return $resolved;
            }
        }

        return null;
    }
}
