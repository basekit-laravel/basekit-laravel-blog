{{--
    Default RSS 2.0 feed for the published posts of one locale.

    Themes override this by publishing the package views (see README). The view
    receives:
      - $translations : Collection of published PostTranslation in $locale
      - $locale       : the locale of this request
      - $url          : BlogUrl, for locale aware links
--}}
<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:content="http://purl.org/rss/1.0/modules/content/">
    <channel>
        <title>{{ config('basekit-laravel-seo.defaults.site_name', config('app.name', 'Blog')) }}</title>
        <link>{{ $url->index($locale) }}</link>
        <description>{{ config('basekit-laravel-seo.defaults.description') ?: 'Latest posts' }}</description>
        <language>{{ $locale }}</language>
        <lastBuildDate>{{ $translations->map(fn ($translation) => $translation->published_at)->filter()->max()?->toRfc2822String() }}</lastBuildDate>
        <atom:link href="{{ $url->feed($locale) }}" rel="self" type="application/rss+xml" />
        @foreach ($translations as $translation)
            <item>
                <title>{{ $translation->title }}</title>
                <link>{{ $url->post($translation->post, $locale) }}</link>
                <guid isPermaLink="true">{{ $url->post($translation->post, $locale) }}</guid>
                <pubDate>{{ $translation->published_at?->toRfc2822String() }}</pubDate>
                <description>{{ $translation->excerpt }}</description>
                <content:encoded><![CDATA[{!! $translation->content !!}]]></content:encoded>
            </item>
        @endforeach
    </channel>
</rss>
