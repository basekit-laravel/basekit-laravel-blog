<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelBlog;

use Illuminate\Support\ServiceProvider;

final class BasekitLaravelBlogServiceProvider extends ServiceProvider
{
    #[\Override]
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/basekit-laravel-blog.php', 'basekit-laravel-blog');
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'basekit-laravel-blog');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/basekit-laravel-blog'),
        ], 'basekit-laravel-blog-views');

        $this->publishes([
            __DIR__.'/../config/basekit-laravel-blog.php' => config_path('basekit-laravel-blog.php'),
        ], 'basekit-laravel-blog-config');

        $this->publishesMigrations([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'basekit-laravel-blog-migrations');

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->registerRoutes();
    }

    protected function registerRoutes(): void
    {
        if (! config('basekit-laravel-blog.enabled', true)) {
            return;
        }

        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
    }
}
