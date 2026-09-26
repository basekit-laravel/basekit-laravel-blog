<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelBlog;

use BasekitLaravel\BasekitLaravelBlog\Actions\DeletePost;
use BasekitLaravel\BasekitLaravelBlog\Actions\RestorePost;
use BasekitLaravel\BasekitLaravelBlog\Actions\RestoreRevision;
use BasekitLaravel\BasekitLaravelBlog\Actions\SavePost;
use BasekitLaravel\BasekitLaravelBlog\Models\Post;
use BasekitLaravel\BasekitLaravelBlog\Seo\PostSeoResolver;
use BasekitLaravel\BasekitLaravelBlog\Seo\PostSitemapProvider;
use BasekitLaravel\BasekitLaravelBlog\Services\PostQuery;
use BasekitLaravel\BasekitLaravelBlog\Support\BlogUrl;
use BasekitLaravel\BasekitLaravelBlog\Support\LocaleRegistry;
use BasekitLaravel\BasekitLaravelSeo\Contracts\SitemapProvider;
use BasekitLaravel\BasekitLaravelSeo\SeoManager;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

final class BasekitLaravelBlogServiceProvider extends ServiceProvider
{
    #[\Override]
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/basekit-laravel-blog.php', 'basekit-laravel-blog');

        $this->app->bind(LocaleRegistry::class, static fn (): LocaleRegistry => LocaleRegistry::fromConfig());
        $this->app->bind(BlogUrl::class, static fn (): BlogUrl => BlogUrl::fromConfig());
        $this->app->singleton(PostQuery::class);

        $this->app->singleton(SavePost::class);
        $this->app->singleton(DeletePost::class);
        $this->app->singleton(RestorePost::class);
        $this->app->singleton(RestoreRevision::class);

        $this->app->tag(PostSeoResolver::class, SeoManager::RESOLVER_TAG);
        $this->app->tag(PostSitemapProvider::class, SitemapProvider::PROVIDER_TAG);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'basekit-laravel-blog');
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'basekit-laravel-blog');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/basekit-laravel-blog'),
        ], 'basekit-laravel-blog-views');

        $this->publishes([
            __DIR__.'/../lang' => $this->app->langPath('vendor/basekit-laravel-blog'),
        ], 'basekit-laravel-blog-translations');

        $this->publishes([
            __DIR__.'/../config/basekit-laravel-blog.php' => config_path('basekit-laravel-blog.php'),
        ], 'basekit-laravel-blog-config');

        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'basekit-laravel-blog-migrations');

        $this->bindPostRouteParameter();
        $this->registerRoutes();
    }

    /**
     * Posts are addressed by the slug of the locale in the route, so a post is
     * only reachable at a URL it actually owns. An unknown slug is a 404.
     */
    private function bindPostRouteParameter(): void
    {
        $parameter = (string) config('basekit-laravel-blog.route_parameter', 'post');

        $this->app->make(Router::class)->bind($parameter, static function (string $value, mixed $route): Post {
            $locale = (string) ($route->parameter('locale') ?? LocaleRegistry::fromConfig()->default);

            $post = Post::query()->whereSlug($value, $locale)->first();

            if (! $post instanceof Post) {
                throw (new ModelNotFoundException)->setModel(Post::class, [$value]);
            }

            return $post;
        });
    }

    private function registerRoutes(): void
    {
        if (! (bool) config('basekit-laravel-blog.enabled', true)) {
            return;
        }

        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
    }
}
