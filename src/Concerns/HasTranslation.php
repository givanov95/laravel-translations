<?php

declare(strict_types=1);

namespace Givanov95\LaravelTranslations\Concerns;

use BackedEnum;
use Givanov95\LaravelTranslations\Models\Translation;
use Givanov95\LaravelTranslations\Translator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\App;

trait HasTranslation
{
    protected array $stagedTranslations = [];

    public static function bootHasTranslation(): void
    {
        static::saved(function ($model): void {
            $model->persistStagedTranslations();
        });

        static::deleting(function ($model): void {
            $model->translations()->delete();
        });
    }

    public function translations(): MorphMany
    {
        return $this->morphMany(Translation::class, 'translatable');
    }

    public function setTranslation(string|BackedEnum $locale, string $key, string $text): self
    {
        $localeValue = $locale instanceof BackedEnum ? (string) $locale->value : $locale;

        // Index by locale+key so calling setTranslation('en','title')
        // followed by setTranslation('bg','title') doesn't overwrite.
        $this->stagedTranslations["{$localeValue}::{$key}"] = [
            'locale' => $localeValue,
            'key'    => $key,
            'text'   => $text,
        ];

        return $this;
    }

    protected function persistStagedTranslations(): void
    {
        if (empty($this->stagedTranslations)) {
            return;
        }

        foreach ($this->stagedTranslations as $translation) {
            $this->translations()->updateOrCreate(
                [
                    'translatable_type' => $this->getMorphClass(),
                    'translatable_id'   => $this->id,
                    'locale'            => $translation['locale'],
                    'key'               => $translation['key'],
                ],
                ['text' => $translation['text']]
            );
        }

        $this->stagedTranslations = [];
    }

    public function scopeWithTranslations(Builder $query): Builder
    {
        return $this->scopeWithTranslationsForLocale($query, self::currentLocale());
    }

    public function scopeWithTranslationsForLocale(Builder $query, string|BackedEnum $locale): Builder
    {
        $value = $locale instanceof BackedEnum ? (string) $locale->value : $locale;

        return $query->with(['translations' => fn ($q) => $q->where('locale', $value)]);
    }

    public function loadTranslations(): self
    {
        $this->load(['translations' => fn ($q) => $q->where('locale', self::currentLocale())]);
        Translator::mapModelTranslationKeys($this);

        return $this;
    }

    private static function currentLocale(): string
    {
        $value = request()->input('locale', App::getLocale());

        return $value instanceof BackedEnum ? (string) $value->value : (string) $value;
    }
}
