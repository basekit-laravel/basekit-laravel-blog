<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelBlog\Actions;

use BasekitLaravel\BasekitLaravelBlog\Data\PostData;
use BasekitLaravel\BasekitLaravelBlog\Data\PostTranslationData;
use BasekitLaravel\BasekitLaravelBlog\Models\Post;
use BasekitLaravel\BasekitLaravelBlog\Models\PostTranslation;
use BasekitLaravel\BasekitLaravelBlog\Services\RevisionRecorder;
use BasekitLaravel\BasekitLaravelSeo\Services\SitemapCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Creates or updates a post with all of its locales.
 *
 * The write is atomic: the post, every translation, the taxonomy assignments
 * and the revision snapshots are saved in one transaction, and the sitemap cache
 * is only cleared once that transaction has committed. Locales missing from the
 * payload are left untouched, so a translation can be added without rewriting
 * the post.
 */
final readonly class SavePost
{
    public function __construct(
        private RevisionRecorder $revisions,
        private SitemapCache $sitemapCache,
    ) {}

    public function execute(PostData $data, ?Post $post = null, ?int $actorId = null): Post
    {
        $actorId ??= $this->currentActorId();

        $post = DB::transaction(function () use ($data, $post, $actorId): Post {
            $post ??= new Post;
            $post->author_id = $data->authorId;
            $post->save();

            foreach ($data->translations as $translation) {
                $this->saveTranslation($post, $translation, $actorId);
            }

            if ($data->replaceTranslations) {
                $this->removeUnusedTranslations($post, $data);
            }

            $post->categories()->sync($data->categoryIds);
            $post->tags()->sync($data->tagIds);

            return $post;
        });

        $this->sitemapCache->clear();

        return $post->load(['translations', 'categories', 'tags']);
    }

    private function saveTranslation(Post $post, PostTranslationData $data, ?int $actorId): PostTranslation
    {
        /** @var PostTranslation|null $translation */
        $translation = $post->translations()->where('locale', $data->locale)->first();

        if ($translation instanceof PostTranslation) {
            $this->revisions->record($translation, $actorId);
            $translation->fill($data->toAttributes());
            $translation->save();
        } else {
            $translation = $post->translations()->create($data->toAttributes());
        }

        if ($data->slug !== null) {
            $post->setSlug($data->slug, $data->locale);
        }

        return $translation;
    }

    /**
     * Drop the locales the payload does not carry, for callers that treat the
     * payload as the whole post.
     */
    private function removeUnusedTranslations(Post $post, PostData $data): void
    {
        $post->translations()
            ->whereNotIn('locale', array_keys($data->translations))
            ->get()
            ->each(fn (PostTranslation $translation) => $translation->delete());
    }

    /**
     * The revision author, when the host application has an authenticated user.
     */
    private function currentActorId(): ?int
    {
        if (! app()->bound('auth')) {
            return null;
        }

        $user = auth()->user();

        return $user instanceof Model ? (int) $user->getKey() : null;
    }
}
