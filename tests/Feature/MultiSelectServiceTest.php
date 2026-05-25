<?php

declare(strict_types=1);

namespace Givanov95\LaravelTranslations\Tests\Feature;

use Givanov95\LaravelTranslations\Services\MultiSelectService;
use Givanov95\LaravelTranslations\Tests\TestCase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

class MultiSelectServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('fake_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
    }

    public function test_data_for_select_returns_collection_keyed_by_text(): void
    {
        FakeCategory::create(['name' => 'Shoes']);
        FakeCategory::create(['name' => 'Bags']);

        $result = (new MultiSelectService(FakeCategory::class))->dataForSelect();

        $this->assertSame(['Shoes' => 1, 'Bags' => 2], $result->all());
    }

    public function test_data_for_select_with_translations_skips_untranslated_rows(): void
    {
        $shoes = FakeCategory::create(['name' => 'Shoes']);
        FakeCategory::create(['name' => 'Bags']);

        $shoes->setTranslation('en', 'title', 'Translated Shoes')->save();

        app()->setLocale('en');
        $result = (new MultiSelectService(FakeCategory::class))->dataForSelectWithTranslations('title');

        $this->assertSame(['Translated Shoes' => $shoes->id], $result->all());
    }

    public function test_data_for_select_from_array_accepts_static_input(): void
    {
        $result = MultiSelectService::dataForSelectFromArray([
            ['id' => 1, 'name' => 'A'],
            ['id' => 2, 'name' => 'B'],
        ]);

        $this->assertSame(['A' => 1, 'B' => 2], $result->all());
    }
}
