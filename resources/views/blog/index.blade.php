{{--
    Default blog index (theme-agnostic fallback).

    Themes override this by publishing the package views (see README). The view
    receives:
      - $translations : LengthAwarePaginator of published PostTranslation in $locale
      - $locale       : the locale of this request
      - $url          : BlogUrl, for locale aware links
      - $locales      : the locales the blog publishes
      - $seo          : the SeoManager, already resolved for the index
--}}

<!DOCTYPE html>
<html lang="{{ $locale }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    {{-- Canonical URL, hreflang alternates and the social cards are rendered by the SEO package. --}}
    <x-basekit-laravel-seo::head />
    @if ($url->hasFeed())
        <link rel="alternate" type="application/rss+xml" title="{{ $locale }}" href="{{ $url->feed($locale) }}">
    @endif
</head>
<body>
<main class="mx-auto max-w-5xl px-4 py-16 sm:px-6 lg:px-8">
    <header class="mb-12">
        <h1 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">Blog</h1>

        @if ($url->hasLocalePrefix() && count($locales) > 1)
            <nav class="mt-4 flex flex-wrap gap-2 text-sm text-gray-600" aria-label="Languages">
                @foreach ($locales as $alternate)
                    @if ($alternate === $locale)
                        <span aria-current="true" class="font-semibold">{{ $alternate }}</span>
                    @else
                        <a class="underline" href="{{ $url->index($alternate) }}">{{ $alternate }}</a>
                    @endif
                @endforeach
            </nav>
        @endif
    </header>

    <div class="grid gap-8 sm:grid-cols-2">
        @forelse ($translations as $translation)
            @php($post = $translation->post)
            <article>
                <a href="{{ $url->post($post, $locale) }}" class="group block">
                    <h2 class="text-xl font-semibold text-gray-900 group-hover:underline">{{ $translation->title }}</h2>
                    <p class="mt-2 text-sm text-gray-500">
                        <time datetime="{{ $translation->published_at?->toIso8601String() }}">
                            {{ $translation->published_at?->toFormattedDateString() }}
                        </time>
                    </p>
                    <p class="mt-3 leading-relaxed text-gray-600">{{ $translation->excerpt }}</p>
                </a>
            </article>
        @empty
            <p class="col-span-full text-gray-500">No posts published yet.</p>
        @endforelse
    </div>

    @if ($translations->hasPages())
        <div class="mt-12">{{ $translations->links() }}</div>
    @endif
</main>
</body>
</html>
