{{--
    Default RSS/Atom feed view for published posts.
--}}
<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>
<rss version="2.0"
    xmlns:atom="http://www.w3.org/2005/Atom">
    <channel>
        <title>{{ config('site.name', 'Blog') }}</title>
        <link>{{ url('/') }}</link>
        <description>Latest posts from {{ config('site.name', 'the site') }}</description>
        <atom:link href="{{ route('blog.rss') }}" rel="self" type="application/rss+xml" />
        @foreach ($posts as $post)
            <item>
                <title>{{ $post->title }}</title>
                <link>{{ route('blog.show', $post) }}</link>
                <guid>{{ route('blog.show', $post) }}</guid>
                <pubDate>{{ $post->published_at?->toRfc2822String() }}</pubDate>
                @if ($post->category)
                    <category>{{ $post->category }}</category>
                @endif
                <description>{{ $post->excerpt }}</description>
            </item>
        @endforeach
    </channel>
</rss>
