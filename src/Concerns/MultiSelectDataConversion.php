<?php

declare(strict_types=1);

namespace Givanov95\LaravelTranslations\Concerns;

trait MultiSelectDataConversion
{
    public static function forSelect(): array
    {
        return array_combine(
            array_column(self::cases(), 'value'),
            array_column(self::cases(), 'name')
        );
    }

    public static function forSelectWith(string $trackBy = 'id', string $label = 'name'): array
    {
        return array_map(
            fn ($case) => [$trackBy => $case->value, $label => $case->name],
            self::cases()
        );
    }

    public static function forSelectWithTranslate(string $trackBy = 'id', string $label = 'name'): array
    {
        return array_map(
            fn ($case) => [$trackBy => $case->value, $label => __($case->name)],
            self::cases()
        );
    }
}
