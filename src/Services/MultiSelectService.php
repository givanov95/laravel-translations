<?php

declare(strict_types=1);

namespace Givanov95\LaravelTranslations\Services;

use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;

class MultiSelectService
{
    private Model|Builder $model;

    private string $idColumnName = 'id';

    private string $textColumnName = 'name';

    /**
     * @param class-string<Model>|Model|Builder $modelClass
     */
    public function __construct(string|Model|Builder $modelClass)
    {
        $this->model = match (true) {
            $modelClass instanceof Builder => $modelClass,
            $modelClass instanceof Model   => $modelClass,
            default                        => new $modelClass(),
        };
    }

    public function setIdColumnName(string $idColumnName): self
    {
        $this->idColumnName = $idColumnName;

        return $this;
    }

    public function setTextColumnName(string $textColumnName): self
    {
        $this->textColumnName = $textColumnName;

        return $this;
    }

    /**
     * Returns a Collection keyed by display text with the id as the value:
     *   ['Category A' => 1, 'Category B' => 2].
     */
    public function dataForSelect(): Collection
    {
        return $this->model
            ->get([$this->idColumnName, $this->textColumnName])
            ->pluck($this->idColumnName, $this->textColumnName);
    }

    public static function dataForSelectFromArray(
        array $items,
        string $idColumn = 'id',
        string $textColumn = 'name'
    ): Collection {
        return collect($items)->pluck($idColumn, $textColumn);
    }

    /**
     * Returns a Collection keyed by translated text with the id as the value.
     * Items without a translation for the current locale + key are skipped.
     */
    public function dataForSelectWithTranslations(string $translationKey): Collection
    {
        $input = request()->input('locale', App::getLocale());
        $locale = $input instanceof BackedEnum ? (string) $input->value : (string) $input;

        return $this->model
            ->with(['translations' => fn ($q) => $q->where(['key' => $translationKey, 'locale' => $locale])])
            ->get()
            ->mapWithKeys(function (Model $item) {
                /** @var Collection<int, \Givanov95\LaravelTranslations\Models\Translation> $translations */
                $translations = $item->getRelation('translations');
                $translation = $translations->first();

                return $translation ? [$translation->text => $item->getKey()] : [];
            });
    }
}
