<?php

declare(strict_types=1);

use BasekitLaravel\BasekitLaravelBlog\Enums\PostStatus;

beforeEach(function (): void {
    $this->post = makePost('Caching in Laravel');

    $this->post->translations()->create([
        'locale' => 'hu',
        'title' => 'Gyorsítótárazás',
        'excerpt' => 'Gyakorlati útmutató.',
        'content' => '<p>Magyar szöveg.</p>',
        'status' => PostStatus::Published,
        'published_at' => now()->subDay(),
    ]);
});

it('serves a prefixed route per locale', function (): void {
    config()->set('basekit-laravel-blog.supported_locales', ['en', 'hu']);
    config()->set('basekit-laravel-blog.locale_prefix', true);

    reloadBlogRoutes();

    $this->get('/en/blog')
        ->assertOk()
        ->assertSee('Caching in Laravel')
        ->assertDontSee('Gyorsítótárazás');

    $this->get('/hu/blog')
        ->assertOk()
        ->assertSee('Gyorsítótárazás')
        ->assertDontSee('Caching in Laravel');
});

it('resolves the post by the slug of the requested locale', function (): void {
    config()->set('basekit-laravel-blog.supported_locales', ['en', 'hu']);
    config()->set('basekit-laravel-blog.locale_prefix', true);

    reloadBlogRoutes();

    $this->get('/hu/blog/'.$this->post->slug('hu'))
        ->assertOk()
        ->assertSee('Magyar szöveg.');
});

it('never serves one locale at the URL of another', function (): void {
    config()->set('basekit-laravel-blog.supported_locales', ['en', 'hu']);
    config()->set('basekit-laravel-blog.locale_prefix', true);

    reloadBlogRoutes();

    $this->get('/en/blog/'.$this->post->slug('hu'))->assertNotFound();
    $this->get('/hu/blog/'.$this->post->slug('en'))->assertNotFound();
});

it('does not serve the unprefixed route when locales are prefixed', function (): void {
    config()->set('basekit-laravel-blog.supported_locales', ['en', 'hu']);
    config()->set('basekit-laravel-blog.locale_prefix', true);

    reloadBlogRoutes();

    $this->get('/blog')->assertNotFound();
});

it('returns 404 for a locale the blog does not publish', function (): void {
    config()->set('basekit-laravel-blog.supported_locales', ['en', 'hu']);
    config()->set('basekit-laravel-blog.locale_prefix', true);

    reloadBlogRoutes();

    $this->get('/de/blog')->assertNotFound();
    $this->get('/de/blog/'.$this->post->slug('en'))->assertNotFound();
});

it('serves a separate feed per locale', function (): void {
    config()->set('basekit-laravel-blog.supported_locales', ['en', 'hu']);
    config()->set('basekit-laravel-blog.locale_prefix', true);

    reloadBlogRoutes();

    $this->get('/hu/blog/rss')
        ->assertOk()
        ->assertSee('Gyorsítótárazás')
        ->assertDontSee('Caching in Laravel');
});

it('serves the default locale without a prefix by default', function (): void {
    config()->set('basekit-laravel-blog.supported_locales', ['en', 'hu']);

    reloadBlogRoutes();

    $this->get('/blog')
        ->assertOk()
        ->assertSee('Caching in Laravel')
        ->assertDontSee('Gyorsítótárazás');

    $this->get('/hu/blog')->assertNotFound();
});

it('normalizes the configured locales and serves their canonical segment', function (): void {
    config()->set('basekit-laravel-blog.supported_locales', ['EN', 'hu-HU']);
    config()->set('basekit-laravel-blog.locale_prefix', true);

    reloadBlogRoutes();

    $this->get('/en/blog')->assertOk();
    $this->get('/hu/blog')->assertOk()->assertSee('Gyorsítótárazás');
});

it('names one route set per locale', function (): void {
    config()->set('basekit-laravel-blog.supported_locales', ['en', 'hu']);
    config()->set('basekit-laravel-blog.locale_prefix', true);

    reloadBlogRoutes();

    $names = collect(app('router')->getRoutes())
        ->map(fn ($route): string => (string) $route->getName())
        ->filter()
        ->values()
        ->all();

    expect($names)->toContain('blog.index.en', 'blog.index.hu', 'blog.show.en', 'blog.show.hu', 'blog.rss.en', 'blog.rss.hu');
});

it('links every served locale of the index exactly once', function (): void {
    config()->set('basekit-laravel-blog.supported_locales', ['en', 'hu']);
    config()->set('basekit-laravel-blog.locale_prefix', true);

    reloadBlogRoutes();

    $content = $this->get('/en/blog')->assertOk()->getContent();

    expect(substr_count($content, 'hreflang="en"'))->toBe(1)
        ->and(substr_count($content, 'hreflang="hu"'))->toBe(1);
});

it('does not offer an alternate for a locale it does not serve', function (): void {
    config()->set('basekit-laravel-blog.supported_locales', ['en', 'hu']);

    reloadBlogRoutes();

    expect($this->get('/blog')->assertOk()->getContent())
        ->not->toContain('hreflang="hu"');
});

it('does not link a feed that is not registered', function (): void {
    config()->set('basekit-laravel-blog.rss_enabled', false);

    reloadBlogRoutes();

    $this->get('/blog')->assertOk()->assertDontSee('application/rss+xml');
    $this->get('/blog/rss')->assertNotFound();
});
