# Changelog

All notable changes to `basekit-laravel/basekit-laravel-blog` are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and this project
adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Localized domain: `PostTranslation`, `Category`, `CategoryTranslation`, `Tag`, `TagTranslation`
  and `PostRevision` models with their tables, relations, factories and localized name contract.
- `PostStatus` enum, a `publiclyVisible()` scope and `isPubliclyVisible()` on translations, so
  scheduled posts become visible once their publication date passes without a scheduled command.
- Write path: `PostData` and `PostTranslationData` DTOs with the validation rules of a post write,
  and the transactional `SavePost`, `DeletePost`, `RestorePost` and `RestoreRevision` actions.
  `PostData::$replaceTranslations` makes a payload the complete set of locales, so a removed locale
  is removed instead of silently kept.
- `PostQuery` as the single public read side: visible translations, listing, related posts and the
  locales a post is publicly readable in.
- Filament admin: `BlogPlugin`, `PostResource`, `CategoryResource` and `TagResource` with locale
  repeaters, a trashed filter, preview, delete, restore and bulk actions, and publishable English
  translations. Posts are written through `PostData` and `SavePost`; validation errors are reported
  on the form fields they belong to.
- SEO integration: `PostSeoResolver` for `seo()->for($translation)` and `PostSitemapProvider`, both
  registered by the service provider.
- RSS: a feed per locale, limited by `rss_limit`, published translations only.
- `BlogUrl` with `serves()`, `servedLocales()` and `hasFeed()` so links, alternates, sitemaps and
  feed discovery only point at routes that exist.
- Localization tests, migration publication and schema tests, `PostQuery` tests, and a Filament
  suite covering the plugin, the resources, the write path and the taxonomy locales.
- `rector.php` and `composer refactor` / `composer refactor-dry` scripts.

### Changed

- Clean-slate schema: the legacy scalar columns on `posts` are gone, as are the legacy
  `author` string, `tags` JSON and `category` columns. See `UPGRADING.md`.
- Migrations are published only and are no longer loaded from the package path, so a published
  migration is never applied twice. They are published with their original timestamps, so existing
  installations receive only the migration files they have not applied yet.
- Routes are registered per locale as `blog.<action>.<locale>` and are only loaded when `enabled` is
  true. `locale_prefix` decides whether every locale is served under its own prefix or only the
  default locale under the plain prefix.
- An unsupported locale, a locale without a slug and a translation that is not publicly visible are
  all 404s. Nothing falls back to another locale, at the routing level or in a link.
- Slugs are provided by `basekit-laravel-slugs` and kept per locale. A slug is generated the first
  time a locale is saved, is never rewritten by a later edit, and is released when its locale is
  removed. Changing one is explicit.
- Removed the unused `basekit-laravel/basekit-laravel-ui`, `basekit-laravel/basekit-laravel-blocks`
  and Blade UI icon runtime dependencies, plus the VCS repository entry only needed for the blocks
  package.

### Fixed

- The index view no longer emits its own hreflang alternates: the resolved `SeoData` already
  renders them, so the page was advertising every locale twice.
- The index view links the feed only when `rss_enabled` registers the route, instead of building a
  URL for a route that does not exist.
- hreflang alternates and sitemap entries cover only the locales a post is publicly readable in, so
  a draft, scheduled or deleted translation is no longer advertised.
- Removing a taxonomy locale now removes its translation and releases its slug, matching the
  behaviour of a post translation.
- The test suite runs with SQLite foreign key constraints enabled, so cascade behaviour matches
  MySQL and PostgreSQL.
- `composer analyse` runs clean at level 6: the write path, the Filament resources and the models
  are fully typed, with no ignores and no baseline.

### Removed

- The transitional legacy columns on `posts` and the compatibility layer that made them optional.
- `PostService` and its test, replaced by `SavePost` and the other write actions.
