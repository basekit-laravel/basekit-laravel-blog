<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelBlog\Support;

use BasekitLaravel\BasekitLaravelBlog\Contracts\HasLocalizedSlugs;

/**
 * Locale aware URLs for the blog.
 *
 * Every route is registered per locale, so link building is a matter of naming
 * the locale. A post that has no slug in the requested locale is not
 * addressable and falls back to the index of that locale instead of producing a
 * broken link.
 */
final readonly class BlogUrl
{
    public function __construct(
        private LocaleRegistry $locales,
        private bool $localePrefix = false,
    ) {}

    public static function fromConfig(): self
    {
        return new self(
            LocaleRegistry::fromConfig(),
            (bool) config('basekit-laravel-blog.locale_prefix', false),
        );
    }

    /**
     * Whether the blog serves prefixed URLs for every locale.
     */
    public function hasLocalePrefix(): bool
    {
        return $this->localePrefix;
    }

    /**
     * Whether the blog has a route for the given locale — and therefore a URL
     * that can be linked, put in a sitemap or offered as an alternate.
     */
    public function serves(string $locale): bool
    {
        $locale = LocaleRegistry::normalize($locale);

        return $this->locales->isSupported($locale)
            && ($this->localePrefix || $locale === $this->locales->default);
    }

    /**
     * The locales the blog actually has routes for. This is a subset of the
     * configured locales: without a prefix every locale but the default shares
     * the index URI and is therefore not separately addressable.
     *
     * @return list<string>
     */
    public function servedLocales(): array
    {
        return array_values(array_filter(
            $this->locales->all(),
            $this->serves(...),
        ));
    }

    /**
     * Whether the blog serves an RSS feed, so views do not link a route that is
     * not registered.
     */
    public function hasFeed(): bool
    {
        return (bool) config('basekit-laravel-blog.rss_enabled', true);
    }

    public function index(string $locale): string
    {
        return route('blog.index.'.$this->locale($locale));
    }

    public function feed(string $locale): string
    {
        return route('blog.rss.'.$this->locale($locale));
    }

    public function post(HasLocalizedSlugs $post, string $locale): string
    {
        $locale = LocaleRegistry::normalize($locale);
        $slug = $post->slug($locale);

        if ($slug === null) {
            return $this->index($locale);
        }

        return route('blog.show.'.$locale, [
            (string) config('basekit-laravel-blog.route_parameter', 'post') => $slug,
        ]);
    }

    private function locale(string $locale): string
    {
        return LocaleRegistry::normalize($locale);
    }
}
