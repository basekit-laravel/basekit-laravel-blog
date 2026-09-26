<?php

declare(strict_types=1);

use BasekitLaravel\BasekitLaravelBlog\Actions\DeletePost;
use BasekitLaravel\BasekitLaravelBlog\Actions\RestorePost;
use BasekitLaravel\BasekitLaravelBlog\Actions\RestoreRevision;
use BasekitLaravel\BasekitLaravelBlog\Actions\SavePost;
use BasekitLaravel\BasekitLaravelBlog\Data\PostData;
use BasekitLaravel\BasekitLaravelBlog\Enums\PostStatus;
use BasekitLaravel\BasekitLaravelBlog\Models\Category;
use BasekitLaravel\BasekitLaravelBlog\Models\Post;
use BasekitLaravel\BasekitLaravelBlog\Models\Tag;
use BasekitLaravel\BasekitLaravelBlog\Tests\TestSupport\Models\User;
use Illuminate\Validation\ValidationException;

beforeEach(function (): void {
    $this->savePost = app(SavePost::class);
});

/**
 * @return array<string, mixed>
 */
function blogInput(array $overrides = []): array
{
    return array_replace_recursive([
        'translations' => [
            'en' => [
                'title' => 'Caching in Laravel',
                'excerpt' => 'A practical guide.',
                'content' => '<p>Body text.</p>',
                'status' => 'published',
                'published_at' => now()->subDay()->toDateTimeString(),
            ],
        ],
    ], $overrides);
}

it('creates a post with its translation and slug', function (): void {
    $post = $this->savePost->execute(PostData::fromInput(blogInput()));

    expect($post->exists)->toBeTrue()
        ->and($post->translations)->toHaveCount(1)
        ->and($post->translation('en')?->title)->toBe('Caching in Laravel')
        ->and($post->translation('en')?->status)->toBe(PostStatus::Published)
        ->and($post->slug('en'))->toBe('caching-in-laravel');
});

it('creates a post in several locales at once', function (): void {
    $post = $this->savePost->execute(PostData::fromInput(blogInput([
        'translations' => [
            'hu' => [
                'title' => 'Gyorsítótárazás',
                'status' => 'draft',
            ],
        ],
    ])));

    expect($post->translations)->toHaveCount(2)
        ->and($post->translation('hu')?->title)->toBe('Gyorsítótárazás')
        ->and($post->slug('hu'))->toBe('gyorsitotarazas');
});

it('assigns the author from the payload', function (): void {
    $author = User::create(['name' => 'Gergő']);

    $post = $this->savePost->execute(PostData::fromInput(blogInput(['author_id' => $author->id])));

    expect($post->author_id)->toBe($author->id)
        ->and($post->author->name)->toBe('Gergő');
});

it('syncs the taxonomy of a post', function (): void {
    $category = Category::factory()->create();
    $tag = Tag::factory()->create();

    $post = $this->savePost->execute(PostData::fromInput(blogInput([
        'category_ids' => [$category->id],
        'tag_ids' => [$tag->id],
    ])));

    expect($post->categories->pluck('id')->all())->toBe([$category->id])
        ->and($post->tags->pluck('id')->all())->toBe([$tag->id]);
});

it('replaces the taxonomy on the next save', function (): void {
    $first = Category::factory()->create();
    $second = Category::factory()->create();

    $post = $this->savePost->execute(PostData::fromInput(blogInput(['category_ids' => [$first->id]])));
    $post = $this->savePost->execute(PostData::fromInput(blogInput(['category_ids' => [$second->id]])), $post);

    expect($post->categories->pluck('id')->all())->toBe([$second->id]);
});

it('honours a manual slug', function (): void {
    $post = $this->savePost->execute(PostData::fromInput(blogInput([
        'translations' => ['en' => ['slug' => 'custom-url']],
    ])));

    expect($post->slug('en'))->toBe('custom-url');
});

it('records a revision of the state it replaces', function (): void {
    $post = $this->savePost->execute(PostData::fromInput(blogInput()));
    $post = $this->savePost->execute(PostData::fromInput(blogInput([
        'translations' => ['en' => ['title' => 'Caching in Laravel 13']],
    ])), $post);

    $revisions = $post->revisions()->where('locale', 'en')->get();

    expect($revisions)->toHaveCount(1)
        ->and($revisions->first()->title)->toBe('Caching in Laravel')
        ->and($post->translation('en')?->title)->toBe('Caching in Laravel 13');
});

it('keeps a bounded number of revisions', function (): void {
    config()->set('basekit-laravel-blog.revisions.limit', 2);

    $post = $this->savePost->execute(PostData::fromInput(blogInput()));

    for ($i = 0; $i < 4; $i++) {
        $post = $this->savePost->execute(PostData::fromInput(blogInput([
            'translations' => ['en' => ['title' => 'Revision '.$i]],
        ])), $post);
    }

    expect($post->revisions()->where('locale', 'en')->count())->toBe(2);
});

