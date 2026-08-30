{{--
    Default blog article view (theme-agnostic fallback).
    Themes override this by publishing the package views (see README).
    The view receives:
      - $post     : the Post being rendered
      - $related  : Collection of related published posts
--}}

<main class="mx-auto max-w-3xl px-4 py-16 sm:px-6 lg:px-8">
    <article>
        <header class="border-b border-gray-200 pb-8">
            <p class="text-sm text-gray-500">
                {{ $post->published_short }}
                @if ($post->category)
                    &middot; {{ $post->category }}
                @endif
                @if ($post->author)
                    &middot; By {{ $post->author }}
                @endif
            </p>
            <h1 class="mt-3 text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">{{ $post->title }}</h1>
            @if ($post->excerpt)
                <p class="mt-4 text-lg leading-relaxed text-gray-600">{{ $post->excerpt }}</p>
            @endif
        </header>

        <div class="mt-8 space-y-6 leading-7 text-gray-700 [&>p]:mb-4 [&_h2]:mt-8 [&_h2]:text-2xl [&_h2]:font-semibold [&_h3]:mt-6 [&_h3]:text-xl [&_h3]:font-semibold">
            {!! $post->content !!}
        </div>
    </article>

    @if ($related->isNotEmpty())
        <aside class="mt-16 border-t border-gray-200 pt-8">
            <h2 class="text-xl font-semibold text-gray-900">Related posts</h2>
            <ul class="mt-4 space-y-3">
                @foreach ($related as $item)
                    <li>
                        <a href="{{ route('blog.show', $item) }}" class="text-gray-700 hover:underline">{{ $item->title }}</a>
                    </li>
                @endforeach
            </ul>
        </aside>
    @endif
</main>
