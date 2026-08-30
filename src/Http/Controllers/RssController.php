<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelBlog\Http\Controllers;

use BasekitLaravel\BasekitLaravelBlog\Services\PostService;
use Illuminate\Http\Response;

final class RssController
{
    public function __invoke(PostService $posts): Response
    {
        return response()
            ->view('basekit-laravel-blog::'.config('basekit-laravel-blog.views.rss', 'blog.rss'), [
                'posts' => $posts->feed((int) config('basekit-laravel-blog.rss_limit', 50)),
            ])
            ->header('Content-Type', 'application/rss+xml; charset=utf-8');
    }
}
