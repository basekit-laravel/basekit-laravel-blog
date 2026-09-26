<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelBlog\Support;

use Illuminate\Database\Eloquent\Model;

/**
 * Resolves the name of a translated model for the default locale, falling back
 * to the first translation that exists.
 *
 * @phpstan-require-extends Model
 */
trait InteractsWithLocalizedNames
{
    public function name(?string $locale = null): string
    {
        $locale ??= LocaleRegistry::fromConfig()->default;

        $translations = $this->relationLoaded('translations')
            ? $this->translations
            : $this->translations()->get();

        $translation = $translations->firstWhere('locale', $locale) ?? $translations->first();

        return (string) ($translation->name ?? '');
    }
}
