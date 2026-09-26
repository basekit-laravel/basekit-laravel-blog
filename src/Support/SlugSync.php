<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelBlog\Support;

use BasekitLaravel\BasekitLaravelBlog\Contracts\HasLocalizedSlugs;

/**
 * Keeps one slug per locale in sync with the localized value that produced it.
 *
 * A slug is generated the first time a locale is saved and is never rewritten
 * afterwards, so renaming a post does not break the URLs already published,
 * indexed or shared. Saving an explicit slug — see the write actions — is the
 * supported way to change it.
 */
final class SlugSync
{
    public function ensureSlug(HasLocalizedSlugs $model, string $locale, string $value): void
    {
        if (! (bool) config('basekit-laravel-blog.generate_slugs', true) || $value === '') {
            return;
        }

        if ($model->hasSlug($locale)) {
            return;
        }

        $model->setSlugFrom($value, $locale);
    }

    /**
     * Drop the slug of a locale that no longer has a translation, so a removed
     * locale cannot keep claiming a URL that now resolves elsewhere.
     */
    public function forgetSlug(HasLocalizedSlugs $model, string $locale): void
    {
        if ($model->hasSlug($locale)) {
            $model->setSlug(null, $locale);
        }
    }
}
