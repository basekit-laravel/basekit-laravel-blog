{{--
    Default blog article (theme-agnostic fallback).

    Themes override this by publishing the package views (see README). The view
    receives:
      - $post        : the Post addressed by the slug of $locale
      - $translation : its published PostTranslation in $locale
      - $related     : other published translations of $locale
      - $locale      : the locale of this request
      - $url         : BlogUrl, for locale aware links
      - $locales     : the locales the blog publishes

    The SEO head is rendered by the SEO package, which the controller resolved
    for this translation: canonical URL, hreflang alternates, Open Graph and the
    BlogPosting JSON-LD.
--}}

<!DOCTYPE html>
<html lang="{{ $locale }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <x-basekit-laravel-seo::head />
</head>
<body>
<main class="mx-auto max-w-3xl px-4 py-16 sm:px-6 lg:px-8">
    <article>
        <header class="mb-8">
            <p class="text-sm text-gray-500">
                <a class="underline" href="{{ $url->index($locale) }}">&larr; {{ $locale }}</a>
                &middot;
                <time datetime="{{ $translation->published_at?->toIso8601String() }}">
                    {{ $translation->published_at?->toFormattedDateString() }}
                </time>
            </p>

            <h1 class="mt-4 text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">
                {{ $translation->title }}
            </h1>

            @if ($translation->excerpt)
                <p class="mt-4 text-lg leading-relaxed text-gray-600">{{ $translation->excerpt }}</p>
            @endif
        </header>

        @if ($translation->featured_image)
            <figure class="mb-8">
                <img
                    src="{{ $translation->featured_image }}"
                    alt="{{ $translation->image_alt ?? $translation->title }}"
                    class="w-full rounded-lg"
                >
                @if ($translation->image_alt)
                    <figcaption class="mt-2 text-sm text-gray-500">{{ $translation->image_alt }}</figcaption>
                @endif
            </figure>
        @endif

        {{-- The content is trusted editor-authored HTML; see UPGRADING.md. --}}
        <div class="prose max-w-none">
            {!! $translation->content !!}
        </div>
    </article>

    @if ($related->isNotEmpty())
        <section class="mt-16 border-t border-gray-200 pt-8">
            <h2 class="text-xl font-semibold text-gray-900">Related posts</h2>

            <ul class="mt-4 space-y-4">
                @foreach ($related as $item)
                    <li>
                        <a class="underline" href="{{ $url->post($item->post, $locale) }}">{{ $item->title }}</a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
</main>
</body>
</html>
