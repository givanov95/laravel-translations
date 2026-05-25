<?php

declare(strict_types=1);

namespace Givanov95\LaravelTranslations\Tests\Feature;

use Givanov95\LaravelTranslations\Concerns\HasTranslation;
use Givanov95\LaravelTranslations\Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

class FakeCategory extends Model
{
    use HasTranslation;

    protected $table = 'fake_categories';

    protected $fillable = ['name'];
}

class HasTranslationTest extends TestCase
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

    public function test_set_translation_persists_in_saved_hook(): void
    {
        $category = new FakeCategory(['name' => 'shoes']);

        $category->setTranslation('en', 'title', 'Shoes')
            ->setTranslation('bg', 'title', 'Обувки')
            ->save();

        $this->assertDatabaseCount('translations', 2);
        $this->assertDatabaseHas('translations', [
            'translatable_type' => FakeCategory::class,
            'translatable_id'   => $category->id,
            'locale'            => 'en',
            'key'               => 'title',
            'text'              => 'Shoes',
        ]);
    }

    public function test_deleting_the_model_cascades_translations(): void
    {
        $category = new FakeCategory(['name' => 'shoes']);
        $category->setTranslation('en', 'title', 'Shoes')->save();

        $this->assertDatabaseCount('translations', 1);

        $category->delete();

        $this->assertDatabaseCount('translations', 0);
    }

    public function test_set_translation_accepts_backed_enum_for_locale(): void
    {
        $enum = FakeLocale::bg;

        $category = new FakeCategory(['name' => 'x']);
        $category->setTranslation($enum, 'title', 'Stripped')->save();

        $this->assertDatabaseHas('translations', [
            'locale' => 'bg',
            'key'    => 'title',
        ]);
    }

    public function test_with_translations_scope_eager_loads_only_current_locale(): void
    {
        $category = new FakeCategory(['name' => 'x']);
        $category->setTranslation('en', 'title', 'English')
            ->setTranslation('bg', 'title', 'Bulgarian')
            ->save();

        app()->setLocale('en');
        $loaded = FakeCategory::withTranslations()->find($category->id);

        $this->assertCount(1, $loaded->translations);
        $this->assertSame('en', $loaded->translations->first()->locale);
    }
}

enum FakeLocale: string
{
    case en = 'en';
    case bg = 'bg';
}