it('does not record revisions when they are disabled', function (): void {
    config()->set('basekit-laravel-blog.revisions.enabled', false);

    $post = $this->savePost->execute(PostData::fromInput(blogInput()));
    $post = $this->savePost->execute(PostData::fromInput(blogInput([
        'translations' => ['en' => ['title' => 'Changed']],
    ])), $post);

    expect($post->revisions()->count())->toBe(0);
});

it('leaves locales missing from the payload untouched', function (): void {
    $post = $this->savePost->execute(PostData::fromInput(blogInput([
        'translations' => ['hu' => ['title' => 'Gyorsítótárazás', 'status' => 'draft']],
    ])));

    $post = $this->savePost->execute(PostData::fromInput(blogInput([
        'translations' => ['en' => ['title' => 'Caching in Laravel 13']],
    ])), $post);

    expect($post->translations)->toHaveCount(2)
        ->and($post->translation('hu')?->title)->toBe('Gyorsítótárazás');
});

it('removes the locales a complete payload leaves out', function (): void {
    $post = $this->savePost->execute(PostData::fromInput(blogInput([
        'translations' => ['hu' => ['title' => 'Gyorsítótárazás', 'status' => 'draft']],
    ])));

    $post = $this->savePost->execute(PostData::fromInput(blogInput([
        'translations' => ['en' => ['title' => 'Caching in Laravel 13']],
        'replace_translations' => true,
    ])), $post);

    expect($post->translations)->toHaveCount(1)
        ->and($post->translation('hu'))->toBeNull()
        ->and($post->translation('en')?->title)->toBe('Caching in Laravel 13');
});

it('keeps the slug of a removed locale out of the post', function (): void {
    $post = $this->savePost->execute(PostData::fromInput(blogInput([
        'translations' => ['hu' => ['title' => 'Gyorsítótárazás', 'status' => 'draft']],
    ])));

    expect($post->slug('hu'))->toBe('gyorsitotarazas');

    $this->savePost->execute(PostData::fromInput(blogInput([
        'translations' => ['en' => ['title' => 'Caching in Laravel 13']],
        'replace_translations' => true,
    ])), $post);

    expect($post->fresh()->slug('hu'))->toBeNull();
});

it('restores a revision through the normal write path', function (): void {
    $post = $this->savePost->execute(PostData::fromInput(blogInput()));
    $post = $this->savePost->execute(PostData::fromInput(blogInput([
        'translations' => ['en' => ['title' => 'Caching in Laravel 13']],
    ])), $post);

    $revision = $post->revisions()->where('locale', 'en')->firstOrFail();

    $post = app(RestoreRevision::class)->execute($revision);

    expect($post->translation('en')?->title)->toBe('Caching in Laravel')
        // Restoring snapshots the state it replaced, so the restore is undoable.
        ->and($post->revisions()->where('locale', 'en')->count())->toBe(2);
});

it('rejects a payload without a title', function (): void {
    $this->savePost->execute(PostData::fromInput(blogInput([
        'translations' => ['en' => ['title' => '']],
    ])));
})->throws(ValidationException::class);

it('rejects a published translation without a publication date', function (): void {
    $this->savePost->execute(PostData::fromInput(blogInput([
        'translations' => ['en' => ['status' => 'published', 'published_at' => null]],
    ])));
})->throws(ValidationException::class);

it('accepts a draft without a publication date', function (): void {
    $post = $this->savePost->execute(PostData::fromInput(blogInput([
        'translations' => ['en' => ['status' => 'draft', 'published_at' => null]],
    ])));

    expect($post->translation('en')?->status)->toBe(PostStatus::Draft)
        ->and($post->translation('en')?->published_at)->toBeNull();
});

it('rejects an unknown category', function (): void {
    $this->savePost->execute(PostData::fromInput(blogInput(['category_ids' => [9999]])));
})->throws(ValidationException::class);

it('soft deletes a post and restores it with its translations', function (): void {
    $post = $this->savePost->execute(PostData::fromInput(blogInput()));

    app(DeletePost::class)->execute($post);

    expect(Post::query()->find($post->id))->toBeNull();

    app(RestorePost::class)->execute($post);

    expect(Post::query()->find($post->id)?->translation('en')?->title)->toBe('Caching in Laravel')
        ->and($post->slug('en'))->toBe('caching-in-laravel');
});

it('force deletes a post with everything attached to it', function (): void {
    $post = $this->savePost->execute(PostData::fromInput(blogInput()));

    app(DeletePost::class)->execute($post, force: true);

    expect(Post::withTrashed()->find($post->id))->toBeNull()
        ->and(DB::table('post_translations')->count())->toBe(0)
        ->and(DB::table('slugs')->count())->toBe(0);
});
