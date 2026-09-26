<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelBlog\Actions;

use BasekitLaravel\BasekitLaravelBlog\Models\Post;
use BasekitLaravel\BasekitLaravelSeo\Services\SitemapCache;

/**
 * Brings a soft deleted post back, with all of its locales and slugs intact.
 */
final readonly class RestorePost
{
    public function __construct(
        private SitemapCache $sitemapCache,
    ) {}

    public function execute(Post $post): Post
    {
        $post->restore();

        $this->sitemapCache->clear();

        return $post;
    }
}
