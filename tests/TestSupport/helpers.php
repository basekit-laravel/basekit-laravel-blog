<?php

declare(strict_types=1);

use BasekitLaravel\BasekitLaravelBlog\Enums\PostStatus;
use BasekitLaravel\BasekitLaravelBlog\Models\Post;
use BasekitLaravel\BasekitLaravelSeo\Contracts\SeoResolver;
use BasekitLaravel\BasekitLaravelSeo\Contracts\SitemapProvider;
use BasekitLaravel\BasekitLaravelSeo\SeoManager;
use Illuminate\Routing\RouteCollection;

if (! function_exists('makePost')) {
    /**
     * A post with one translation, which is the smallest unit the public blog
     * can render. The slug is generated from the title, exactly as it is in
     * production.
     *
     * @param  array<string, mixed>  $translation
     */
    function makePost(string $title = 'Caching in Laravel', array $translation = [], ?string $locale = 'en'): Post
    {
        $post = Post::factory()->create();

        $post->translations()->create(array_merge([
            'locale' => $locale,
            'title' => $title,
            'excerpt' => 'A practical guide.',
            'content' => '<p>Body text.</p>',
            'status' => PostStatus::Published,
            'published_at' => now()->subDay(),
        ], $translation));

        return $post->refresh();
    }
}

if (! function_exists('reloadBlogRoutes')) {
    /**
     * Re-register the blog routes against the current configuration.
     *
     * The routes are built once, when the providers boot. Tests that change the
     * routing configuration need them rebuilt; every other route of the host
     * application is kept.
     */
    function reloadBlogRoutes(): void
    {
        $router = app('router');
        $routes = new RouteCollection;

        foreach ($router->getRoutes() as $route) {
            if (! str_starts_with((string) $route->getName(), 'blog.')) {
                $routes->add($route);
            }
        }

        $router->setRoutes($routes);

        require __DIR__.'/../../routes/web.php';
    }
}

if (! function_exists('blogSeoResolver')) {
    /**
     * The blog SEO resolver as the SEO package discovers it: through its tag.
     */
    function blogSeoResolver(): SeoResolver
    {
        foreach (app()->tagged(SeoManager::RESOLVER_TAG) as $resolver) {
            if ($resolver instanceof SeoResolver) {
                return $resolver;
            }
        }

        throw new RuntimeException('The blog does not register an SEO resolver.');
    }
}

if (! function_exists('blogSitemapProvider')) {
    /**
     * The blog sitemap provider as the SEO package discovers it.
     */
    function blogSitemapProvider(): SitemapProvider
    {
        foreach (app()->tagged(SitemapProvider::PROVIDER_TAG) as $provider) {
            if ($provider instanceof SitemapProvider) {
                return $provider;
            }
        }

        throw new RuntimeException('The blog does not register a sitemap provider.');
    }
}
