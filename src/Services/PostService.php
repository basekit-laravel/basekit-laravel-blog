<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelBlog\Services;

use BasekitLaravel\BasekitLaravelBlog\Models\Post;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class PostService
{
    /**
     * Fetch the listing of published posts, optionally paginated.
     *
     * @return Collection<int, Post>|LengthAwarePaginator<Post>
     */
    public function listing(int $perPage, bool $paginate = true): Collection|LengthAwarePaginator
    {
        $query = Post::query()->published()->ordered();

        return $paginate ? $query->paginate($perPage) : $query->get();
    }

    /**
     * Published posts related to the given post, preferring its category.
     *
     * @return Collection<int, Post>
     */
    public function related(Post $post, int $limit = 2): Collection
    {
        return Post::query()
            ->published()
            ->whereKeyNot($post->getKey())
            ->when($post->category, fn (Builder $query, string $category) => $query->where('category', $category))
            ->ordered()
            ->limit($limit)
            ->get();
    }

    /**
     * Latest-published category names used to build listing filters.
     *
     * @return Collection<int, string>
     */
    public function categories(): Collection
    {
        return Post::query()
            ->published()
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->pluck('category')
            ->unique()
            ->sort()
            ->values();
    }

    /**
     * Recent published posts used to build the RSS feed.
     *
     * @return Collection<int, Post>
     */
    public function feed(int $limit = 50): Collection
    {
        return Post::query()
            ->published()
            ->ordered()
            ->limit($limit)
            ->get();
    }
}
