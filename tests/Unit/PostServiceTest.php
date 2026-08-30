<?php

declare(strict_types=1);

use BasekitLaravel\BasekitLaravelBlog\Services\PostService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

it('returns published posts ordered by most recent for the listing', function (): void {
    $older = makePost('older', 'Laravel', now()->subDays(2));
    $newer = makePost('newer', 'Laravel', now()->subDay());
    makePost('draft', 'Laravel', now()->subDay())->update(['is_published' => false]);

    $posts = (new PostService)->listing(6);

    expect($posts)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($posts->pluck('id')->all())->toBe([$newer->id, $older->id]);
});

it('returns related posts preferring the same category and excluding the current one', function (): void {
    $current = makePost('current', 'Laravel');
    $sameCategory = makePost('same-category', 'Laravel');
    makePost('other-draft', 'Vue')->update(['is_published' => false]);

    $related = (new PostService)->related($current, 10);

    expect($related->pluck('id'))
        ->toContain($sameCategory->id)
        ->not->toContain($current->id);
});

it('lists published categories only', function (): void {
    makePost('post-one', 'Laravel');
    makePost('post-draft', 'Hidden')->update(['is_published' => false]);

    expect((new PostService)->categories()->all())->toBe(['Laravel']);
});

it('builds a feed limited to recent published posts', function (): void {
    $older = makePost('older', 'Laravel', now()->subDays(2));
    $newer = makePost('newer', 'Laravel', now()->subDay());

    $feed = (new PostService)->feed(1);

    expect($feed->pluck('id')->all())->toBe([$newer->id])
        ->and($feed)->not->toContain($older);
});
