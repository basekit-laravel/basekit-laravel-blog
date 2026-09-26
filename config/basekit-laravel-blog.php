<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Enable the Blog feature
    |--------------------------------------------------------------------------
    |
    | When disabled the Blog routes and the Filament resources are not
    | registered. Composer packages remain the primary mechanism for enabling
    | features, so this switch is a convenience for sites that keep the
    | package installed but opt out of routing and admin screens.
    |
    */

    'enabled' => (bool) env('BASEKIT_BLOG_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Locales
    |--------------------------------------------------------------------------
    |
    | The default locale is used when a request carries no locale prefix and as
    | the locale of the unprefixed routes. Locales are matched on their primary
    | subtag, so "en-US" and "en" resolve to the same translation.
    |
    */

    'default_locale' => (string) env('BASEKIT_BLOG_DEFAULT_LOCALE', 'en'),

    'supported_locales' => [
        'en',
    ],

    /*
    |--------------------------------------------------------------------------
    | Route configuration
    |--------------------------------------------------------------------------
    |
    | The blog lives under a URI built from `route_prefix` (e.g. /blog). When
    | `locale_prefix` is enabled every supported locale also gets its own
    | prefixed variant (e.g. /hu/blog), which is required for correct hreflang
    | alternates. Posts are always bound by the slug of the requested locale.
    |
    */

    'route_prefix' => 'blog',

    'route_parameter' => 'post',

    'locale_prefix' => (bool) env('BASEKIT_BLOG_LOCALE_PREFIX', false),

    'rss_enabled' => (bool) env('BASEKIT_BLOG_RSS_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Listing configuration
    |--------------------------------------------------------------------------
    |
    | Number of posts shown per page, and how many other posts of the same
    | locale the article page suggests. Listing always paginates.
    |
    */

    'per_page' => 6,

    'related_limit' => 3,

    /*
    |--------------------------------------------------------------------------
    | Feed configuration
    |--------------------------------------------------------------------------
    |
    | Maximum number of posts included in the RSS feed. Kept below the full
    | archive for performance and feed-reader friendliness.
    |
    */

    'rss_limit' => 50,

    /*
    |--------------------------------------------------------------------------
    | Model configuration
    |--------------------------------------------------------------------------
    |
    | The post author relationship resolves the application's user model. The
    | package never ships a user model of its own, so applications using a
    | different class publish this config and point it at their own model.
    |
    */

    'models' => [
        'user' => 'App\\Models\\User',
    ],

    /*
    |--------------------------------------------------------------------------
    | Slug behaviour
    |--------------------------------------------------------------------------
    |
    | A post, category or tag gets one slug per locale. Slugs are generated from
    | the title of a translation the first time that locale is saved; later
    | edits never rewrite an existing slug, so published URLs stay stable.
    |
    */

    'generate_slugs' => (bool) env('BASEKIT_BLOG_GENERATE_SLUGS', true),

    /*
    |--------------------------------------------------------------------------
    | Revisions
    |--------------------------------------------------------------------------
    |
    | When enabled, every update of an existing translation snapshots its
    | previous state into post_revisions before the new state is written.
    |
    */

    'revisions' => [
        'enabled' => (bool) env('BASEKIT_BLOG_REVISIONS', true),
        'limit' => 20,
    ],

    /*
    |--------------------------------------------------------------------------
    | View configuration
    |--------------------------------------------------------------------------
    |
    | The default views rendered by the Blog controllers. Themes can override
    | these by publishing the package views or by pointing the view names at
    | their own Blade templates. The views receive the resolved locale, the
    | translation being rendered and a BlogUrl helper for locale aware links.
    |
    */

    'views' => [
        'index' => 'basekit-laravel-blog::blog.index',
        'article' => 'basekit-laravel-blog::blog.article',
        'rss' => 'basekit-laravel-blog::blog.rss',
    ],

];
