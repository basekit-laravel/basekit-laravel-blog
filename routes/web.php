<?php

declare(strict_types=1);

use BasekitLaravel\BasekitLaravelBlog\Http\Controllers\PostController;
use BasekitLaravel\BasekitLaravelBlog\Http\Controllers\RssController;
use Illuminate\Support\Facades\Route;

$prefix = trim((string) config('basekit-laravel-blog.route_prefix', 'blog'), '/');
$parameter = config('basekit-laravel-blog.route_parameter', 'post');

Route::middleware('web')->group(function () use ($prefix, $parameter): void {
    Route::get($prefix, [PostController::class, 'index'])->name('blog.index');

    if (config('basekit-laravel-blog.rss_enabled', true)) {
        Route::get($prefix.'/rss', RssController::class)->name('blog.rss');
    }

    Route::get($prefix.'/{'.$parameter.'}', [PostController::class, 'show'])->name('blog.show');
});
