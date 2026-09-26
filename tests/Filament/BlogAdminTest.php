<?php

declare(strict_types=1);

use BasekitLaravel\BasekitLaravelBlog\Filament\BlogPlugin;
use BasekitLaravel\BasekitLaravelBlog\Filament\Resources\CategoryResource;
use BasekitLaravel\BasekitLaravelBlog\Filament\Resources\CategoryResource\Pages\EditCategory;
use BasekitLaravel\BasekitLaravelBlog\Filament\Resources\CategoryResource\Pages\ListCategories;
use BasekitLaravel\BasekitLaravelBlog\Filament\Resources\PostResource;
use BasekitLaravel\BasekitLaravelBlog\Filament\Resources\PostResource\Pages\CreatePost;
use BasekitLaravel\BasekitLaravelBlog\Filament\Resources\PostResource\Pages\EditPost;
use BasekitLaravel\BasekitLaravelBlog\Filament\Resources\PostResource\Pages\ListPosts;
use BasekitLaravel\BasekitLaravelBlog\Filament\Resources\TagResource;
use BasekitLaravel\BasekitLaravelBlog\Filament\Resources\TagResource\Pages\ListTags;
use BasekitLaravel\BasekitLaravelBlog\Models\Category;
use BasekitLaravel\BasekitLaravelBlog\Models\Post;
use BasekitLaravel\BasekitLaravelBlog\Models\PostTranslation;
use BasekitLaravel\BasekitLaravelBlog\Models\Tag;
use BasekitLaravel\BasekitLaravelBlog\Tests\TestSupport\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->actingAs(User::query()->create(['name' => 'Editor']));

    Filament::setCurrentPanel(Filament::getPanel('blog'));
});

it('registers the blog resources on the panel', function (): void {
    expect((new BlogPlugin)->getId())->toBe('basekit-laravel-blog')
        ->and(Filament::getPanel('blog')->getResources())
        ->toContain(PostResource::class, CategoryResource::class, TagResource::class);
});

it('lists posts with the title of the default locale', function (): void {
    makePost('Caching in Laravel');

    Livewire::test(ListPosts::class)
        ->assertCanSeeTableRecords(Post::all())
        ->assertTableColumnStateSet('title', 'Caching in Laravel', Post::first());
});

it('lists posts of a trashed post when the filter asks for them', function (): void {
    $post = makePost('Caching in Laravel');
    $post->delete();

    Livewire::test(ListPosts::class)
        ->assertCanNotSeeTableRecords([$post]);

    Livewire::test(ListPosts::class, ['tableFilters' => ['trashed' => ['value' => 'only']]])
        ->assertCanSeeTableRecords([$post]);
});

