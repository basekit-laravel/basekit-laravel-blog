<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelBlog\Services;

use BasekitLaravel\BasekitLaravelBlog\Models\PostRevision;
use BasekitLaravel\BasekitLaravelBlog\Models\PostTranslation;

/**
 * Snapshots translations before they are overwritten.
 *
 * The revision stores the state that is about to be replaced, so restoring is a
 * copy from a revision rather than a diff reconstruction. Only the newest
 * revisions are kept per post and locale, which bounds the table without a
 * scheduled cleanup job.
 */
final class RevisionRecorder
{
    public function record(PostTranslation $translation, ?int $createdBy = null): ?PostRevision
    {
        if (! $this->enabled()) {
            return null;
        }

        $revision = PostRevision::query()->create([
            'post_id' => $translation->post_id,
            'locale' => $translation->locale,
            'title' => $translation->title,
            'excerpt' => $translation->excerpt,
            'content' => $translation->content,
            'status' => $translation->status,
            'published_at' => $translation->published_at,
            'is_featured' => $translation->is_featured,
            'featured_image' => $translation->featured_image,
            'image_alt' => $translation->image_alt,
            'meta_title' => $translation->meta_title,
            'meta_description' => $translation->meta_description,
            'created_by' => $createdBy,
        ]);

        $this->prune($translation, $revision);

        return $revision;
    }

    /**
     * Drop revisions beyond the configured limit for the same post and locale.
     */
    private function prune(PostTranslation $translation, PostRevision $revision): void
    {
        $limit = max(0, (int) config('basekit-laravel-blog.revisions.limit', 20));

        if ($limit === 0) {
            $revision->delete();

            return;
        }

        $stale = PostRevision::query()
            ->where('post_id', $translation->post_id)
            ->where('locale', $translation->locale)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->offset($limit)
            ->limit($limit)
            ->pluck('id');

        if ($stale->isNotEmpty()) {
            PostRevision::query()->whereIn('id', $stale)->delete();
        }
    }

    private function enabled(): bool
    {
        return (bool) config('basekit-laravel-blog.revisions.enabled', true);
    }
}
