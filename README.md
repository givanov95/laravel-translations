# givanov95/laravel-translations

Multi-locale translation infrastructure for Laravel + Inertia + Vue projects.

## What's included

- `Translation` polymorphic Eloquent model — store any model's translatable text per locale
- `HasTranslation` trait — `setTranslation()`, `loadTranslations()`, `withTranslations()` scope
- `Translator` service — cached JSON loader, `getAllLocales()`, model relation key mapping
- `InitAppLocale` + `InitLocalePrefix` middlewares
- `MultiSelectService` — load select options from any Eloquent model, with optional translation lookup
- `MultiSelectDataConversion` trait — turn a PHP enum into select option arrays
- `TranslationPlugin.ts` — Vue plugin exposing `__('key', { name: 'value' })`

The package is **enum-agnostic** — your project keeps its own `Locale` enum and passes either `Locale::en` or the plain `'en'` string. Internally everything works with strings.

## Install

```bash
composer require givanov95/laravel-translations
php artisan migrate
php artisan vendor:publish --tag=translations-frontend   # copies TranslationPlugin.ts
# optional:
php artisan vendor:publish --tag=translations-config
php artisan vendor:publish --tag=translations-migrations
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

### On models

```php
use Givanov95\LaravelTranslations\Concerns\HasTranslation;

class Category extends Model
{
    use HasTranslation;
}

// staging + persisting:
$category->setTranslation('en', 'title', 'Shoes')
    ->setTranslation('bg', 'title', 'Обувки')
    ->save();

// eager-loading translations for current locale:
Category::withTranslations()->get();

// loading + key-indexing for a single model:
$category->loadTranslations();
$category->translations['title']->text;
```

### Select options from a model

```php
use Givanov95\LaravelTranslations\Services\MultiSelectService;

// untranslated (name column):
$options = (new MultiSelectService(Category::class))->dataForSelect();

// translated (translation row with key='title' for current locale):
$options = (new MultiSelectService(Category::class))->dataForSelectWithTranslations('title');
```

### Enum to options

```php
use Givanov95\LaravelTranslations\Concerns\MultiSelectDataConversion;

enum Status: string
{
    use MultiSelectDataConversion;
    case Active = 'active';
    case Draft = 'draft';
}

Status::forSelect();             // ['active' => 'Active', 'draft' => 'Draft']
Status::forSelectWithTranslate(); // [{id: 'active', name: __('Active')}, ...]
```

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
composer test          # PHPUnit (16 tests)
composer analyse       # PHPStan level 5
```

### Pre-commit hook

`composer install` / `composer update` symlinks the repo's `pre-commit` script into `.git/hooks/pre-commit`. It runs `composer test` + `composer analyse` before any commit that touches `.php` files — replacement for CI since the repo is private.

Bypass with `git commit --no-verify` when you genuinely need to (WIP commit, doc-only change you've already validated).

## License

MIT
