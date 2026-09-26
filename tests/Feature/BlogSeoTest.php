<?php

declare(strict_types=1);

use BasekitLaravel\BasekitLaravelBlog\Actions\DeletePost;
use BasekitLaravel\BasekitLaravelBlog\Enums\PostStatus;
use BasekitLaravel\BasekitLaravelBlog\Models\PostTranslation;
use BasekitLaravel\BasekitLaravelBlog\Tests\TestSupport\Models\User;
use BasekitLaravel\BasekitLaravelSeo\Support\SitemapEntry;

beforeEach(function (): void {
    $this->author = User::create(['name' => 'Gergő Tar']);
});

it('resolves metadata for a published translation', function (): void {
    $post = makePost('Caching in Laravel', [
        'meta_title' => 'Caching in Laravel 13',
        'meta_description' => 'How the cache works.',
        'featured_image' => '/images/cover.jpg',
    ], locale: 'en');

    $data = blogSeoResolver()->resolve($post->translation('en'));

    expect($data)->not->toBeNull()
        ->and($data->title)->toBe('Caching in Laravel 13')
        ->and($data->description)->toBe('How the cache works.')
        ->and($data->canonicalUrl?->toString())->toBe(url('/blog/'.$post->slug('en')))
        ->and($data->locale)->toBe('en');
});

it('falls back to the title and the excerpt for metadata', function (): void {
    $post = makePost('Caching in Laravel', ['excerpt' => 'A practical guide.']);

    $data = blogSeoResolver()->resolve($post->translation('en'));

    expect($data?->title)->toBe('Caching in Laravel')
        ->and($data->description)->toBe('A practical guide.');
});

it('declares the author of the post in the article schema', function (): void {
    $post = makePost('Caching in Laravel');
    $post->update(['author_id' => $this->author->id]);

    $data = blogSeoResolver()->resolve($post->fresh()->translation('en'));

    $schema = ($data?->schemas[0] ?? null)?->toArray();

    expect($schema)->toBeArray()
        ->and($schema['@type'])->toBe('BlogPosting')
        ->and($schema['headline'])->toBe('Caching in Laravel')
        ->and($schema['author'])->toBe(['@type' => 'Person', 'name' => 'Gergő Tar'])
        ->and($schema['datePublished'])->toBeString()
        ->and($schema['mainEntityOfPage'])->toBe(['@type' => 'WebPage', '@id' => url('/blog/'.$post->slug('en'))]);
});

it('adds an alternate for every locale the post has a slug for', function (): void {
    $post = makePost('Caching in Laravel');

    $post->translations()->create([
        'locale' => 'hu',
        'title' => 'Gyorsítótárazás',
        'status' => PostStatus::Published,
        'published_at' => now()->subDay(),
    ]);

    config()->set('basekit-laravel-blog.supported_locales', ['en', 'hu']);
    config()->set('basekit-laravel-blog.locale_prefix', true);

    reloadBlogRoutes();

    $data = blogSeoResolver()->resolve($post->translation('en'));

    $alternates = collect($data?->alternates ?? [])
        ->mapWithKeys(fn ($alternate): array => [$alternate->hreflang => $alternate->url->toString()])
        ->all();

    expect($alternates)->toBe([
        'en' => url('/en/blog/'.$post->slug('en')),
        'hu' => url('/hu/blog/'.$post->slug('hu')),
    ]);
});

it('adds no alternate for a translation that is not public', function (): void {
    $post = makePost('Caching in Laravel');

    $post->translations()->create([
        'locale' => 'hu',
        'title' => 'Gyorsítótárazás',
        'status' => PostStatus::Draft,
    ]);

    $post->translations()->create([
        'locale' => 'de',
        'title' => 'Caching in Laravel',
        'status' => PostStatus::Scheduled,
        'published_at' => now()->addWeek(),
    ]);

    config()->set('basekit-laravel-blog.supported_locales', ['en', 'hu', 'de']);
    config()->set('basekit-laravel-blog.locale_prefix', true);

    reloadBlogRoutes();

    $data = blogSeoResolver()->resolve($post->translation('en'));

    $alternates = collect($data?->alternates ?? [])
        ->mapWithKeys(fn ($alternate): array => [$alternate->hreflang => $alternate->url->toString()])
        ->all();

    expect($alternates)->toBe(['en' => url('/en/blog/'.$post->slug('en'))]);
});

