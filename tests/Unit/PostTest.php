<?php

declare(strict_types=1);

use BasekitLaravel\BasekitLaravelBlog\Models\Post;

it('casts boolean and date attributes', function (): void {
    $post = Post::create([
        'title' => 'Post',
        'slug' => 'post',
        'excerpt' => 'Exc.',
        'content' => '<p>Body.</p>',
        'tags' => ['laravel', 'php'],
        'featured' => true,
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);

    expect($post->featured)->toBeTrue()
        ->and($post->is_published)->toBeTrue()
        ->and($post->published_at)->toBeInstanceOf(DateTimeInterface::class)
        ->and($post->tags)->toBe(['laravel', 'php']);
});

it('exposes the slug as the route key', function (): void {
    $post = Post::create([
        'title' => 'Post',
        'slug' => 'post',
        'excerpt' => 'Exc.',
        'content' => '<p>Body.</p>',
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);

    expect($post->getRouteKeyName())->toBe('slug');
});

it('formats the published date shortly', function (): void {
    $post = Post::create([
        'title' => 'Post',
        'slug' => 'post',
        'excerpt' => 'Exc.',
        'content' => '<p>Body.</p>',
        'published_at' => now()->parse('2025-11-18 09:00:00'),
    ]);

    expect($post->published_short)->toBe('Nov 18, 2025');
});

it('falls back to the title for the seo title', function (): void {
    $post = Post::create([
        'title' => 'Custom title',
        'slug' => 'post',
        'excerpt' => 'Exc.',
        'content' => '<p>Body.</p>',
    ]);

    expect($post->seo_title)->toBe('Custom title');
});

it('derives a seo description from the excerpt', function (): void {
    $post = Post::create([
        'title' => 'Post',
        'slug' => 'post',
        'excerpt' => 'A useful excerpt about caching.',
        'content' => '<p>Body.</p>',
    ]);

    expect($post->seo_description)->toBe('A useful excerpt about caching.');
});

it('knows whether a post is published', function (): void {
    expect(Post::create([
        'title' => 'Published',
        'slug' => 'published',
        'excerpt' => 'Exc.',
        'content' => '<p>Body.</p>',
        'is_published' => true,
        'published_at' => now()->subDay(),
    ])->isPublished())->toBeTrue();

    expect(Post::create([
        'title' => 'Draft',
        'slug' => 'draft',
        'excerpt' => 'Exc.',
        'content' => '<p>Body.</p>',
        'is_published' => false,
        'published_at' => now()->subDay(),
    ])->isPublished())->toBeFalse();

    expect(Post::create([
        'title' => 'Future',
        'slug' => 'future',
        'excerpt' => 'Exc.',
        'content' => '<p>Body.</p>',
        'is_published' => true,
        'published_at' => now()->addDay(),
    ])->isPublished())->toBeFalse();
});
