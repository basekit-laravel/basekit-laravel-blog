<?php

declare(strict_types=1);

use BasekitLaravel\BasekitLaravelBlog\Enums\PostStatus;
use BasekitLaravel\BasekitLaravelBlog\Models\Post;
use BasekitLaravel\BasekitLaravelBlog\Models\PostTranslation;
use Illuminate\Database\QueryException;

it('casts translation attributes', function (): void {
    $translation = PostTranslation::factory()->published()->create();

    expect($translation->status)->toBe(PostStatus::Published)
        ->and($translation->is_featured)->toBeFalse()
        ->and($translation->published_at)->toBeInstanceOf(DateTimeInterface::class);
});

it('keeps the post identity free of translated content', function (): void {
    $post = Post::factory()->create();

    expect($post->translations)->toBeEmpty()
        ->and($post->exists)->toBeTrue();
});

it('stores one translation per locale', function (): void {
    $post = Post::factory()->create();

    PostTranslation::factory()->forLocale('en')->create(['post_id' => $post->id]);
    PostTranslation::factory()->forLocale('hu')->create(['post_id' => $post->id]);

    $post->refresh()->load('translations');

    expect($post->translations)->toHaveCount(2)
        ->and($post->translations->pluck('locale')->all())->toBe(['en', 'hu']);
});

it('rejects a duplicate translation for the same locale', function (): void {
    $post = Post::factory()->create();

    PostTranslation::factory()->forLocale('en')->create(['post_id' => $post->id]);

    PostTranslation::factory()->forLocale('en')->create(['post_id' => $post->id]);
})->throws(QueryException::class);

it('filters translations by locale', function (): void {
    $post = Post::factory()->create();

    PostTranslation::factory()->forLocale('en')->create(['post_id' => $post->id]);
    PostTranslation::factory()->forLocale('hu')->create(['post_id' => $post->id]);

    $translations = $post->translations()->forLocale('hu')->get();

    expect($translations)->toHaveCount(1)
        ->and($translations->first()->locale)->toBe('hu');
});

it('exposes published and elapsed scheduled translations as publicly visible', function (): void {
    $post = Post::factory()->create();

    $published = PostTranslation::factory()->forLocale('en')->published()->create(['post_id' => $post->id]);
    $scheduled = PostTranslation::factory()->forLocale('hu')->scheduled(now()->subHour())->create(['post_id' => $post->id]);

    $visible = $post->translations()->publiclyVisible()->pluck('id');

    expect($visible->all())->toBe([$published->id, $scheduled->id])
        ->and($published->isPubliclyVisible())->toBeTrue()
        ->and($scheduled->isPubliclyVisible())->toBeTrue();
});

it('hides drafts, future and undated translations', function (): void {
    $post = Post::factory()->create();

    $draft = PostTranslation::factory()->forLocale('en')->create(['post_id' => $post->id]);
    $future = PostTranslation::factory()->forLocale('hu')->published()->create([
        'post_id' => $post->id,
        'published_at' => now()->addDay(),
    ]);
    $undated = PostTranslation::factory()->forLocale('de')->create([
        'post_id' => $post->id,
        'status' => PostStatus::Published,
        'published_at' => null,
    ]);

    expect($post->translations()->publiclyVisible()->count())->toBe(0)
        ->and($draft->isPubliclyVisible())->toBeFalse()
        ->and($future->isPubliclyVisible())->toBeFalse()
        ->and($undated->isPubliclyVisible())->toBeFalse();
});

it('keeps translations when a post is soft deleted and removes them on force delete', function (): void {
    $post = Post::factory()->create();
    PostTranslation::factory()->create(['post_id' => $post->id]);

    $post->delete();

    expect(Post::query()->count())->toBe(0)
        ->and(Post::withTrashed()->count())->toBe(1)
        ->and(PostTranslation::query()->count())->toBe(1);

    $post->forceDelete();

    expect(Post::withTrashed()->count())->toBe(0)
        ->and(PostTranslation::query()->count())->toBe(0);
});
