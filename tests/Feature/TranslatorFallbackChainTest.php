<?php

declare(strict_types=1);

namespace Givanov95\LaravelTranslations\Tests\Feature;

use Givanov95\LaravelTranslations\Tests\TestCase;
use Givanov95\LaravelTranslations\Translator;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

class TranslatorFallbackChainTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['app.fallback_locale' => 'en-GB']);
    }

    /**
     * @param  array<string, array<string, string>>  $files  locale => translations
     */
    private function langFiles(array $files): string
    {
        $dir = $this->withLangFile('placeholder', []);
        unlink("{$dir}/placeholder.json");

        foreach ($files as $locale => $translations) {
            file_put_contents("{$dir}/{$locale}.json", json_encode($translations));
        }

        return $dir;
    }

    public function test_the_chain_is_off_by_default(): void
    {
        $this->assertFalse(config('translations.fallback_chain'));

        $this->langFiles(['bg-BG' => ['Save' => 'Запис'], 'en-GB' => ['Save' => 'Save', 'Cancel' => 'Cancel']]);

        $this->assertSame(['Save' => 'Запис'], Translator::translations('bg-BG'));
    }

    public function test_a_missing_string_comes_from_the_base_language_and_then_from_the_app_fallback(): void
    {
        config(['translations.fallback_chain' => true]);
        $this->langFiles([
            'bg-BG' => ['Save' => 'Запис (BG)'],
            'bg' => ['Save' => 'Запис (bg)', 'Cancel' => 'Отказ (bg)'],
            'en-GB' => ['Save' => 'Save', 'Cancel' => 'Cancel', 'Delete' => 'Delete'],
        ]);

        $this->assertSame(
            ['Save' => 'Запис (BG)', 'Cancel' => 'Отказ (bg)', 'Delete' => 'Delete'],
            Translator::translations('bg-BG'),
            'the requested locale wins, then the base language, then the app fallback',
        );
    }

    public function test_the_chain_skips_files_that_do_not_exist(): void
    {
        config(['translations.fallback_chain' => true]);
        $this->langFiles(['bg-BG' => ['Save' => 'Запис'], 'en-GB' => ['Save' => 'Save', 'Cancel' => 'Cancel']]);

        $this->assertSame(['Save' => 'Запис', 'Cancel' => 'Cancel'], Translator::translations('bg-BG'));
    }

    public function test_a_locale_without_a_file_gets_the_app_fallback(): void
    {
        config(['translations.fallback_chain' => true]);
        $this->langFiles(['en-GB' => ['Save' => 'Save']]);

        $this->assertSame(['Save' => 'Save'], Translator::translations('de-DE'));
    }

    public function test_a_locale_with_no_file_anywhere_in_its_chain_has_no_translations(): void
    {
        config(['translations.fallback_chain' => true]);
        $this->langFiles(['fr-FR' => ['Save' => 'Enregistrer']]);

        $this->assertSame([], Translator::translations('de-DE'));
        $this->assertFalse(Cache::has('translations_de-DE'));
    }

    public function test_the_current_locale_is_used_when_none_is_passed(): void
    {
        config(['translations.fallback_chain' => true]);
        $this->langFiles(['bg-BG' => ['Save' => 'Запис'], 'en-GB' => ['Cancel' => 'Cancel']]);
        app()->setLocale('bg-BG');

        $this->assertSame(['Cancel' => 'Cancel', 'Save' => 'Запис'], Translator::translations());
    }

    public function test_editing_a_fallback_file_is_picked_up_without_clearing_the_cache(): void
    {
        config(['translations.fallback_chain' => true]);
        $dir = $this->langFiles(['bg-BG' => ['Save' => 'Запис'], 'en-GB' => ['Cancel' => 'Cancel']]);
        $mtime = filemtime("{$dir}/en-GB.json");

        $this->assertSame(['Cancel' => 'Cancel', 'Save' => 'Запис'], Translator::translations('bg-BG'));

        file_put_contents("{$dir}/en-GB.json", json_encode(['Cancel' => 'Cancel it']));
        touch("{$dir}/en-GB.json", $mtime + 10);

        $this->assertSame(['Cancel' => 'Cancel it', 'Save' => 'Запис'], Translator::translations('bg-BG'));
    }

    public function test_adding_a_fallback_file_later_is_picked_up(): void
    {
        config(['translations.fallback_chain' => true]);
        $dir = $this->langFiles(['bg-BG' => ['Save' => 'Запис']]);

        $this->assertSame(['Save' => 'Запис'], Translator::translations('bg-BG'));

        file_put_contents("{$dir}/en-GB.json", json_encode(['Cancel' => 'Cancel']));

        $this->assertSame(['Cancel' => 'Cancel', 'Save' => 'Запис'], Translator::translations('bg-BG'));
    }

    public function test_changing_the_app_fallback_locale_changes_the_result_without_clearing_the_cache(): void
    {
        config(['translations.fallback_chain' => true]);
        $this->langFiles(['bg-BG' => ['Save' => 'Запис'], 'en-GB' => ['A' => 'a'], 'de-DE' => ['A' => 'b']]);

        $this->assertSame(['A' => 'a', 'Save' => 'Запис'], Translator::translations('bg-BG'));

        config(['app.fallback_locale' => 'de-DE']);

        $this->assertSame(['A' => 'b', 'Save' => 'Запис'], Translator::translations('bg-BG'));
    }

    public function test_integer_like_keys_are_kept(): void
    {
        config(['translations.fallback_chain' => true]);
        $this->langFiles(['bg-BG' => ['404' => 'Не е намерено'], 'en-GB' => ['404' => 'Not found', '500' => 'Server error']]);

        $this->assertSame([404 => 'Не е намерено', 500 => 'Server error'], Translator::translations('bg-BG'));
    }

    public function test_invalid_json_in_a_fallback_file_is_an_error_naming_that_file(): void
    {
        config(['translations.fallback_chain' => true]);
        $dir = $this->langFiles(['bg-BG' => ['Save' => 'Запис']]);
        file_put_contents("{$dir}/en-GB.json", '{ not json');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("{$dir}/en-GB.json");

        Translator::translations('bg-BG');
    }

    public function test_the_chain_off_ignores_a_broken_fallback_file(): void
    {
        $dir = $this->langFiles(['bg-BG' => ['Save' => 'Запис']]);
        file_put_contents("{$dir}/en-GB.json", '{ not json');

        $this->assertSame(['Save' => 'Запис'], Translator::translations('bg-BG'));
    }

    public function test_locale_chain(): void
    {
        $this->assertSame(['en-US', 'en', 'en-GB'], Translator::localeChain('en-US'));
        $this->assertSame(['bg-BG', 'bg', 'en-GB'], Translator::localeChain('bg-BG'));
        $this->assertSame(['en-GB', 'en'], Translator::localeChain('en-GB'));
        $this->assertSame(['en', 'en-GB'], Translator::localeChain('en'));
    }

    public function test_locale_chain_drops_empty_values_and_duplicates(): void
    {
        config(['app.fallback_locale' => 'en']);
        $this->assertSame(['en'], Translator::localeChain('en'));
        $this->assertSame(['bg', 'en'], Translator::localeChain('bg'));

        config(['app.fallback_locale' => '']);
        $this->assertSame(['bg-BG', 'bg'], Translator::localeChain('bg-BG'));
        $this->assertSame(['bg'], Translator::localeChain('bg'));
    }
}
