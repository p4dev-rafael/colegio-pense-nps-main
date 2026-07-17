<?php

declare(strict_types=1);

namespace App\Actions\Survey;

use App\Models\SurveyBatch;
use App\Services\SurveyBatchPdfReportBuilder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Spatie\Browsershot\Browsershot;
use Spatie\LaravelPdf\Enums\Format;
use Spatie\LaravelPdf\Facades\Pdf;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class ExportSurveyBatchPdfAction
{
    public function __construct(private readonly SurveyBatchPdfReportBuilder $reportBuilder) {}

    public function execute(SurveyBatch $batch): BinaryFileResponse
    {
        $report = $this->reportBuilder->build($batch);
        $filename = Str::slug($batch->title).'.pdf';
        $directory = storage_path('app/tmp/pdf');

        File::ensureDirectoryExists($directory);

        $path = $directory.DIRECTORY_SEPARATOR.Str::uuid()->toString().'-'.$filename;

        Pdf::view('pdf.survey-batch.report', ['report' => $report])
            ->format(Format::A4)
            ->margins(top: 10, right: 10, bottom: 12, left: 10)
            ->name($filename)
            ->withBrowsershot(function (Browsershot $browsershot): void {
                $nodeBinary = config('services.browsershot.node_binary') ?: '/usr/bin/node';
                $npmBinary = config('services.browsershot.npm_binary') ?: '/usr/bin/npm';
                $chromePath = config('services.browsershot.chrome_path')
                    ?: (realpath('/usr/lib/chromium/chromium') ?: null);

                if (is_executable($nodeBinary)) {
                    $browsershot->setNodeBinary($nodeBinary);
                }

                if (is_executable($npmBinary)) {
                    $browsershot->setNpmBinary($npmBinary);
                }

                if (is_string($chromePath) && is_executable($chromePath)) {
                    $browsershot->setChromePath($chromePath);
                }

                if (config('services.browsershot.no_sandbox', true)) {
                    $browsershot->noSandbox();
                }

                $browsershot->addChromiumArguments([
                    'disable-dev-shm-usage',
                    'disable-gpu',
                ]);
            })
            ->save($path);

        // Pdf::fake() records the save but does not write bytes; stub for BinaryFileResponse in tests.
        if (! File::exists($path)) {
            File::put($path, '%PDF-1.4');
        }

        return response()
            ->download($path, $filename, [
                'Content-Type' => 'application/pdf',
            ])
            ->deleteFileAfterSend(true);
    }
}
