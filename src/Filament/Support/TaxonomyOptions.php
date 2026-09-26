<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelBlog\Filament\Support;

use BasekitLaravel\BasekitLaravelBlog\Contracts\HasLocalizedName;
use BasekitLaravel\BasekitLaravelBlog\Support\LocaleRegistry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Builds the options of a translated select from the default locale name of
 * each term, so a site with localized taxonomy still shows readable labels.
 */
final class TaxonomyOptions
{
    /**
     * @param  class-string<Model&HasLocalizedName>  $model
     * @return array<int|string, string>
     */
    public static function make(string $model, ?string $search = null): array
    {
        $locales = LocaleRegistry::fromConfig()->all();

        return $model::query()
            ->with('translations')
            ->when($search !== null, function (Builder $query) use ($search): void {
                $query->whereHas('translations', function (Builder $query) use ($search): void {
                    $query->where('name', 'like', '%'.$search.'%');
                });
            })
            ->get()
            ->mapWithKeys(fn (Model $term): array => [$term->getKey() => self::label($term, $locales)])
            ->all();
    }

    /**
     * @param  Model&HasLocalizedName  $term
     * @param  list<string>  $locales
     */
    private static function label(Model $term, array $locales): string
    {
        foreach ($locales as $locale) {
            $name = $term->name($locale);

            if ($name !== '') {
                return $name;
            }
        }

        return $term->name();
    }
}
