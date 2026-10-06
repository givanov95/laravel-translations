# givanov95/laravel-translations

Multi-locale translation infrastructure for Laravel + Inertia + Vue projects.

## What's included

- `Translator` service — cached JSON loader (`Translator::translations()`), `getAllLocales()`, `clearCache()`
- `InitAppLocale` + `InitLocalePrefix` middlewares
- `TranslationPlugin.ts` — Vue plugin exposing `__('key', { name: 'value' })`

Version 2 is just these three. The model translations (`Translation`, `HasTranslation`) and the select helpers (`MultiSelectService`, `MultiSelectDataConversion`) of version 1 were never used by the projects that use the package and are gone; see [Upgrading from 1.x](#upgrading-from-1x).

The package is **enum-agnostic** — your project keeps its own `Locale` enum and passes either `Locale::en` or the plain `'en'` string. Internally everything works with strings.

## Install

```bash
composer require givanov95/laravel-translations
php artisan vendor:publish --tag=translations-frontend   # copies TranslationPlugin.ts
# optional:
php artisan vendor:publish --tag=translations-config
```

## Setup

### 1. Locale enum (project-side)

Create `app/Enums/Locale.php` in your project (the package doesn't ship one — locales differ per project):

```php
namespace App\Enums;

enum Locale: string
{
    case en = 'en';
    case bg = 'bg';
}
```

### 2. Lang files

Create `lang/en.json`, `lang/bg.json` etc. with your translations:

```json
{
    "Save": "Save",
    "Cancel": "Cancel"
}
```

### 3. Register middlewares (bootstrap/app.php)

```php
use Givanov95\LaravelTranslations\Middleware\InitAppLocale;
use Givanov95\LaravelTranslations\Middleware\InitLocalePrefix;

$middleware->web(append: [
    InitAppLocale::class,
    InitLocalePrefix::class,
    \App\Http\Middleware\HandleInertiaRequests::class,
]);
```

### 4. Share with Inertia

In `app/Http/Middleware/HandleInertiaRequests.php`:

```php
use Givanov95\LaravelTranslations\Translator;
use Illuminate\Support\Facades\App;

public function share(Request $request): array
{
    return array_merge_recursive(parent::share($request), [
        'locale'       => fn () => App::getLocale(),
        'translations' => fn () => Translator::translations(),
        // ...
    ]);
}
```

### 5. Install the Vue plugin (resources/js/app.ts)

```ts
import TranslationPlugin from '@/plugins/TranslationPlugin';

// inside createInertiaApp setup():
const translations = props.initialPage.props.translations as Record<string, string>;
app.use(TranslationPlugin, translations);
```

## Usage

### The translator

```php
use Givanov95\LaravelTranslations\Translator;

Translator::translations();      // the current locale: the contents of lang/{locale}.json
Translator::translations('bg');  // a given locale
Translator::getAllLocales();     // every locale that has a lang/*.json file
```

- A locale without a `lang/{locale}.json` file has **no translations**: `translations()` returns `[]`. The locale can come from a URL prefix or a cookie, and a value nobody has a file for must not turn a page into a 500. A file that is not valid JSON is still an error (`RuntimeException`).
- The result is cached per locale. The cache entry remembers the modification time and size of the file it came from, so editing `lang/{locale}.json` is picked up on the next call without any clearing. `Translator::clearCache()` is still there, for example after rewriting a file from code within the same second with the same size.

### In Vue templates

```vue
<template>
    <h1>{{ __('Categories') }}</h1>
    <p>{{ __('Hello, :name', { name: user.name }) }}</p>
</template>
```

## Development

```bash
composer install
composer test          # PHPUnit
composer analyse       # PHPStan level 5
```

### CI

`.github/workflows/ci.yml` runs PHPUnit and PHPStan on PHP 8.3 and 8.4 for every push to `main` and every pull request (the shared `php-package` workflow from [`givanov95/ci-workflows`](https://github.com/givanov95/ci-workflows)). It uses the newest Laravel that `composer.json` allows; Laravel 11 and 12 are not tested separately.

### Pre-commit hook

`composer install` / `composer update` symlinks the repo's `pre-commit` script into `.git/hooks/pre-commit`; it is a thin shim over the shared hook of [`givanov95/laravel-git-hooks`](https://github.com/givanov95/laravel-git-hooks) and runs `composer test` + `composer analyse` before any commit that touches `.php` files.

Bypass with `git commit --no-verify` when you genuinely need to (WIP commit, doc-only change you've already validated).

## Upgrading from 1.x

2.0 removes what no project used:

- `Givanov95\LaravelTranslations\Models\Translation`, the `HasTranslation` trait and `Translator::mapModelTranslationKeys()` / `mapCollectionTranslationKeys()`;
- `Services\MultiSelectService` and `Concerns\MultiSelectDataConversion` (they produced the retired `{ "Text": id }` payload; the standard is `{ value, label }`);
- the package migration and the `translations-migrations` publish tag. `php artisan migrate` no longer creates a `translations` table.

Nothing else changes: `Translator`, the two middlewares, the config and the Vue plugin are as before. If you did use one of the removed classes, copy it from the `v1.1.1` tag into your project. A `translations` table that earlier versions already created is left alone; drop it with a migration of your own if nothing uses it (`Schema::dropIfExists('translations')`).

## License

MIT
