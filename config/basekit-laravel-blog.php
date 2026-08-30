<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Enable the Blog feature
    |--------------------------------------------------------------------------
    |
    | When disabled the Blog routes are not registered. Composer packages
    | remain the primary mechanism for enabling features, so this switch is a
    | convenience for sites that keep the package but opt out of routing.
    |
    */

    'enabled' => (bool) env('BASEKIT_BLOG_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Route configuration
    |--------------------------------------------------------------------------
    |
    | The blog lives under a URI built from `route_prefix` (e.g. /blog).
    | The index renders at {prefix}, the feed at {prefix}/rss and a single
    | post at {prefix}/{post}, where the parameter binds to a Post by slug.
    |
    */

    'route_prefix' => 'blog',

    'route_parameter' => 'post',

    'rss_enabled' => (bool) env('BASEKIT_BLOG_RSS_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Listing configuration
    |--------------------------------------------------------------------------
    |
    | Number of posts shown per page, and whether the blog index paginates
    | published posts (ordered by most recent) instead of listing them all.
    |
    */

    'per_page' => 6,

    'paginate' => true,

    /*
    |--------------------------------------------------------------------------
    | View configuration
    |--------------------------------------------------------------------------
    |
    | The default views rendered by the Blog controllers. Themes can override
    | these by publishing the package views (see README) or by pointing the
    | view names at their own Blade templates.
    |
    */

    'views' => [
        'index' => 'blog.index',
        'article' => 'blog.article',
        'rss' => 'blog.rss',
    ],

];
