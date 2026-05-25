<?php

declare(strict_types=1);

namespace Givanov95\LaravelTranslations\Tests\Feature;

use Givanov95\LaravelTranslations\Tests\TestCase;
use Givanov95\LaravelTranslations\Translator;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

class TranslatorTest extends TestCase
{
    public function test_loads_translations_from_json_file(): void
    {
        $this->withLangFile('en', ['Save' => 'Save', 'Cancel' => 'Cancel']);

        $translations = Translator::translations('en');

        $this->assertSame(['Save' => 'Save', 'Cancel' => 'Cancel'], $translations);
    }

    public function test_caches_translations_per_locale(): void
    {
        $dir = $this->withLangFile('en', ['Save' => 'Save']);

        Translator::translations('en');
        $this->assertTrue(Cache::has('translations_en'));

        // Mutating the file should NOT affect the second call because of cache.
        file_put_contents("{$dir}/en.json", json_encode(['Save' => 'CHANGED']));

        $this->assertSame(['Save' => 'Save'], Translator::translations('en'));
    }

    public function test_uses_app_locale_when_none_passed(): void
    {
        $this->withLangFile('bg', ['Save' => 'Запис']);
        App::setLocale('bg');

        $this->assertSame(['Save' => 'Запис'], Translator::translations());
    }

    public function test_throws_when_file_missing(): void
    {
        $this->withLangFile('en', ['Save' => 'Save']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Translation file not found for locale [xx]');

        Translator::translations('xx');
    }

    public function test_get_all_locales_lists_every_json_file(): void
    {
        $dir = $this->withLangFile('en', []);
        file_put_contents("{$dir}/bg.json", '{}');
        file_put_contents("{$dir}/notes.txt", 'ignored');

        $locales = Translator::getAllLocales();

        sort($locales);
        $this->assertSame(['bg', 'en'], $locales);
    }

    public function test_clear_cache_forgets_every_locale(): void
    {
        $dir = $this->withLangFile('en', ['k' => 'v']);
        file_put_contents("{$dir}/bg.json", json_encode(['k' => 'v']));

        Translator::translations('en');
        Translator::translations('bg');
        $this->assertTrue(Cache::has('translations_en'));
        $this->assertTrue(Cache::has('translations_bg'));

        Translator::clearCache();

        $this->assertFalse(Cache::has('translations_en'));
        $this->assertFalse(Cache::has('translations_bg'));
    }
}
