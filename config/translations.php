<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Language files path
    |--------------------------------------------------------------------------
    |
    | Path (relative to base_path()) where the locale JSON files live.
    | Default 'lang' matches Laravel's convention.
    |
    */
    'lang_path' => 'lang',

    /*
    |--------------------------------------------------------------------------
    | Locale fallback chain
    |--------------------------------------------------------------------------
    |
    | false (default): Translator::translations('bg-BG') returns the contents of lang/bg-BG.json only.
    |
    | true: it merges the files of the locale's chain, the most generic first so the requested locale
    | wins: lang/<app.fallback_locale>.json, then lang/bg.json (the base language, the part before
    | the first "-"), then lang/bg-BG.json. A string that is missing in the regional file then comes
    | from the base language or from the application's fallback locale instead of showing the bare key.
    | Files that do not exist are skipped; see Translator::localeChain().
    |
    */
    'fallback_chain' => false,
];
