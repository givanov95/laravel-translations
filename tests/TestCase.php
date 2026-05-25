<?php

declare(strict_types=1);

namespace Givanov95\LaravelTranslations\Tests;

use Givanov95\LaravelTranslations\Translator;
use Givanov95\LaravelTranslations\TranslationsServiceProvider;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\File;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [TranslationsServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        /** @var Application $app */
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
        $app['config']->set('app.locale', 'en');
        $app['config']->set('app.fallback_locale', 'en');
    }

    protected function tearDown(): void
    {
        // Bust the Translator cache and clean any temp lang dirs the test made.
        Translator::clearCache();
        foreach (glob(base_path('lang-test-*')) ?: [] as $dir) {
            File::deleteDirectory($dir);
        }

        parent::tearDown();
    }

    /**
     * Write `{dir}/{locale}.json` inside the testbench app's base_path and
     * point `translations.lang_path` at it so Translator finds it.
     */
    protected function withLangFile(string $locale, array $translations): string
    {
        $dir = base_path('lang-test-'.uniqid());
        if (! is_dir($dir)) {
            mkdir($dir, 0o755, true);
        }

        config(['translations.lang_path' => basename($dir)]);
        file_put_contents("{$dir}/{$locale}.json", json_encode($translations));

        return $dir;
    }
}
