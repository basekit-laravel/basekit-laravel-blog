<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelBlog\Actions;

use BasekitLaravel\BasekitLaravelBlog\Data\PostData;
use BasekitLaravel\BasekitLaravelBlog\Data\PostTranslationData;
use BasekitLaravel\BasekitLaravelBlog\Models\Post;
use BasekitLaravel\BasekitLaravelBlog\Models\PostRevision;

/**
 * Writes a revision back onto the post it belongs to.
 *
 * The restore goes through {@see SavePost}, so the state being replaced is
 * snapshotted as a new revision first and the restore itself is undoable. Only
 * the locale of the revision is touched; the other locales, the taxonomy and
 * the slugs are kept as they are.
 */
final readonly class RestoreRevision
{
    public function __construct(
        private SavePost $savePost,
    ) {}

    public function execute(PostRevision $revision, ?int $actorId = null): Post
    {
        $post = $revision->post;

        $data = new PostData(
            translations: [
                $revision->locale => new PostTranslationData(
                    locale: $revision->locale,
                    title: $revision->title,
                    excerpt: $revision->excerpt,
                    content: $revision->content,
                    status: $revision->status,
                    publishedAt: $revision->published_at,
                    isFeatured: $revision->is_featured,
                    featuredImage: $revision->featured_image,
                    imageAlt: $revision->image_alt,
                    metaTitle: $revision->meta_title,
                    metaDescription: $revision->meta_description,
                ),
            ],
            authorId: $post->author_id,
            categoryIds: $post->categories()->pluck('categories.id')->all(),
            tagIds: $post->tags()->pluck('tags.id')->all(),
        );

        return $this->savePost->execute($data, $post, $actorId);
    }
}
