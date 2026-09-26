<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelBlog\Actions;

use BasekitLaravel\BasekitLaravelBlog\Models\Post;
use BasekitLaravel\BasekitLaravelSeo\Services\SitemapCache;

/**
 * Removes a post from the public blog.
 *
 * Deleting is a soft delete by default so the slug and every locale survive for
 * a restore. A force delete drops the post with its translations, taxonomy
 * assignments and revisions.
 */
final readonly class DeletePost
{
    public function __construct(
        private SitemapCache $sitemapCache,
    ) {}

    public function execute(Post $post, bool $force = false): void
    {
        $force ? $post->forceDelete() : $post->delete();

        $this->sitemapCache->clear();
    }
}
