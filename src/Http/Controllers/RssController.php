<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelBlog\Http\Controllers;

use BasekitLaravel\BasekitLaravelBlog\Models\Post;
use Illuminate\Http\Response;

final class RssController
{
    public function __invoke(): Response
    {
        $posts = Post::query()
            ->published()
            ->ordered()
            ->take(50)
            ->get();

        return response()
            ->view('basekit-laravel-blog::'.config('basekit-laravel-blog.views.rss', 'blog.rss'), [
                'posts' => $posts,
            ])
            ->header('Content-Type', 'application/rss+xml; charset=utf-8');
    }
}
