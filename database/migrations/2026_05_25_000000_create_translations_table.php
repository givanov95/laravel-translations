<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('translations', function (Blueprint $table) {
            $table->id();
            $table->char('locale', 5);
            $table->morphs('translatable');
            $table->string('key');
            $table->longText('text');
            $table->timestamps();

            $table->unique(
                ['locale', 'translatable_type', 'translatable_id', 'key'],
                'unique_translation'
            );
            $table->index(['locale'], 'translations_locale_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('translations');
    }
};
