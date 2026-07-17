<?php

declare(strict_types=1);

namespace App\Support\Pdf;

use RuntimeException;
use Spatie\Browsershot\Browsershot;

/**
 * Applies Node/Chromium paths for Spatie Browsershot in Docker and production hosts.
 */
final class BrowsershotConfigurator
{
    /**
     * @var list<string>
     */
    private const CHROME_CANDIDATES = [
        '/usr/lib/chromium/chromium',
        '/usr/bin/chromium-browser',
        '/usr/bin/chromium',
        '/usr/lib/chromium-browser/chromium-browser',
        '/snap/bin/chromium',
        '/usr/bin/google-chrome-stable',
        '/usr/bin/google-chrome',
    ];

    public function configure(Browsershot $browsershot, bool $requireChrome = true): void
    {
        $nodeBinary = $this->firstExecutable([
            config('laravel-pdf.browsershot.node_binary'),
            config('services.browsershot.node_binary'),
            '/usr/bin/node',
        ]);

        $npmBinary = $this->firstExecutable([
            config('laravel-pdf.browsershot.npm_binary'),
            config('services.browsershot.npm_binary'),
            '/usr/bin/npm',
        ]);

        $chromePath = $this->resolveChromePath();

        if ($nodeBinary !== null) {
            $browsershot->setNodeBinary($nodeBinary);
        }

        if ($npmBinary !== null) {
            $browsershot->setNpmBinary($npmBinary);
        }

        if ($chromePath !== null) {
            $browsershot->setChromePath($chromePath);
        } elseif ($requireChrome && ! app()->runningUnitTests()) {
            throw new RuntimeException(
                'Browsershot could not find Chrome/Chromium. '.
                'Install Chromium on the server and set LARAVEL_PDF_CHROME_PATH '.
                '(or BROWSERSHOT_CHROME_PATH) to the absolute binary path, e.g. /usr/bin/chromium-browser.'
            );
        }

        $noSandbox = config('laravel-pdf.browsershot.no_sandbox');
        if ($noSandbox === null) {
            $noSandbox = config('services.browsershot.no_sandbox', true);
        }

        if ($noSandbox) {
            $browsershot->noSandbox();
        }

        $browsershot->addChromiumArguments([
            'disable-dev-shm-usage',
            'disable-gpu',
        ]);
    }

    public function resolveChromePath(): ?string
    {
        $configured = config('laravel-pdf.browsershot.chrome_path')
            ?: config('services.browsershot.chrome_path');

        if (is_string($configured) && $configured !== '') {
            $resolved = realpath($configured) ?: $configured;

            if (is_executable($resolved)) {
                return $resolved;
            }

            if (! app()->runningUnitTests()) {
                throw new RuntimeException(
                    "Configured Chrome path [{$configured}] is not executable. ".
                    'Update LARAVEL_PDF_CHROME_PATH to a valid Chromium/Chrome binary.'
                );
            }
        }

        return $this->firstExecutable(self::CHROME_CANDIDATES);
    }

    /**
     * @param  list<mixed>  $candidates
     */
    private function firstExecutable(array $candidates): ?string
    {
        foreach ($candidates as $candidate) {
            if (! is_string($candidate) || $candidate === '') {
                continue;
            }

            $resolved = realpath($candidate) ?: $candidate;

            if (is_executable($resolved)) {
                return $resolved;
            }
        }

        return null;
    }
}