it('creates a post through the write path', function (): void {
    Livewire::test(CreatePost::class)
        ->fillForm([
            'author_id' => User::query()->first()?->getKey(),
            'translations' => [
                [
                    'locale' => 'en',
                    'title' => 'Caching in Laravel',
                    'content' => '<p>Body text.</p>',
                    'status' => 'published',
                    'published_at' => now()->subDay()->format('Y-m-d H:i:s'),
                    'is_featured' => true,
                ],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $post = Post::query()->sole();

    expect($post->author)->toBeInstanceOf(User::class)
        ->and($post->translation('en')?->title)->toBe('Caching in Laravel')
        ->and($post->translation('en')?->is_featured)->toBeTrue()
        ->and($post->slug('en'))->toBe('caching-in-laravel');
});

it('keeps one translation per locale and drops removed locales', function (): void {
    config()->set('basekit-laravel-blog.supported_locales', ['en', 'hu']);

    $post = makePost('Caching in Laravel');
    $post->translations()->create([
        'locale' => 'hu',
        'title' => 'Gyorsítótárazás',
        'status' => 'published',
        'published_at' => now()->subDay(),
    ]);

    $test = Livewire::test(EditPost::class, ['record' => $post->getKey()]);

    /** @var list<array<string, mixed>> $rows */
    $rows = $test->get('data.translations');

    expect(array_column($rows, 'locale'))->toBe(['en', 'hu'])
        ->and(array_column($rows, 'title'))->toBe(['Caching in Laravel', 'Gyorsítótárazás']);

    $test
        ->fillForm([
            'translations' => [
                [
                    'locale' => 'en',
                    'title' => 'Caching in Laravel, revisited',
                    'content' => '<p>Body text.</p>',
                    'status' => 'published',
                    'published_at' => now()->subDay()->format('Y-m-d H:i:s'),
                ],
            ],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($post->fresh()->translations()->pluck('locale')->all())->toBe(['en'])
        ->and($post->fresh()->translation('en')?->title)->toBe('Caching in Laravel, revisited')
        ->and($post->slug('en'))->toBe('caching-in-laravel');
});

it('refuses to save a post without a title', function (): void {
    Livewire::test(CreatePost::class)
        ->fillForm([
            'translations' => [
                ['locale' => 'en', 'title' => '', 'status' => 'draft'],
            ],
        ])
        ->call('create')
        ->assertHasFormErrors(['translations.0.title']);
});

it('refuses to publish a translation without a date', function (): void {
    Livewire::test(CreatePost::class)
        ->fillForm([
            'translations' => [
                ['locale' => 'en', 'title' => 'Caching in Laravel', 'status' => 'published'],
            ],
        ])
        ->call('create')
        ->assertHasFormErrors(['translations.0.published_at']);

    expect(PostTranslation::query()->count())->toBe(0);
});

it('refuses a slug the column cannot hold', function (): void {
    $post = makePost('Caching in Laravel');

    Livewire::test(EditPost::class, ['record' => $post->getKey()])
        ->fillForm([
            'translations' => [
                [
                    'locale' => 'en',
                    'title' => 'Caching in Laravel',
                    'status' => 'published',
                    'published_at' => now()->subDay()->format('Y-m-d H:i:s'),
                    'slug' => str_repeat('a', 300),
                ],
            ],
        ])
        ->call('save')
        ->assertHasFormErrors(['translations.0.slug']);
});

it('removes a taxonomy locale the editor no longer submitted', function (): void {
    config()->set('basekit-laravel-blog.supported_locales', ['en', 'hu']);

    $category = Category::factory()->create();
    $category->translations()->create(['locale' => 'en', 'name' => 'Performance']);
    $category->translations()->create(['locale' => 'hu', 'name' => 'Teljesítmény']);

    $test = Livewire::test(EditCategory::class, ['record' => $category->getKey()]);

    /** @var list<array<string, mixed>> $rows */
    $rows = $test->get('data.translations');

    expect(array_column($rows, 'locale'))->toBe(['en', 'hu'])
        ->and(array_column($rows, 'name'))->toBe(['Performance', 'Teljesítmény']);

    $test
        ->fillForm([
            'translations' => [
                ['locale' => 'en', 'name' => 'Performance'],
            ],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $category = $category->fresh();

    expect($category->translations()->pluck('locale')->all())->toBe(['en'])
        ->and($category->translation('hu'))->toBeNull()
        ->and($category->slug('hu'))->toBeNull()
        ->and($category->slug('en'))->toBe('performance');
});

it('restores a deleted post from the table', function (): void {
    $post = makePost('Caching in Laravel');
    $post->delete();

    Livewire::test(ListPosts::class, ['tableFilters' => ['trashed' => 'only']])
        ->assertTableActionExists('restore', record: $post);

    $post->restore();

    expect(Post::query()->withoutGlobalScopes()->count())->toBe(1);
});

it('lists categories and tags by their localized name', function (): void {
    $category = Category::factory()->create();
    $category->translations()->create(['locale' => 'en', 'name' => 'Performance']);

    $tag = Tag::factory()->create();
    $tag->translations()->create(['locale' => 'en', 'name' => 'Caching']);

    Livewire::test(ListCategories::class)
        ->assertCanSeeTableRecords([$category])
        ->assertTableColumnStateSet('name', 'Performance', $category);

    Livewire::test(ListTags::class)
        ->assertCanSeeTableRecords([$tag])
        ->assertTableColumnStateSet('name', 'Caching', $tag);
});
