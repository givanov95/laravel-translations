<?php

declare(strict_types=1);

namespace Givanov95\LaravelTranslations\Tests\Unit;

use Givanov95\LaravelTranslations\Concerns\MultiSelectDataConversion;
use Givanov95\LaravelTranslations\Tests\TestCase;

enum FakeStatus: string
{
    use MultiSelectDataConversion;
    case Active = 'active';
    case Draft = 'draft';
}

class MultiSelectDataConversionTest extends TestCase
{
    public function test_for_select_returns_value_keyed_by_name(): void
    {
        $this->assertSame([
            'active' => 'Active',
            'draft'  => 'Draft',
        ], FakeStatus::forSelect());
    }

    public function test_for_select_with_returns_indexed_array_of_objects(): void
    {
        $result = FakeStatus::forSelectWith();

        $this->assertCount(2, $result);
        $this->assertSame(['id' => 'active', 'name' => 'Active'], $result[0]);
        $this->assertSame(['id' => 'draft', 'name' => 'Draft'], $result[1]);
    }

    public function test_for_select_with_custom_track_by_and_label(): void
    {
        $result = FakeStatus::forSelectWith('value', 'title');

        $this->assertSame(['value' => 'active', 'title' => 'Active'], $result[0]);
    }
}
