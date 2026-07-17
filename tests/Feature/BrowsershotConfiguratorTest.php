<?php

declare(strict_types=1);

use App\Support\Pdf\BrowsershotConfigurator;
use Illuminate\Support\Facades\File;
use Spatie\Browsershot\Browsershot;

test('ensure chrome home creates writable directories under storage', function (): void {
    $home = storage_path('app/chrome-test-'.uniqid());

    config(['laravel-pdf.browsershot.chrome_home' => $home]);

    $resolved = app(BrowsershotConfigurator::class)->ensureChromeHome();

    expect($resolved)->toBe($home)
        ->and(File::isDirectory($home))->toBeTrue()
        ->and(File::isDirectory($home.'/config'))->toBeTrue()
        ->and(File::isDirectory($home.'/cache'))->toBeTrue();

    File::deleteDirectory($home);
});

test('configure applies environment options and chromium arguments', function (): void {
    $home = storage_path('app/chrome-test-'.uniqid());
    config(['laravel-pdf.browsershot.chrome_home' => $home]);

    $browsershot = Browsershot::html('<h1>test</h1>');

    app(BrowsershotConfigurator::class)->configure($browsershot, requireChrome: false);

    $ref = new ReflectionClass($browsershot);

    $additionalOptions = $ref->getProperty('additionalOptions');
    $additionalOptions->setAccessible(true);
    /** @var array<string, mixed> $options */
    $options = $additionalOptions->getValue($browsershot);

    $chromiumArguments = $ref->getProperty('chromiumArguments');
    $chromiumArguments->setAccessible(true);
    /** @var list<string> $args */
    $args = $chromiumArguments->getValue($browsershot);

    expect($options['env']['HOME'] ?? null)->toBe($home)
        ->and($options['env']['XDG_CONFIG_HOME'] ?? null)->toBe($home.'/config')
        ->and($options['env']['XDG_CACHE_HOME'] ?? null)->toBe($home.'/cache')
        ->and($args)->toContain('--disable-crash-reporter')
        ->and($args)->toContain('--disable-dev-shm-usage')
        ->and($args)->toContain('--no-first-run');

    File::deleteDirectory($home);
});
