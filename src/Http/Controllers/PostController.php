<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelBlog\Http\Controllers;

use BasekitLaravel\BasekitLaravelBlog\Models\Post;
use Illuminate\Contracts\View\View;

final class PostController
{
    public function index(): View
    {
        $published = Post::query()->published()->ordered();

        $posts = config('basekit-laravel-blog.paginate', true)
            ? $published->paginate((int) config('basekit-laravel-blog.per_page', 6))
            : $published->get();

        $categories = Post::query()
            ->published()
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->pluck('category')
            ->unique()
            ->sort()
            ->values();

        return view('basekit-laravel-blog::'.config('basekit-laravel-blog.views.index', 'blog.index'), [
            'posts' => $posts,
            'categories' => $categories,
        ]);
    }

    public function show(Post $post): View
    {
        abort_unless($post->is_published && $post->published_at?->isPast(), 404);

        $related = Post::query()
            ->published()
            ->where('id', '!=', $post->id)
            ->when($post->category, fn ($query, $category) => $query->where('category', $category))
            ->ordered()
            ->take(2)
            ->get();

        return view('basekit-laravel-blog::'.config('basekit-laravel-blog.views.article', 'blog.article'), [
            'post' => $post,
            'related' => $related,
        ]);
    }
}
