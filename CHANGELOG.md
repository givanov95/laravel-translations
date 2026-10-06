# Changelog

All notable changes to `givanov95/laravel-translations` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Breaking
- Removed the parts no project used: the `Translation` model, the `HasTranslation` trait,
  `Translator::mapModelTranslationKeys()` / `mapCollectionTranslationKeys()`, `Services\MultiSelectService`
  and `Concerns\MultiSelectDataConversion`. What stays is `Translator` (`translations()`,
  `getAllLocales()`, `clearCache()`), the two middlewares, the config and the Vue plugin.
- The package no longer loads or publishes a migration, so `php artisan migrate` stops creating a
  `translations` table (and the `translations-migrations` publish tag is gone). A table that was
  already created is left alone.
- The `illuminate/database` requirement is gone with them.

  **Upgrade:** nothing to do if you only use the translator, the middlewares and the plugin. If
  you did use a removed class, copy it from the `v1.1.1` tag into your project. Drop the old table
  with a migration of your own if nothing uses it. See "Upgrading from 1.x" in the README.

### Fixed
- `Translator::translations()` returns `[]` for a locale without a `lang/{locale}.json` file instead
  of throwing a `RuntimeException`, which turned a locale nobody has a file for (from a URL
  prefix or a cookie) into a 500. A file that is not valid JSON still throws.
- The cache is invalidated when the file changes: an entry remembers the modification time and size of
  the file it was read from, and a changed file replaces it. Before, the entry lived forever until
  `Translator::clearCache()` or a cache clear on deploy. `clearCache()` stays. Entries written by 1.x
  have no version and are simply read again.

### Added
- CI: PHPUnit and PHPStan on PHP 8.3 and 8.4 for every push to `main` and every pull request.

## [1.1.1] and earlier

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
