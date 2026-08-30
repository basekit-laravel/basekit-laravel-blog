<?php

declare(strict_types=1);

use BasekitLaravel\BasekitLaravelBlog\Models\Post;

beforeEach(function (): void {
    Post::create([
        'title' => 'Caching in Laravel',
        'slug' => 'caching-in-laravel',
        'excerpt' => 'A practical guide.',
        'content' => '<p>Body text.</p>',
        'category' => 'Laravel',
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);

    Post::create([
        'title' => 'Hidden post',
        'slug' => 'hidden-post',
        'excerpt' => 'A draft.',
        'content' => '<p>Draft.</p>',
        'is_published' => false,
        'published_at' => now()->subDay(),
    ]);
});

it('lists published posts on the index', function (): void {
    $this->get('/blog')
        ->assertOk()
        ->assertSee('Caching in Laravel')
        ->assertDontSee('Hidden post');
});

it('renders a published post', function (): void {
    $this->get('/blog/caching-in-laravel')
        ->assertOk()
        ->assertSee('Caching in Laravel')
        ->assertSee('Body text.');
});

it('returns 404 for an unpublished post', function (): void {
    $this->get('/blog/hidden-post')->assertNotFound();
});

it('returns 404 for a not-yet-published post', function (): void {
    Post::create([
        'title' => 'Future',
        'slug' => 'future',
        'excerpt' => 'Later.',
        'content' => '<p>Later.</p>',
        'is_published' => true,
        'published_at' => now()->addDay(),
    ]);

    $this->get('/blog/future')->assertNotFound();
});

it('returns 404 for an unknown slug', function (): void {
    $this->get('/blog/missing')->assertNotFound();
});

it('serves an rss feed', function (): void {
    $this->get('/blog/rss')
        ->assertOk()
        ->assertHeader('Content-Type', 'application/rss+xml; charset=utf-8')
        ->assertSee('Caching in Laravel');
});
