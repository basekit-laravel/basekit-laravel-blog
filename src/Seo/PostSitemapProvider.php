<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelBlog\Seo;

use BasekitLaravel\BasekitLaravelBlog\Models\Post;
use BasekitLaravel\BasekitLaravelBlog\Services\PostQuery;
use BasekitLaravel\BasekitLaravelBlog\Support\BlogUrl;
use BasekitLaravel\BasekitLaravelBlog\Support\LocaleRegistry;
use BasekitLaravel\BasekitLaravelSeo\Contracts\SitemapProvider;
use BasekitLaravel\BasekitLaravelSeo\Support\SitemapEntry;

/**
 * Feeds the blog URLs into the sitemap of the host application.
 *
 * The blog index is listed once per locale and every publicly readable
 * translation becomes an entry, because a post translated into three languages
 * has three distinct URLs and therefore three distinct sitemap entries. The
 * aggregation, splitting and caching of the document are the SEO package's job;
 * this provider only decides which URLs exist.
 */
final readonly class PostSitemapProvider implements SitemapProvider
{
    public function __construct(
        private PostQuery $posts,
        private BlogUrl $url,
        private LocaleRegistry $locales,
    ) {}

    #[\Override]
    public function entries(): iterable
    {
        foreach ($this->locales->all() as $locale) {
            if (! $this->url->serves($locale)) {
                continue;
            }

            yield new SitemapEntry(
                loc: $this->url->index($locale),
                changefreq: 'daily',
                priority: 1.0,
            );
        }

        foreach ($this->posts->visible()->cursor() as $translation) {
            $post = $translation->post;

            // A translation in a locale the blog does not serve is not a public
            // URL: no route is registered for it, so it is not a sitemap entry.
            if (! $post instanceof Post
                || ! $this->url->serves($translation->locale)
                || $post->slug($translation->locale) === null) {
                continue;
            }

            yield new SitemapEntry(
                loc: $this->url->post($post, $translation->locale),
                lastmod: $translation->updated_at ?? $translation->published_at,
                changefreq: 'weekly',
                priority: $translation->is_featured ? 0.9 : 0.7,
            );
        }
    }
}
