<?php

declare(strict_types=1);

use BasekitLaravel\BasekitLaravelBlog\Enums\PostStatus;
use BasekitLaravel\BasekitLaravelBlog\Models\Post;

beforeEach(function (): void {
    makePost('Caching in Laravel');
    makePost('Hidden post', ['status' => PostStatus::Draft, 'published_at' => null]);
});

it('lists published posts on the index', function (): void {
    $this->get('/blog')
        ->assertOk()
        ->assertSee('Caching in Laravel')
        ->assertDontSee('Hidden post');
});

it('renders a published post at its slug', function (): void {
    $this->get('/blog/caching-in-laravel')
        ->assertOk()
        ->assertSee('Caching in Laravel')
        ->assertSee('Body text.');
});

it('returns 404 for a draft', function (): void {
    $this->get('/blog/hidden-post')->assertNotFound();
});

it('returns 404 for a not yet published post', function (): void {
    makePost('Future', [
        'status' => PostStatus::Scheduled,
        'published_at' => now()->addDay(),
    ]);

    $this->get('/blog/future')->assertNotFound();
});

it('returns 404 for an unknown slug', function (): void {
    $this->get('/blog/missing')->assertNotFound();
});

it('serves an rss feed of the default locale', function (): void {
    $this->get('/blog/rss')
        ->assertOk()
        ->assertHeader('Content-Type', 'application/rss+xml; charset=utf-8')
        ->assertSee('Caching in Laravel');
});

it('links the index to the article of the requested locale', function (): void {
    $post = Post::query()->whereHas('translations', fn ($query) => $query->where('title', 'Caching in Laravel'))->firstOrFail();

    $this->get('/blog')
        ->assertOk()
        ->assertSee(url('/blog/'.$post->slug('en')), escape: false);
});
