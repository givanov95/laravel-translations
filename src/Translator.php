<?php

declare(strict_types=1);

namespace Givanov95\LaravelTranslations;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use RuntimeException;

class Translator
{
    private const CACHE_KEY_PREFIX = 'translations_';

    /**
     * Load and cache the JSON translation file for the given locale (or current).
     *
     * A locale without a file has no translations: the locale can come from a URL prefix or a
     * cookie, and a value nobody has a file for must not turn a page into a 500. A file that is not
     * valid JSON is still an error, that is a mistake in the project.
     *
     * The cache entry remembers which version of the file it was read from (modification time and
     * size), so editing the file replaces it without anyone calling clearCache().
     *
     * @return array<string, string>
     */
    public static function translations(?string $locale = null): array
    {
        $locale = $locale ?: App::getLocale();
        $path = self::langPath($locale);

        if (! File::exists($path)) {
            return [];
        }

        $version = File::lastModified($path).'-'.File::size($path);
        $key = self::CACHE_KEY_PREFIX.$locale;
        $cached = Cache::get($key);

        if (is_array($cached) && ($cached['version'] ?? null) === $version && is_array($cached['translations'] ?? null)) {
            return $cached['translations'];
        }

        $decoded = json_decode(File::get($path), true);

        if (! is_array($decoded)) {
            throw new RuntimeException("Invalid JSON in translation file: {$path}");
        }

        Cache::forever($key, ['version' => $version, 'translations' => $decoded]);

        return $decoded;
    }

    /**
     * List every locale that has a `lang/{locale}.json` file.
     *
     * @return array<int, string>
     */
    public static function getAllLocales(): array
    {
        $dir = base_path(config('translations.lang_path', 'lang'));

        if (! File::isDirectory($dir)) {
            return [];
        }

        return collect(File::files($dir))
            ->filter(fn ($file) => $file->getExtension() === 'json')
            ->map(fn ($file) => $file->getFilenameWithoutExtension())
            ->values()
            ->all();
    }

    public static function clearCache(): void
    {
        foreach (self::getAllLocales() as $locale) {
            Cache::forget(self::CACHE_KEY_PREFIX.$locale);
        }
    }

    private static function langPath(string $locale): string
    {
        return base_path(config('translations.lang_path', 'lang').'/'.$locale.'.json');
    }
}
