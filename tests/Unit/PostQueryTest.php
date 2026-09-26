<?php

declare(strict_types=1);

use BasekitLaravel\BasekitLaravelBlog\Enums\PostStatus;
use BasekitLaravel\BasekitLaravelBlog\Models\Category;
use BasekitLaravel\BasekitLaravelBlog\Services\PostQuery;
use Illuminate\Support\Facades\DB;

beforeEach(function (): void {
    $this->posts = new PostQuery;
});

it('returns only the visible translations of the requested locale', function (): void {
    makePost('English post');
    makePost('Magyar bejegyzés', locale: 'hu');
    makePost('Draft post', ['status' => PostStatus::Draft, 'published_at' => null]);
    makePost('Future post', ['status' => PostStatus::Scheduled, 'published_at' => now()->addDay()]);

    $titles = $this->posts->latest('en', 10)->pluck('title')->all();

    expect($titles)->toBe(['English post']);
});

it('orders the listing by publication date, newest first', function (): void {
    makePost('Older', ['published_at' => now()->subWeek()]);
    makePost('Newer', ['published_at' => now()->subDay()]);
    makePost('Newest', ['published_at' => now()->subMinute()]);

    expect($this->posts->latest('en', 10)->pluck('title')->all())
        ->toBe(['Newest', 'Newer', 'Older']);
});

it('paginates the listing with a per page limit', function (): void {
    makePost('First', ['published_at' => now()->subDays(3)]);
    makePost('Second', ['published_at' => now()->subDays(2)]);
    makePost('Third', ['published_at' => now()->subDay()]);

    $page = $this->posts->paginate('en', 2);

    expect($page->total())->toBe(3)
        ->and($page->items())->toHaveCount(2)
        ->and($page->items()[0]->title)->toBe('Third');
});

it('reveals a scheduled translation once its date has passed', function (): void {
    makePost('Scheduled', [
        'status' => PostStatus::Scheduled,
        'published_at' => now()->subMinute(),
    ]);

    expect($this->posts->latest('en', 10))->toHaveCount(1);
});

it('limits the feed to the requested number of posts', function (): void {
    makePost('First', ['published_at' => now()->subDays(2)]);
    makePost('Second', ['published_at' => now()->subDay()]);
    makePost('Third', ['published_at' => now()]);

    expect($this->posts->latest('en', 2)->pluck('title')->all())->toBe(['Third', 'Second']);
});

it('suggests related posts that share a category', function (): void {
    $category = Category::factory()->create();

    $first = makePost('First');
    $first->categories()->attach($category);

    $second = makePost('Second');
    $second->categories()->attach($category);

    makePost('Unrelated');

    $related = $this->posts->related($first->translations()->firstOrFail(), 5);

    expect($related->pluck('title')->all())->toBe(['Second']);
});

it('never suggests the post itself', function (): void {
    $post = makePost('Only post');

    expect($this->posts->related($post->translations()->firstOrFail(), 5))->toHaveCount(0);
});

it('lists the categories of the visible posts of a locale', function (): void {
    $laravel = Category::factory()->create();
    $laravel->translations()->create(['locale' => 'en', 'name' => 'Laravel']);
    $laravel->translations()->create(['locale' => 'hu', 'name' => 'Laravel (hu)']);

    $unused = Category::factory()->create();
    $unused->translations()->create(['locale' => 'en', 'name' => 'Unused']);

    $post = makePost('Post');
    $post->categories()->attach($laravel);

    $categories = $this->posts->categoriesIn('en');

    expect($categories)->toHaveCount(1)
        ->and($categories->first()->translation('en')?->name)->toBe('Laravel');
});

it('does not leak posts of other locales into a listing', function (): void {
    makePost('English only');
    makePost('Magyar only', locale: 'hu');

    expect($this->posts->latest('hu', 10)->pluck('title')->all())->toBe(['Magyar only']);
});

it('eager loads the post, its slugs and its author without extra queries', function (): void {
    makePost('First', ['published_at' => now()->subDays(2)]);
    makePost('Second', ['published_at' => now()->subDay()]);
    makePost('Third', ['published_at' => now()]);

    $queries = 0;
    DB::listen(function () use (&$queries): void {
        $queries++;
    });

    $translations = $this->posts->latest('en', 10);
    $translations->each(fn ($translation) => $translation->post->slug('en'));

    expect($translations)->toHaveCount(3)
        ->and($translations->first()->relationLoaded('post'))->toBeTrue()
        ->and($translations->first()->post->relationLoaded('slugs'))->toBeTrue()
        ->and($translations->first()->post->relationLoaded('author'))->toBeTrue()
        // One query for the translations, one for the posts, one for the slugs —
        // the same number for one post as for three.
        ->and($queries)->toBe(3);
});
