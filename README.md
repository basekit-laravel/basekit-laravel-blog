# Basekit Laravel Blog

A reusable, optional blog feature package for Basekit-powered Laravel applications. It ships the
localized domain, migrations, routes, SEO and RSS integration, a Filament admin and theme-agnostic
Blade views.

- Localized by design: every translatable field lives on a translation table, one row per locale.
- Slug per locale, generated once and never silently rewritten, so published URLs stay stable.
- Translation-first reads: a post is only ever returned through a visible translation of the
  requested locale. No fallback, ever.
- SEO and RSS are per locale and only advertise what is actually reachable.

## Requirements

- PHP `^8.4|^8.5`
- Laravel `^13`
- [`basekit-laravel/basekit-laravel-seo`](https://github.com/gergo-tar/basekit-laravel-seo) and
  [`basekit-laravel/basekit-laravel-slugs`](https://github.com/gergo-tar/basekit-laravel-slugs)
- Filament `^5.8` — only needed for the admin screens

The two Basekit packages are not on Packagist yet. Require them from a path repository, or from your
own VCS mirror, before requiring this package:

```json
{
    "repositories": [
        { "type": "path", "url": "../packages/basekit-laravel-seo" },
        { "type": "path", "url": "../packages/basekit-laravel-slugs" }
    ]
}
```

## Installation

```bash
composer require basekit-laravel/basekit-laravel-blog
php artisan vendor:publish --tag="basekit-laravel-blog-migrations"
php artisan vendor:publish --tag="basekit-laravel-blog-config"
php artisan migrate
```

Migrations are **published, not auto-loaded**. Publishing them once gives the consuming application
full ownership of the schema, so customisations are never overwritten by the package. Published
migrations keep the timestamps shipped with the package, so re-running `vendor:publish` never
re-stamps an already applied migration: existing installations simply receive the new files.

Optional publish tags:

| Tag | Publishes |
| --- | --- |
| `basekit-laravel-blog-config` | `config/basekit-laravel-blog.php` |
| `basekit-laravel-blog-migrations` | `database/migrations` |
| `basekit-laravel-blog-views` | The Blade views, into `resources/views/vendor/basekit-laravel-blog` |
| `basekit-laravel-blog-translations` | The `en` translations |

## Localization model

A `Post` is the language-neutral identity of an article. Everything that can be translated lives on
`PostTranslation`, keyed by a BCP 47 locale, with one row per post and locale.

| Model | Purpose |
| --- | --- |
| `Post` | Identity, author reference, soft deletes, relations |
| `PostTranslation` | Title, excerpt, content, status, publication date, featured state, image, SEO overrides, slug |
| `Category` / `CategoryTranslation` | Category identity and its localized name, description and SEO overrides |
| `Tag` / `TagTranslation` | Tag identity and its localized name and SEO overrides |
| `PostRevision` | Historical snapshot of a post translation |

```php
use BasekitLaravel\BasekitLaravelBlog\Enums\PostStatus;
use BasekitLaravel\BasekitLaravelBlog\Models\Post;

$post = Post::create(['author_id' => $user->id]);

$post->translations()->create([
    'locale' => 'en',
    'title' => 'Caching in Laravel',
    'excerpt' => 'A practical guide.',
    'content' => '<p>Body.</p>',
    'status' => PostStatus::Published,
    'published_at' => now(),
]);

$post->translation('en')?->title; // Caching in Laravel
$post->translation('de');         // null — no silent locale fallback
```

A `Category` and a `Tag` are the same shape: an identity plus one localized name per locale. Both
resolve their displayed name with the configured default locale:

```php
$category->translation('hu')?->name;
$category->name();        // the name in the default locale, or the first one
$category->name('hu');    // the name in that locale, or the first one
```

### Publication semantics

A translation is publicly readable when it is published or scheduled and its publication date has
passed. Scheduled posts therefore become visible on their own, without a cron job:

```php
$post->translations()->publiclyVisible()->get();
$post->translations()->forLocale('hu')->publiclyVisible()->get();
$translation->isPubliclyVisible();
```

Content is stored as trusted HTML and rendered unescaped by the bundled article view. Sanitise
content before saving it if authors are not trusted.

## Slugs

Every post, category and tag gets one slug per locale, stored by the Slugs package. A slug is
generated from the title — or the name, for a taxonomy term — the first time that locale is saved
and is never rewritten afterwards, so renaming an article does not break the URLs already published,
indexed or shared. Changing a slug is an explicit act:

```php
$post->translation('en')->update(['slug' => 'a-new-url']); // or through the write action
$post->slug('en');   // the slug of that locale
$post->slug('de');   // null — that locale has no slug
$post->slug();       // the slug of the default locale
```

Removing a locale also releases its slug, so a removed locale cannot keep claiming a URL that now
resolves elsewhere. Set `generate_slugs` to `false` to manage every slug by hand.

## Writing posts

The package has no public write endpoint — the blog is read-only to the public. Writes go through
one validated DTO and the transactional `SavePost` action:

```php
use BasekitLaravel\BasekitLaravelBlog\Actions\SavePost;
use BasekitLaravel\BasekitLaravelBlog\Data\PostData;
use BasekitLaravel\BasekitLaravelBlog\Enums\PostStatus;

$data = PostData::fromInput([
    'author_id' => $user->id,
    'category_ids' => [1, 2],
    'tag_ids' => [3],
    'translations' => [
        'en' => [
            'title' => 'Caching in Laravel',
            'excerpt' => 'A practical guide.',
            'content' => '<p>Body.</p>',
            'status' => PostStatus::Published,
            'published_at' => now(),
            'is_featured' => true,
        ],
        'hu' => ['title' => 'Gyorsítótárazás', 'status' => PostStatus::Draft],
    ],
]);

$post = app(SavePost::class)->execute($data);
```

The DTO is the only thing the actions accept, which keeps validation at the edge and the write path
free of raw input:

| Method | Purpose |
| --- | --- |
| `PostData::fromInput(array $input)` | Validates and builds the payload; throws `ValidationException` |
| `PostData::validate(array $input)` | The validated array, for callers that report errors against their own fields |
| `PostData::normalizedInput(array $input)` | The input in the shape the rules expect — backed enums become scalars |
| `PostData::fromValidated(array $validated)` | Builds the payload from an already validated array |

Translations are keyed by locale, so a partial payload is a partial update: locales it does not
mention are left alone. An editor that sees every locale at once — such as the Filament form — sets
`replaceTranslations`, which makes the payload the complete set of locales and removes the ones left
out of it.

`SavePost::execute()` saves the post, all of its translations, the taxonomy assignments and the
revision snapshots in one transaction, and clears the sitemap cache after the commit.

| Action | Purpose |
| --- | --- |
| `SavePost::execute(PostData $data, ?Post $post = null, ?int $actorId = null)` | Creates or updates a post with all of its locales |
| `DeletePost::execute(Post $post, bool $force = false)` | Soft deletes, or removes for good, with the cascade to translations, revisions and slugs |
| `RestorePost::execute(Post $post)` | Restores a soft deleted post |
| `RestoreRevision::execute(PostRevision $revision, ?int $actorId = null)` | Writes a revision back onto its post as a new revision |

Revisions are recorded when `revisions.enabled` is true: every update of an existing translation
snapshots its previous state into `post_revisions` first.

## Routing and URLs

The blog registers one set of routes per locale, named `blog.<action>.<locale>`, so link building
never has to guess a prefix:

| Route name | URI |
| --- | --- |
| `blog.index.{locale}` | `/blog` — or `/{locale}/blog` with `locale_prefix` |
| `blog.show.{locale}` | `/blog/{post}` — bound by the slug of that locale |
| `blog.rss.{locale}` | `/blog/rss` |

Without `locale_prefix` only the default locale is served, on the plain `/blog` URI, because every
other locale would claim the same address. With it enabled every supported locale is served under
its own prefix, which is what correct hreflang alternates require. A locale the blog does not
publish is a 404 — never a redirect to another locale, and never a silently substituted translation.

Use the `BlogUrl` helper instead of building URLs yourself:

```php
use BasekitLaravel\BasekitLaravelBlog\Support\BlogUrl;

$url = app(BlogUrl::class);

$url->hasLocalePrefix();          // whether prefixed routes are served
$url->serves('hu');               // whether the blog has a route for that locale
$url->servedLocales();            // the locales it actually serves
$url->hasFeed();                  // whether the RSS route is registered
$url->index('hu');                // the localized index
$url->post($post, 'hu');          // the article, or the index when that locale has no slug
$url->feed('hu');                 // the localized feed
```

## SEO and RSS

The blog integrates with the SEO package rather than rendering head tags of its own:

- `PostSeoResolver` is registered as a resolver, so `seo()->for($translation)` — or the article
  controller — yields the title, description, canonical URL, Open Graph, Twitter card and the
  `BlogPosting` JSON-LD of that translation.
- hreflang alternates cover the locales a post is **publicly readable** in. A draft, scheduled or
  deleted translation is never advertised.
- `PostSitemapProvider` is registered as a sitemap provider and yields the index plus every visible
  translation, skipping locales the blog has no route for.
- The bundled views render the head through `<x-basekit-laravel-seo::head />` and link the feed only
  when `rss_enabled` registers it. Themed views should do the same.
- The RSS feed is per locale and returns the published translations of that locale only.

## Filament admin

The admin is part of the package. Register the plugin on a panel:

```php
use BasekitLaravel\BasekitLaravelBlog\Filament\BlogPlugin;

public function panel(Panel $panel): Panel
{
    return $panel->plugin(new BlogPlugin);
}
```

Filament `^5.8` is a development dependency of this package; the consuming application requires it.

| Resource | Screens |
| --- | --- |
| `PostResource` | List, create, edit — locales as a repeater, preview, delete, restore, bulk delete/restore |
| `CategoryResource` | List, create, edit — locales as a repeater |
| `TagResource` | List, create, edit — locales as a repeater |

- Posts are written through `PostData` and `SavePost`, with the form asking for a complete set of
  locales, so a removed locale is removed.
- Validation errors are reported on the form fields they belong to, and the save halts.
- A post that is soft deleted stays listable through the trashed filter, and can be restored or
  removed for good from the table.
- Taxonomy saves are transactional, and a locale removed from a term releases its slug.
- Labels are read in the default locale, and the Filament strings are publishable with the
  `basekit-laravel-blog-translations` tag.

The translations of a post are edited as a repeater rather than in a separate relation manager, so
one form is one complete save. Use `PostQuery::visibleLocales()` if you need the public locales of a
post elsewhere in your admin.

## Configuration

`config/basekit-laravel-blog.php` is merged from the package. Publish it to change any key.

| Key | Default | Purpose |
| --- | --- | --- |
| `enabled` | `true` | Registers the blog routes when enabled |
| `default_locale` | `en` | Locale of the unprefixed routes and of localized reads |
| `supported_locales` | `['en']` | Locales the blog publishes, matched on their primary subtag |
| `route_prefix` | `blog` | URI prefix for the blog routes |
| `route_parameter` | `post` | Route parameter the article slug is bound to |
| `locale_prefix` | `false` | Serves `/{locale}/blog` for every supported locale |
| `rss_enabled` | `true` | Registers the RSS routes |
| `per_page` | `6` | Posts per index page |
| `related_limit` | `3` | Posts suggested at the end of an article |
| `rss_limit` | `50` | Items in the RSS feed |
| `generate_slugs` | `true` | Generates a slug the first time a locale is saved |
| `revisions.enabled` | `true` | Snapshots the previous state on every update |
| `revisions.limit` | `20` | Revisions kept per translation |
| `models.user` | `App\Models\User` | Model resolved by `Post::author()` |
| `views.index` | `basekit-laravel-blog::blog.index` | View rendered by the index controller |
| `views.article` | `basekit-laravel-blog::blog.article` | View rendered by the article controller |
| `views.rss` | `basekit-laravel-blog::blog.rss` | View rendered by the feed controller |

`models.user` is the only key the domain needs: the package ships no user model of its own, so point
it at your application's user class.

Every locale in `supported_locales` must be present in the translation tables. Missing locales are
simply empty, and an empty locale renders as an unpublished page rather than as a fallback.

## Views

The bundled views are a theme-agnostic fallback, not a design. Override them by publishing the
package views, or by pointing `views.*` at your own templates. They receive:

| View | Receives |
| --- | --- |
| `blog.index` | `$translations` (paginator of `PostTranslation`), `$locale`, `$url`, `$locales`, `$seo` |
| `blog.article` | `$post`, `$translation`, `$related`, `$locale`, `$url`, `$locales` |
| `blog.rss` | `$translations` (the published translations of `$locale`), `$locale`, `$url` |

Render the head with `<x-basekit-laravel-seo::head />` and never emit hreflang or feed links by
hand: the resolved `SeoData` already carries them, and a hand-written duplicate tells a crawler the
page has two addresses.

## Development

```bash
composer test           # Pest test suite
composer test-coverage  # Pest with coverage
composer format         # Laravel Pint
composer lint           # Laravel Pint in check mode
composer analyse        # PHPStan / Larastan
composer refactor-dry   # Rector, preview
composer refactor       # Rector, apply
```

The architecture decisions behind the package are documented in `docs/01_BLOG_ARCHITECTURE.md`.

## License

MIT
