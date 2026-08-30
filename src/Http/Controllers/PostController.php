<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelBlog\Http\Controllers;

use BasekitLaravel\BasekitLaravelBlog\Models\Post;
use BasekitLaravel\BasekitLaravelBlog\Services\PostService;
use Illuminate\Contracts\View\View;

final class PostController
{
    public function index(PostService $posts): View
    {
        return view('basekit-laravel-blog::'.config('basekit-laravel-blog.views.index', 'blog.index'), [
            'posts' => $posts->listing(
                (int) config('basekit-laravel-blog.per_page', 6),
                (bool) config('basekit-laravel-blog.paginate', true),
            ),
            'categories' => $posts->categories(),
        ]);
    }

    public function show(Post $post, PostService $posts): View
    {
        abort_unless($post->isPublished(), 404);

        return view('basekit-laravel-blog::'.config('basekit-laravel-blog.views.article', 'blog.article'), [
            'post' => $post,
            'related' => $posts->related($post),
        ]);
    }
}