it('does not resolve a subject that is not a translation', function (): void {
    $post = makePost('Caching in Laravel');

    expect(blogSeoResolver()->supports($post))->toBeFalse()
        ->and(blogSeoResolver()->resolve($post))->toBeNull()
        ->and(blogSeoResolver()->supports('nonsense'))->toBeFalse();
});

it('does not resolve a translation without a slug in its locale', function (): void {
    $post = makePost('Caching in Laravel');
    $post->setSlug(null, 'hu');

    $translation = new PostTranslation(['locale' => 'hu', 'title' => 'Magyar']);
    $translation->setRelation('post', $post);

    expect(blogSeoResolver()->resolve($translation))->toBeNull();
});

it('renders the resolved metadata in the article head', function (): void {
    $post = makePost('Caching in Laravel', ['meta_description' => 'How the cache works.']);

    $this->get('/blog/'.$post->slug('en'))
        ->assertOk()
        ->assertSee('<title>Caching in Laravel</title>', escape: false)
        ->assertSee('<meta name="description" content="How the cache works."', escape: false)
        ->assertSee('<link rel="canonical" href="'.url('/blog/'.$post->slug('en')).'"', escape: false)
        ->assertSee('BlogPosting', escape: false)
        ->assertSee('og:type" content="article', escape: false);
});

it('lists the index and every visible translation of a served locale', function (): void {
    makePost('Caching in Laravel');
    makePost('Magyar bejegyzés', locale: 'hu');
    makePost('Draft', ['status' => PostStatus::Draft, 'published_at' => null]);

    $entries = collect(blogSitemapProvider()->entries())
        ->map(fn (SitemapEntry $entry): string => $entry->loc)
        ->all();

    expect($entries)->toBe([url('/blog'), url('/blog/caching-in-laravel')]);
});

it('does not list a translation the blog has no route for', function (): void {
    config()->set('basekit-laravel-blog.supported_locales', ['en', 'hu']);

    makePost('Caching in Laravel');
    makePost('Magyar bejegyzés', locale: 'hu');

    $entries = collect(blogSitemapProvider()->entries())
        ->map(fn (SitemapEntry $entry): string => $entry->loc)
        ->all();

    // hu is supported but not served: without a prefix there is no /hu/blog route.
    expect($entries)->toBe([url('/blog'), url('/blog/caching-in-laravel')]);
});

it('lists a translated post once the blog serves its locale', function (): void {
    config()->set('basekit-laravel-blog.supported_locales', ['en', 'hu']);
    config()->set('basekit-laravel-blog.locale_prefix', true);

    reloadBlogRoutes();

    makePost('Caching in Laravel');
    makePost('Magyar bejegyzés', locale: 'hu');

    $entries = collect(blogSitemapProvider()->entries())
        ->map(fn (SitemapEntry $entry): string => $entry->loc)
        ->all();

    expect($entries)->toBe([
        url('/en/blog'),
        url('/hu/blog'),
        url('/en/blog/caching-in-laravel'),
        url('/hu/blog/magyar-bejegyzes'),
    ]);
});

it('lists a featured post with a higher sitemap priority', function (): void {
    makePost('Featured', ['is_featured' => true]);
    makePost('Regular');

    $priorities = collect(blogSitemapProvider()->entries())
        ->mapWithKeys(fn (SitemapEntry $entry): array => [$entry->loc => $entry->priority])
        ->all();

    expect($priorities[url('/blog/featured')])->toBe(0.9)
        ->and($priorities[url('/blog/regular')])->toBe(0.7);
});

it('drops a post from the sitemap when it is deleted', function (): void {
    $post = makePost('Caching in Laravel');

    $this->get('/sitemap.xml')->assertOk();

    app(DeletePost::class)->execute($post);

    $this->get('/sitemap.xml')
        ->assertOk()
        ->assertDontSee($post->slug('en'), escape: false);
});
