<?php

declare(strict_types=1);

use BasekitLaravel\BasekitLaravelBlog\Models\Category;
use BasekitLaravel\BasekitLaravelBlog\Models\Post;
use BasekitLaravel\BasekitLaravelBlog\Models\PostRevision;
use BasekitLaravel\BasekitLaravelBlog\Models\PostTranslation;
use BasekitLaravel\BasekitLaravelBlog\Models\Tag;
use BasekitLaravel\BasekitLaravelBlog\Tests\TestSupport\Models\User;

it('resolves a translation through the loaded relation', function (): void {
    $post = Post::factory()->create();
    PostTranslation::factory()->forLocale('en')->create(['post_id' => $post->id]);
    PostTranslation::factory()->forLocale('hu')->create(['post_id' => $post->id]);

    $post->load('translations');

    expect($post->translation('hu')?->locale)->toBe('hu')
        ->and($post->translation('de'))->toBeNull();
});

it('resolves a translation without loading the whole relation', function (): void {
    $post = Post::factory()->create();
    PostTranslation::factory()->forLocale('hu')->create(['post_id' => $post->id]);

    expect(Post::query()->findOrFail($post->id)->translation('hu')?->locale)->toBe('hu');
});

it('links categories and tags to posts in both directions', function (): void {
    $post = Post::factory()->create();
    $category = Category::factory()->create();
    $tag = Tag::factory()->create();

    $post->categories()->attach($category);
    $tag->posts()->attach($post);

    $post->load('categories');

    expect($post->categories->pluck('id')->all())->toBe([$category->id])
        ->and($category->posts()->pluck('posts.id')->all())->toBe([$post->id])
        ->and($tag->posts()->pluck('posts.id')->all())->toBe([$post->id]);
});

it('removes pivot rows when a post is detached', function (): void {
    $post = Post::factory()->create();
    $category = Category::factory()->create();

    $post->categories()->attach($category);
    $post->categories()->detach($category);

    expect($post->fresh()->categories()->count())->toBe(0);
});

it('removes pivot rows when the tag side is detached', function (): void {
    $post = Post::factory()->create();
    $tag = Tag::factory()->create();

    $tag->posts()->attach($post);
    $tag->posts()->detach($post);

    expect($tag->posts()->count())->toBe(0);
});

it('translates categories and tags per locale', function (): void {
    $category = Category::factory()->create();
    $tag = Tag::factory()->create();

    $category->translations()->create(['locale' => 'en', 'name' => 'Laravel']);
    $category->translations()->create(['locale' => 'hu', 'name' => 'Laravel (hu)']);
    $tag->translations()->create(['locale' => 'en', 'name' => 'PHP']);
    $tag->translations()->create(['locale' => 'hu', 'name' => 'PHP (hu)']);

    expect($category->translations()->forLocale('hu')->firstOrFail()->name)->toBe('Laravel (hu)')
        ->and($tag->translations()->forLocale('hu')->firstOrFail()->name)->toBe('PHP (hu)');
});

it('resolves the author from the configured user model', function (): void {
    $author = User::create(['name' => 'Gergő']);
    $post = Post::factory()->create(['author_id' => $author->id]);

    expect($post->author)->toBeInstanceOf(User::class)
        ->and($post->author->name)->toBe('Gergő');
});

it('has no author when none is assigned', function (): void {
    expect(Post::factory()->create()->author)->toBeNull();
});

it('stores revision snapshots for a post and locale', function (): void {
    $post = Post::factory()->create();
    $author = User::create(['name' => 'Gergő']);

    $revision = PostRevision::factory()->create([
        'post_id' => $post->id,
        'created_by' => $author->id,
    ]);

    expect($revision->post->is($post))->toBeTrue()
        ->and($revision->created_at)->toBeInstanceOf(DateTimeInterface::class)
        ->and($revision->post->revisions()->count())->toBe(1);
});
