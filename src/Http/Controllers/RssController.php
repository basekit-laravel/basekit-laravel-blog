<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelBlog\Http\Controllers;

use BasekitLaravel\BasekitLaravelBlog\Services\PostQuery;
use BasekitLaravel\BasekitLaravelBlog\Support\BlogUrl;
use BasekitLaravel\BasekitLaravelBlog\Support\LocaleRegistry;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * The RSS feed of one locale.
 *
 * The feed is always written in the locale of the route and only contains
 * translations of that locale, so a feed reader never receives a post in a
 * language it did not subscribe to.
 */
final readonly class RssController
{
    public function __construct(
        private PostQuery $posts,
        private BlogUrl $url,
        private LocaleRegistry $locales,
    ) {}

    public function __invoke(Request $request): Response
    {
        $locale = LocaleRegistry::normalize((string) ($request->route('locale') ?? $this->locales->default));

        if (! $this->locales->isSupported($locale)) {
            throw new NotFoundHttpException;
        }

        $translations = $this->posts->latest($locale, (int) config('basekit-laravel-blog.rss_limit', 50));

        return response()
            ->view((string) config('basekit-laravel-blog.views.rss', 'basekit-laravel-blog::blog.rss'), [
                'translations' => $translations,
                'locale' => $locale,
                'url' => $this->url,
            ])
            ->header('Content-Type', 'application/rss+xml; charset=utf-8');
    }
}
