<?php

declare(strict_types=1);
use BasekitLaravel\BasekitLaravelBlog\Models\Post;
use Carbon\CarbonInterface;
use TailwindMerge\TailwindMerge;

/*
 * The Basekit Blocks `frame` component depends on a global `twMerge()` helper
 * that is normally provided by the consuming application. In standalone package
 * tests we define a guarded fallback so block-rendering tests run without the
 * host app, delegating to the tailwind-merge engine.
 */

if (! function_exists('twMerge')) {
    function twMerge(...$classLists): string
    {
        return TailwindMerge::instance()->merge($classLists);
    }
}

if (! function_exists('makePost')) {
    function makePost(string $slug, string $category, ?CarbonInterface $publishedAt = null): Post
    {
        return Post::create([
            'title' => str($slug)->headline()->toString(),
            'slug' => $slug,
            'excerpt' => 'Exc.',
            'content' => '<p>Body.</p>',
            'category' => $category,
            'is_published' => true,
            'published_at' => $publishedAt ?? now()->subDay(),
        ]);
    }
}
