<?php

declare(strict_types=1);

use BasekitLaravel\BasekitLaravelBlog\Http\Controllers\PostController;
use BasekitLaravel\BasekitLaravelBlog\Http\Controllers\RssController;
use BasekitLaravel\BasekitLaravelBlog\Support\LocaleRegistry;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Blog routes
|--------------------------------------------------------------------------
|
| The blog registers one set of routes per locale, named `blog.<action>.<locale>`,
| so link building never has to guess a prefix (see the BlogUrl helper). With
| `locale_prefix` disabled only the default locale is served, on the plain
| `/blog` URI; with it enabled every supported locale is served on `/<locale>/blog`.
|
| Posts are bound by the slug of the requested locale: a post that has no slug,
| or no visible translation, in that locale is a 404 — never a redirect to
| another locale.
|
*/

Route::middleware('web')->group(function (): void {
    $parameter = (string) config('basekit-laravel-blog.route_parameter', 'post');
    $prefix = trim((string) config('basekit-laravel-blog.route_prefix', 'blog'), '/');
    $locales = LocaleRegistry::fromConfig();
    $localePrefix = (bool) config('basekit-laravel-blog.locale_prefix', false);
    $variants = $localePrefix ? $locales->all() : [$locales->default];

    foreach ($variants as $locale) {
        $uri = implode('/', $localePrefix ? [$locale, $prefix] : [$prefix]);

        Route::get($uri, [PostController::class, 'index'])
            ->defaults('locale', $locale)
            ->name('blog.index.'.$locale);

        if ((bool) config('basekit-laravel-blog.rss_enabled', true)) {
            Route::get($uri.'/rss', RssController::class)
                ->defaults('locale', $locale)
                ->name('blog.rss.'.$locale);
        }

        Route::get($uri.'/{'.$parameter.'}', [PostController::class, 'show'])
            ->defaults('locale', $locale)
            ->name('blog.show.'.$locale);
    }
});
