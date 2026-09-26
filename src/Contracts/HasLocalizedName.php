<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelBlog\Contracts;

/**
 * Implemented by models whose human readable name is a translated attribute,
 * such as categories and tags.
 */
interface HasLocalizedName
{
    /**
     * The name in the given locale, falling back to the first available
     * translation so the model always has something to show.
     */
    public function name(?string $locale = null): string;
}
