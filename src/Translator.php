<?php

declare(strict_types=1);

namespace Givanov95\LaravelTranslations;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
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
     * With `translations.fallback_chain` on, the files of localeChain() are merged, the most generic
     * first, so the requested locale wins; a locale with no file anywhere in its chain still has none.
     *
     * The cache entry remembers which version of the file(s) it was read from (modification time and
     * size, of every file in the chain), so editing a file replaces it without anyone calling
     * clearCache().
     *
     * @return array<string, string>
     */
    public static function translations(?string $locale = null): array
    {
        $locale = $locale ?: App::getLocale();
        $chain = config('translations.fallback_chain') ? self::localeChain($locale) : [$locale];

        // Existing files only, the most generic locale first so that the requested one is applied last.
        $files = [];
        foreach (array_reverse($chain) as $candidate) {
            $path = self::langPath($candidate);

            if (File::exists($path)) {
                $files[$candidate] = $path;
            }
        }

        if ($files === []) {
            return [];
        }

        $version = count($chain) > 1
            ? implode('|', array_map(
                fn (string $candidate, string $path): string => $candidate.':'.File::lastModified($path).'-'.File::size($path),
                array_keys($files),
                $files,
            ))
            : File::lastModified($files[$locale]).'-'.File::size($files[$locale]);
        $key = self::CACHE_KEY_PREFIX.$locale;
        $cached = Cache::get($key);

        if (is_array($cached) && ($cached['version'] ?? null) === $version && is_array($cached['translations'] ?? null)) {
            return $cached['translations'];
        }

        $messages = [];
        foreach ($files as $path) {
            $decoded = json_decode(File::get($path), true);

            if (! is_array($decoded)) {
                throw new RuntimeException("Invalid JSON in translation file: {$path}");
            }

            // array_replace, not array_merge: array_merge renumbers integer-like keys ("404").
            $messages = array_replace($messages, $decoded);
        }

        Cache::forever($key, ['version' => $version, 'translations' => $messages]);

        return $messages;
    }

    /**
     * The order in which a locale is resolved with `translations.fallback_chain` on: the exact locale,
     * its base language (the part before the first "-", the BCP 47 region separator), then the
     * application's fallback locale. Empty values and duplicates are dropped:
     * "bg-BG" => ["bg-BG", "bg", "en-GB"] when app.fallback_locale is "en-GB".
     *
     * @return list<string>
     */
    public static function localeChain(string $locale): array
    {
        $chain = [$locale];
        $base = Str::before($locale, '-');

        if ($base !== '' && $base !== $locale) {
            $chain[] = $base;
        }

        $fallback = (string) config('app.fallback_locale');

        if ($fallback !== '') {
            $chain[] = $fallback;
        }

        return array_values(array_unique(array_filter($chain, fn (string $l): bool => $l !== '')));
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
