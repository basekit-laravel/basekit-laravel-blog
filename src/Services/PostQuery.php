<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelBlog\Services;

use BasekitLaravel\BasekitLaravelBlog\Models\Category;
use BasekitLaravel\BasekitLaravelBlog\Models\Post;
use BasekitLaravel\BasekitLaravelBlog\Models\PostTranslation;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * The public read side of the blog.
 *
 * Every query goes through post_translations because that is where the locale
 * and the publication state live. A post is therefore only ever returned
 * through one of its translations, which makes it impossible to leak a post
 * whose translation for the requested locale is missing or unpublished.
 */
final readonly class PostQuery
{
    /**
     * The visible translations of a locale, newest first.
     *
     * @return Builder<PostTranslation>
     */
    public function visibleIn(string $locale, ?CarbonInterface $at = null): Builder
    {
        return $this->visible($at)->forLocale($locale);
    }

    /**
     * The locales a post is publicly readable in right now.
     *
     * A locale whose translation is draft, scheduled or deleted is not
     * readable, so it must not be advertised anywhere that tells a crawler or a
     * reader where a post can be found.
     *
     * @return list<string>
     */
    public function visibleLocales(Post $post, ?CarbonInterface $at = null): array
    {
        return $post->translations()
            ->publiclyVisible($at)
            ->orderBy('locale')
            ->pluck('locale')
            ->map(static fn (string $locale): string => $locale)
            ->values()
            ->all();
    }

    /**
     * The visible translations of every locale — the sitemap needs all of them,
     * a localized page needs exactly one.
     *
     * @return Builder<PostTranslation>
     */
    public function visible(?CarbonInterface $at = null): Builder
    {
        return PostTranslation::query()
            ->publiclyVisible($at)
            ->with(['post.slugs', 'post.author']);
    }

    /**
     * A page of visible translations for the blog index.
     *
     * @return LengthAwarePaginator<int, PostTranslation>
     */
    public function paginate(string $locale, int $perPage, ?CarbonInterface $at = null): LengthAwarePaginator
    {
        return $this->visibleIn($locale, $at)
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate(max(1, $perPage))
            ->withQueryString();
    }

    /**
     * The most recent visible translations of a locale, for the feed.
     *
     * @return Collection<int, PostTranslation>
     */
    public function latest(string $locale, int $limit, ?CarbonInterface $at = null): Collection
    {
        return $this->visibleIn($locale, $at)
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit(max(1, $limit))
            ->get();
    }

    /**
     * Other visible posts of the same locale, preferring shared categories.
     *
     * @return Collection<int, PostTranslation>
     */
    public function related(PostTranslation $translation, int $limit): Collection
    {
        $post = $translation->post;

        if (! $post instanceof Post) {
            return new Collection;
        }

        $categoryIds = $post->categories()->pluck('categories.id')->all();

        return $this->visibleIn($translation->locale)
            ->where($translation->getTable().'.id', '!=', $translation->getKey())
            ->when(
                $categoryIds !== [],
                static fn (Builder $query): Builder => $query->whereHas(
                    'post.categories',
                    static fn (Builder $categories): Builder => $categories->whereIn('categories.id', $categoryIds),
                ),
            )
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit(max(1, $limit))
            ->get();
    }

    /**
     * The categories used by visible posts of a locale, ordered by localized name.
     *
     * @return Collection<int, Category>
     */
    public function categoriesIn(string $locale): Collection
    {
        return Category::query()
            ->whereHas('posts.translations', static function (Builder $translations) use ($locale): void {
                $translations->forLocale($locale)->publiclyVisible();
            })
            ->with(['translations' => static fn ($translations) => $translations->forLocale($locale)])
            ->get()
            ->sortBy(static fn (Category $category): string => $category->translation($locale)->name ?? '')
            ->values();
    }
}
