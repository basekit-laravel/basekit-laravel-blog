<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelBlog\Seo;

use BasekitLaravel\BasekitLaravelBlog\Models\Post;
use BasekitLaravel\BasekitLaravelBlog\Models\PostTranslation;
use BasekitLaravel\BasekitLaravelBlog\Services\PostQuery;
use BasekitLaravel\BasekitLaravelBlog\Support\BlogUrl;
use BasekitLaravel\BasekitLaravelSeo\Contracts\SeoResolver;
use BasekitLaravel\BasekitLaravelSeo\SeoData;
use BasekitLaravel\BasekitLaravelSeo\Support\ArticleSchema;
use BasekitLaravel\BasekitLaravelSeo\Support\OpenGraph;

/**
 * Resolves the metadata of a single post translation.
 *
 * The subject is the translation rather than the post, because the locale — and
 * with it the title, the description and the URL — is a property of the
 * translation. A post is addressed in this package by a translation, so handing
 * the translation to `seo()->for()` is also the natural call site in a view.
 */
final readonly class PostSeoResolver implements SeoResolver
{
    public function __construct(
        private BlogUrl $url,
        private PostQuery $posts,
    ) {}

    #[\Override]
    public function supports(mixed $subject): bool
    {
        return $subject instanceof PostTranslation;
    }

    #[\Override]
    public function resolve(mixed $subject): ?SeoData
    {
        if (! $subject instanceof PostTranslation) {
            return null;
        }

        $post = $subject->post;

        if (! $post instanceof Post || $post->slug($subject->locale) === null) {
            return null;
        }

        $url = $this->url->post($post, $subject->locale);
        $description = $subject->meta_description ?? $subject->excerpt;
        $image = $this->absoluteImage($subject->featured_image);

        $data = SeoData::make()
            ->withTitle($subject->meta_title ?? $subject->title)
            ->withDescription($description)
            ->withCanonicalUrl($url)
            ->withLocale($subject->locale)
            ->withOpenGraph(OpenGraph::fromArray(array_filter([
                'type' => 'article',
                'title' => $subject->meta_title ?? $subject->title,
                'description' => $description,
                'url' => $url,
                'image' => $image,
            ], static fn (mixed $value): bool => $value !== null)))
            ->withSchema($this->schema($subject, $url, $image));

        foreach ($this->posts->visibleLocales($post) as $locale) {
            $data = $data->withAlternate($locale, $this->url->post($post, $locale));
        }

        return $data;
    }

    private function schema(PostTranslation $translation, string $url, ?string $image): ArticleSchema
    {
        $schema = (new ArticleSchema)
            ->type('BlogPosting')
            ->headline($translation->title)
            ->description($translation->meta_description ?? $translation->excerpt)
            ->url($url)
            ->mainEntityOfPage($url)
            ->datePublishedFrom($translation->published_at)
            ->dateModified($translation->updated_at?->toIso8601String());

        if ($image !== null) {
            $schema = $schema->image($image);
        }

        $author = $translation->post?->author;

        if ($author !== null) {
            $name = (string) ($author->name ?? '');

            if ($name !== '') {
                $schema = $schema->author($name);
            }
        }

        return $schema;
    }

    /**
     * Open Graph and schema.org images must be absolute URLs.
     */
    private function absoluteImage(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return url($path);
    }
}
