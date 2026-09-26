<?php

declare(strict_types=1);

use BasekitLaravel\BasekitLaravelBlog\Enums\PostStatus;
use BasekitLaravel\BasekitLaravelBlog\Models\Category;
use BasekitLaravel\BasekitLaravelBlog\Models\Post;
use BasekitLaravel\BasekitLaravelBlog\Models\PostTranslation;
use BasekitLaravel\BasekitLaravelBlog\Models\Tag;

it('generates a slug from the title of the first saved locale', function (): void {
    $post = makePost('Caching in Laravel');

    expect($post->slug('en'))->toBe('caching-in-laravel')
        ->and($post->slug('hu'))->toBeNull();
});

it('keeps the slug when the title changes', function (): void {
    $post = makePost('Caching in Laravel');

    $post->translations()->update(['title' => 'Caching in Laravel 13']);

    expect($post->fresh()->slug('en'))->toBe('caching-in-laravel');
});

it('gives every locale its own slug', function (): void {
    $post = makePost('Caching in Laravel');

    $post->translations()->create([
        'locale' => 'hu',
        'title' => 'Gyorsítótárazás Laravelben',
        'status' => PostStatus::Published,
        'published_at' => now()->subDay(),
    ]);

    expect($post->fresh()->slugMap())->toBe([
        'en' => 'caching-in-laravel',
        'hu' => 'gyorsitotarazas-laravelben',
    ]);
});

it('does not generate a slug when slug generation is disabled', function (): void {
    config()->set('basekit-laravel-blog.generate_slugs', false);

    $post = makePost('Caching in Laravel');

    expect($post->slug('en'))->toBeNull();
});

it('generates a slug for categories and tags from their names', function (): void {
    $category = Category::factory()->create();
    $category->translations()->create(['locale' => 'hu', 'name' => 'Laravel tippek']);

    $tag = Tag::factory()->create();
    $tag->translations()->create(['locale' => 'en', 'name' => 'Queue Jobs']);

    expect($category->slug('hu'))->toBe('laravel-tippek')
        ->and($tag->slug('en'))->toBe('queue-jobs');
});

it('resolves a post by the slug of a locale', function (): void {
    $post = makePost('Caching in Laravel');
    $post->translations()->create([
        'locale' => 'hu',
        'title' => 'Gyorsítótárazás',
        'status' => PostStatus::Published,
        'published_at' => now()->subDay(),
    ]);

    expect(Post::query()->whereSlug('caching-in-laravel', 'en')->first()?->is($post))->toBeTrue()
        ->and(Post::query()->whereSlug('gyorsitotarazas', 'hu')->first()?->is($post))->toBeTrue()
        ->and(Post::query()->whereSlug('gyorsitotarazas', 'en')->first())->toBeNull();
});

it('hides a soft deleted post from slug resolution', function (): void {
    $post = makePost('Caching in Laravel');
    $post->delete();

    expect(Post::query()->whereSlug('caching-in-laravel', 'en')->first())->toBeNull()
        ->and(Post::withTrashed()->whereSlug('caching-in-laravel', 'en')->first()?->is($post))->toBeTrue();
});

it('lists the locales a post is published in', function (): void {
    $post = makePost('Caching in Laravel');
    $post->translations()->create([
        'locale' => 'hu',
        'title' => 'Gyorsítótárazás',
        'status' => PostStatus::Draft,
    ]);

    expect($post->publishedLocales())->toBe(['en'])
        ->and($post->isPublishedIn('en'))->toBeTrue()
        ->and($post->isPublishedIn('hu'))->toBeFalse();
});

it('keeps the identity free of translated content', function (): void {
    $post = makePost('Caching in Laravel');

    expect(PostTranslation::query()->count())->toBe(1)
        ->and($post->translations->first()->title)->toBe('Caching in Laravel');
});
