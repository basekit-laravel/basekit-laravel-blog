<?php

declare(strict_types=1);

use BasekitLaravel\BasekitLaravelBlog\BasekitLaravelBlogServiceProvider;
use BasekitLaravel\BasekitLaravelBlog\Models\Category;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

it('publishes the package migrations without also loading them', function (): void {
    $published = ServiceProvider::pathsToPublish(
        BasekitLaravelBlogServiceProvider::class,
        'basekit-laravel-blog-migrations',
    );

    expect($published)->not->toBeEmpty()
        ->and(Schema::hasTable('posts'))->toBeTrue()
        ->and(Schema::hasTable('post_translations'))->toBeTrue();
});

it('keeps published migration timestamps so applied migrations are not re-run', function (): void {
    $published = ServiceProvider::pathsToPublish(
        BasekitLaravelBlogServiceProvider::class,
        'basekit-laravel-blog-migrations',
    );

    $files = [];

    foreach (array_keys($published) as $from) {
        $files = [...$files, ...glob($from.'/*.php') ?: []];
    }

    expect($files)->not->toBeEmpty();

    foreach ($files as $file) {
        expect(basename($file))->toMatch('/^\d{4}_\d{2}_\d{2}_\d{6}_/');
    }
});

it('creates the posts table with the author and a soft delete', function (): void {
    expect(Schema::hasColumns('posts', ['id', 'author_id', 'created_at', 'updated_at', 'deleted_at']))->toBeTrue();
});

it('creates the localized post translations', function (): void {
    expect(Schema::hasColumns('post_translations', [
        'id', 'post_id', 'locale', 'title', 'excerpt', 'content', 'status', 'published_at',
        'is_featured', 'featured_image', 'image_alt', 'meta_title', 'meta_description',
    ]))->toBeTrue();
});

it('creates the taxonomy tables and their pivots', function (): void {
    expect(Schema::hasTable('categories'))->toBeTrue()
        ->and(Schema::hasTable('category_translations'))->toBeTrue()
        ->and(Schema::hasTable('tags'))->toBeTrue()
        ->and(Schema::hasTable('tag_translations'))->toBeTrue()
        ->and(Schema::hasTable('category_post'))->toBeTrue()
        ->and(Schema::hasTable('post_tag'))->toBeTrue();
});

it('creates the revision table', function (): void {
    expect(Schema::hasColumns('post_revisions', [
        'id', 'post_id', 'locale', 'title', 'content', 'status', 'created_by', 'created_at',
    ]))->toBeTrue();
});

it('stores the identity and the localized content in separate rows', function (): void {
    $post = makePost('Caching in Laravel');

    expect($post->getTable())->toBe('posts')
        ->and(array_keys($post->getAttributes()))
        ->toContain('id', 'author_id', 'created_at', 'updated_at')
        ->not->toContain('title', 'excerpt', 'content', 'slug', 'status', 'published_at');
});

it('enforces one translation per locale', function (): void {
    $post = makePost('Caching in Laravel');

    expect(fn () => $post->translations()->create([
        'locale' => 'en',
        'title' => 'Duplicate',
    ]))->toThrow(Exception::class);
});

it('removes a translation with its post', function (): void {
    $post = makePost('Caching in Laravel');

    DB::table('posts')->where('id', $post->id)->delete();

    expect(DB::table('post_translations')->where('post_id', $post->id)->exists())->toBeFalse();
});

it('removes a translation and its slug on a force delete', function (): void {
    $post = makePost('Caching in Laravel');

    $post->forceDelete();

    expect(DB::table('post_translations')->where('post_id', $post->id)->exists())->toBeFalse()
        ->and(DB::table('slugs')->where('sluggable_id', $post->id)->exists())->toBeFalse();
});

it('detaches the taxonomy when a post is deleted', function (): void {
    $category = Category::factory()->create();

    $post = makePost('Caching in Laravel');
    $post->categories()->attach($category);

    $post->forceDelete();

    expect(DB::table('category_post')->where('post_id', $post->id)->exists())->toBeFalse();
});
