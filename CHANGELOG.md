# Changelog

All notable changes to `givanov95/laravel-translations` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Initial extraction from `laravel-starter`.
- `Translation` polymorphic Eloquent model + migration with unique constraint on `(locale, translatable_type, translatable_id, key)`.
- `HasTranslation` trait: `setTranslation()` (accepts string or `BackedEnum` locale), `loadTranslations()`, `withTranslations()` / `withTranslationsForLocale()` scopes. Staging is keyed by locale+key so calling `setTranslation('en','title')` followed by `setTranslation('bg','title')` correctly creates two rows.
- `Translator` service: cached JSON loader (`translations`), `getAllLocales()` reads `lang/*.json` files, `clearCache()`, `mapModelTranslationKeys()` / `mapCollectionTranslationKeys()`.
- `InitAppLocale` + `InitLocalePrefix` middlewares.
- `MultiSelectService` — load select options from Eloquent models, with `dataForSelectWithTranslations(key)` for translation-aware payloads.
- `MultiSelectDataConversion` trait — turns PHP enums into `forSelect()` / `forSelectWith()` / `forSelectWithTranslate()` arrays.
- `TranslationPlugin.ts` — Vue plugin exposing `__('key', { name })`.
- `TranslationsServiceProvider` — auto-loads the package migration; publishes config (`translations-config`), migrations (`translations-migrations`), and the frontend plugin (`translations-frontend`).

### Notes
- The package is enum-agnostic: your project keeps its own `Locale` enum and passes either the enum or a plain locale string.
- Locale lookups are cached via `Cache::rememberForever` — call `Translator::clearCache()` after editing lang JSON files.
