<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelBlog\Http\Controllers;

use BasekitLaravel\BasekitLaravelBlog\Models\Post;
use BasekitLaravel\BasekitLaravelBlog\Models\PostTranslation;
use BasekitLaravel\BasekitLaravelBlog\Services\PostQuery;
use BasekitLaravel\BasekitLaravelBlog\Support\BlogUrl;
use BasekitLaravel\BasekitLaravelBlog\Support\LocaleRegistry;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * The public blog: the localized index and the localized article page.
 *
 * The locale is part of the route, never a query parameter and never a silent
 * fallback: a post without a visible translation in the requested locale is a
 * 404, and so is a request for a locale the blog does not publish.
 */
final readonly class PostController
{
    public function __construct(
        private PostQuery $posts,
        private BlogUrl $url,
        private LocaleRegistry $locales,
    ) {}

    public function index(Request $request): View
    {
        $locale = $this->locale($request);

        $seo = seo()
            ->title($this->siteName())
            ->description($this->description())
            ->locale($locale);

        foreach ($this->url->servedLocales() as $alternate) {
            $seo->alternate($alternate, $this->url->index($alternate));
        }

        return view((string) config('basekit-laravel-blog.views.index', 'basekit-laravel-blog::blog.index'), [
            'translations' => $this->posts->paginate($locale, (int) config('basekit-laravel-blog.per_page', 6)),
            'locale' => $locale,
            'url' => $this->url,
            'locales' => $this->locales->all(),
            'seo' => $seo,
        ]);
    }

    public function show(Request $request, Post $post): View
    {
        $locale = $this->locale($request);

        $translation = $post->translation($locale);

        if (! $translation instanceof PostTranslation || ! $translation->isPubliclyVisible()) {
            throw new NotFoundHttpException;
        }

        seo()->for($translation);

        return view((string) config('basekit-laravel-blog.views.article', 'basekit-laravel-blog::blog.article'), [
            'post' => $post,
            'translation' => $translation,
            'related' => $this->posts->related($translation, (int) config('basekit-laravel-blog.related_limit', 3)),
            'locale' => $locale,
            'url' => $this->url,
            'locales' => $this->locales->all(),
        ]);
    }

    /**
     * The locale of the current route.
     *
     * @throws NotFoundHttpException
     */
    private function locale(Request $request): string
    {
        $locale = LocaleRegistry::normalize((string) ($request->route('locale') ?? $this->locales->default));

        if (! $this->locales->isSupported($locale)) {
            throw new NotFoundHttpException;
        }

        return $locale;
    }

    private function siteName(): string
    {
        return (string) config('basekit-laravel-seo.defaults.site_name', config('app.name', 'Blog'));
    }

    private function description(): ?string
    {
        $description = config('basekit-laravel-seo.defaults.description');

        return is_string($description) && $description !== '' ? $description : null;
    }
}
