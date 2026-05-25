<?php

declare(strict_types=1);

namespace Givanov95\LaravelTranslations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
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
     * @return array<string, string>
     */
    public static function translations(?string $locale = null): array
    {
        $locale = $locale ?: App::getLocale();
        $path = self::langPath($locale);

        return Cache::rememberForever(self::CACHE_KEY_PREFIX.$locale, function () use ($path, $locale) {
            if (! File::exists($path)) {
                throw new RuntimeException("Translation file not found for locale [{$locale}]: {$path}");
            }

            $decoded = json_decode(File::get($path), true);

            if (! is_array($decoded)) {
                throw new RuntimeException("Invalid JSON in translation file: {$path}");
            }

            return $decoded;
        });
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

    /**
     * Rebuild the model's `translations` relation as a Collection keyed by
     * translation `key` instead of sequential integers.
     */
    public static function mapModelTranslationKeys(Model $model): void
    {
        if (! $model->relationLoaded('translations')) {
            return;
        }

        $mapped = new Collection();
        /** @var iterable<Models\Translation> $translations */
        $translations = $model->getRelation('translations');
        foreach ($translations as $translation) {
            $mapped->put($translation->key, $translation);
        }

        $model->setRelation('translations', $mapped);
    }

    public static function mapCollectionTranslationKeys(Collection $collection): Collection
    {
        return $collection->each(fn ($model) => self::mapModelTranslationKeys($model));
    }

    private static function langPath(string $locale): string
    {
        return base_path(config('translations.lang_path', 'lang').'/'.$locale.'.json');
    }
}
