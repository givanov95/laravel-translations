<?php

declare(strict_types=1);

namespace Givanov95\LaravelTranslations;

use Illuminate\Support\ServiceProvider;

class TranslationsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/translations.php', 'translations');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/translations.php' => config_path('translations.php'),
            ], 'translations-config');

            $this->publishes([
                __DIR__.'/../resources/js/plugins/TranslationPlugin.ts'
                    => resource_path('js/plugins/TranslationPlugin.ts'),
            ], 'translations-frontend');
        }
    }
}
