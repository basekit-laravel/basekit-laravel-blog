<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelBlog\Contracts;

/**
 * A model that owns one slug per locale through the slugs package.
 *
 * Implemented by the blog models that `use BasekitLaravel\BasekitLaravelSlugs\HasSlugs`,
 * so the shared services can type against one contract instead of the trait.
 */
interface HasLocalizedSlugs
{
    public function slug(?string $locale = null): ?string;

    public function hasSlug(?string $locale = null): bool;

    /**
     * @return array<string, string>
     */
    public function slugMap(): array;

    public function setSlug(?string $slug, ?string $locale = null): static;

    public function setSlugFrom(string $value, ?string $locale = null): static;
}
