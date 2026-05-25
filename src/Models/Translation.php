<?php

declare(strict_types=1);

namespace Givanov95\LaravelTranslations\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $id
 * @property string $locale
 * @property string $translatable_type
 * @property int $translatable_id
 * @property string $key
 * @property string $text
 */
class Translation extends Model
{
    protected $fillable = [
        'locale',
        'translatable_type',
        'translatable_id',
        'key',
        'text',
    ];

    public function translatable(): MorphTo
    {
        return $this->morphTo();
    }
}
