{{--
    Default blog index (theme-agnostic fallback).
    Themes override this by publishing the package views (see README).
    The view receives:
      - $posts       : Collection|LengthAwarePaginator of published posts
      - $categories  : Collection of distinct post categories
--}}

<main class="mx-auto max-w-5xl px-4 py-16 sm:px-6 lg:px-8">
    <header class="mb-12">
        <h1 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">Blog</h1>
        @if ($categories->isNotEmpty())
            <nav class="mt-4 flex flex-wrap gap-2 text-sm text-gray-600">
                @foreach ($categories as $category)
                    <span class="rounded-full bg-gray-100 px-3 py-1">{{ $category }}</span>
                @endforeach
            </nav>
        @endif
    </header>

    <div class="grid gap-8 sm:grid-cols-2">
        @forelse ($posts as $post)
            <article>
                <a href="{{ route('blog.show', $post) }}" class="group block">
                    <h2 class="text-xl font-semibold text-gray-900 group-hover:underline">{{ $post->title }}</h2>
                    <p class="mt-2 text-sm text-gray-500">
                        {{ $post->published_short }}
                        @if ($post->category)
                            &middot; {{ $post->category }}
                        @endif
                    </p>
                    <p class="mt-3 leading-relaxed text-gray-600">{{ $post->excerpt }}</p>
                </a>
            </article>
        @empty
            <p class="col-span-full text-gray-500">No posts published yet.</p>
        @endforelse
    </div>

    @if (method_exists($posts, 'links'))
        <div class="mt-12">{{ $posts->links() }}</div>
    @endif
</main>
