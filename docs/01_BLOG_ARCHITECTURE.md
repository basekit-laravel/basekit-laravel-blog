# Basekit Laravel Blog — Architecture & Implementation Plan

**Status:** Architecture baseline  
**Package:** `basekit-laravel-blog`  
**Primary admin integration:** Filament (optional)  
**Related packages:** `basekit-laravel-seo`, `basekit-laravel-slugs`

## 1. Goal

Build a reusable, Laravel-native blogging package with a feature set comparable to a practical WordPress blog, while keeping the architecture modular, maintainable, testable, localization-first, and independent from any specific admin UI.

The package must work as a standalone Laravel package and integrate cleanly with:

- `basekit-laravel-slugs` for slug generation, persistence, uniqueness, and localized slugs. See it in parent folder, it is not yet available in github.
- `basekit-laravel-seo` for SEO metadata, canonical URLs, hreflang, structured data, sitemap integration, and robots handling.
- Filament as an optional administration adapter.

There must be **no `basekit-laravel-content` package**. Blog owns blog-specific content.

## 2. Core principles

1. Laravel-native first.
2. Localization is a first-class feature, not a later enhancement.
3. Keep domain/business logic independent from Filament.
4. Reuse existing Basekit packages instead of duplicating their responsibilities.
5. Prefer simple, explicit domain models over generic abstraction layers.
6. Avoid unnecessary third-party dependencies.
7. Database-backed functionality first; external search engines are future integrations.
8. Frontend views must be replaceable.
9. Package functionality must be testable without requiring Filament.
10. Public APIs must be deliberate and documented.
11. Security and publication visibility must be explicit.
12. WordPress is a feature reference, not an architectural reference.

## 3. Package boundaries

### Blog owns

- Posts
- Post translations
- Categories
- Category translations
- Tags
- Tag translations
- Post/category/tag relationships
- Authors/user association
- Post content
- Excerpts
- Featured image/media references
- Publication lifecycle
- Scheduling
- Revisions
- Search
- Frontend blog routes/views
- RSS/Atom feeds
- Blog events
- Blog-specific policies/authorization hooks
- Blog configuration
- Blog translations
- Localized publication availability

### Slugs owns

- Slug generation
- Slug persistence
- Slug uniqueness
- Localized slugs
- Slug-related APIs

Blog must consume the Slugs package API rather than reimplementing slug generation.

### SEO owns

- SEO metadata rendering
- Canonical URLs
- Open Graph metadata
- JSON-LD / structured data
- hreflang rendering
- Sitemap generation
- Robots handling

Blog must expose the data SEO needs but must not duplicate SEO rendering or sitemap logic.

### Filament owns

- Admin resources
- Forms
- Tables
- Filters
- Actions
- Notifications
- Admin-specific translation editing UX

Filament must call Blog's core services/actions rather than contain business rules.

## 4. Functional scope

The package should provide:

- Create/edit/delete posts
- Draft posts
- Scheduled posts
- Published posts
- Unpublish posts
- Restore deleted posts where supported
- Post revisions
- Categories
- Tags
- Authors
- Featured image reference
- Excerpt
- Search
- Pagination
- Related posts where practical
- RSS/Atom feeds
- Localized content
- Localized slugs through `basekit-laravel-slugs`
- Per-language publication state
- Frontend routes
- Replaceable frontend views
- Optional Filament administration
- Events
- Policies/authorization integration
- Package translations

Comments and a complete media library are explicitly out of the initial core scope.

## 5. Domain model

The initial domain should be evaluated around:

- `Post`
- `PostTranslation`
- `Category`
- `CategoryTranslation`
- `Tag`
- `TagTranslation`
- `PostRevision`
- configured application `User`

The exact model structure must be confirmed during Phase 0 after inspecting the existing repository.

### Author

Do not create a duplicate Author model by default. Use the application's configured User model.

```php
' models' => [
    'user' => App\\Models\\User::class,
],
```

The Post should expose its author through a normal relationship.

## 6. Localization — first-class requirement

Localization must be designed before implementation.

The package must support:

- Multiple application locales
- Translated post titles
- Translated post excerpts
- Translated post content
- Translated category names/descriptions
- Translated tag names
- Localized slugs
- Independent publication state per locale
- Locale-aware frontend routes
- Locale-aware search
- Locale-aware feeds
- Locale-aware sitemap URL discovery
- Locale-aware SEO integration
- Translation-aware Filament editing
- Package UI translations

A post existing in the database does **not** imply that it is available in every language.

Example:

| Locale | Translation | Status |
|---|---|---|
| `en` | exists | published |
| `hu` | exists | published |
| `es` | exists | draft |
| `de` | missing | unavailable |

The frontend must never expose a missing, draft, or future translation as a published translation.

### Publication is per translation

A single post may have:

- English published on October 1
- Hungarian published on October 5
- Spanish still in draft

Publishing one translation must not automatically publish every other translation.

## 7. Translation storage decision

Before implementation, explicitly evaluate at least:

### Option A — JSON translations

Example:

```text
posts
- title JSON
- excerpt JSON
- content JSON
```

### Option B — Translation tables

Example:

```text
posts
post_translations
```

Compare:

- Queryability
- Indexing
- Laravel ergonomics
- Relationships
- Validation
- Filament integration
- Locale-specific publication state
- Locale-specific `published_at`
- Revisions
- Search
- Database portability
- Maintainability
- Future extensibility
- Performance
- Migration complexity

Do not introduce a generic translation manager abstraction unless the architecture demonstrates a real need for one.

The final architecture must document the selected approach and why.

## 8. Publishing lifecycle

Use an explicit `PostStatus` enum or equivalent domain representation.

Initial states:

- `draft`
- `scheduled`
- `published`

Publication visibility should be determined through a single well-defined domain/query abstraction.

A translation should be publicly visible only when:

```text
status = published
AND
published_at <= now()
```

If scheduled:

```text
status = scheduled
AND
published_at > now()
```

The exact representation may differ if the architecture audit identifies a better Laravel-native design.

Do not duplicate publication conditions throughout controllers, views, repositories, and Filament resources.

## 9. Slugs integration

Localized URLs should support patterns such as:

```text
/en/blog/my-first-post
/hu/blog/elso-bejegyzesem
/es/blog/mi-primera-publicacion
```

Each translation may have a different slug.

Blog must:

- request/generate slugs through `basekit-laravel-slugs`
- use its public API
- not duplicate slug-generation logic
- not duplicate slug uniqueness rules
- expose the translation/locale context required by the Slugs package

The exact route and API integration must be confirmed during the audit.

## 10. SEO integration

Blog must integrate with `basekit-laravel-seo`.

Blog should provide enough information for SEO to generate:

- canonical URLs
- alternate language URLs
- hreflang
- article structured data
- localized sitemap entries
- appropriate metadata

Blog must not create a second SEO system.

## 11. Categories and tags

Categories and tags are separate domain concepts.

Categories should support:

- Name
- Description
- Localized content
- Localized slugs through Slugs
- Posts relationship

Tags should support:

- Name
- Localized content
- Localized slugs through Slugs
- Posts relationship

Category/tag filtering must respect the current locale and publication state.

## 12. Revisions

Posts should support revisions.

A revision should preserve relevant historical post data, including as appropriate:

- Title
- Excerpt
- Content
- Translation/locale
- Author/editor
- Timestamp
- Other content fields required to restore a meaningful version

Initial scope:

- Create revision
- List revisions
- Restore revision

Visual diff can be deferred.

The exact revision schema must be evaluated together with the chosen localization architecture.

## 13. Search

Initial search should be database-backed.

Search should support, at minimum:

- title
- excerpt
- content

Search must respect the current locale.

Do not require Meilisearch, Elasticsearch, Typesense, or another external search engine for the initial package.

The architecture should leave a clean path for future Scout/search-engine integrations.

## 14. Frontend

Default routes should be configurable and possible to disable.

Potential defaults:

```text
/blog
/blog/{post}
/blog/category/{category}
/blog/tag/{tag}
/blog/author/{author}
/blog/search
/blog/feed
```

With localization, the architecture may support:

```text
/{locale}/blog
/{locale}/blog/{post}
```

or another documented routing strategy.

The exact routing approach must be selected during Phase 0.

Frontend views must be replaceable.

Applications must be able to:

- use the package frontend
- replace individual views
- disable package routes
- build a completely custom frontend

## 15. Feeds

Support RSS and/or Atom feeds.

Feeds must:

- respect locale
- include only publicly available translations
- not expose drafts
- not expose scheduled/future content
- use localized URLs
- integrate cleanly with SEO where appropriate

The initial feed design should avoid mixing languages unless explicitly configured.

## 16. Admin architecture

The Blog core must not require Filament.

Recommended initial structure:

```text
src/
    Actions/
    Contracts/
    Enums/
    Events/
    Filament/
    Http/
    Models/
    Policies/
    Queries/
    Services/
    Support/
```

Filament resources should live in the optional integration area and call core actions/services.

Examples:

```text
CreatePost
UpdatePost
PublishPost
UnpublishPost
SchedulePost
DeletePost
RestorePost
RestoreRevision
```

Do not create action classes merely to increase abstraction. Use actions where they provide reusable behavior, transaction boundaries, authorization boundaries, events, or testability.

## 17. Filament localization UX

The Filament integration should make multilingual editing practical.

The editor should allow:

- Selecting a locale
- Seeing available translations
- Seeing missing translations
- Creating a translation
- Editing a translation
- Publishing/unpublishing a translation
- Scheduling a translation
- Managing its localized slug

The interface should make it difficult to accidentally publish one language when another language is incomplete.

The exact Filament UX should be designed after the core translation architecture is chosen.

## 18. Events

Consider:

```text
PostCreated
PostUpdated
PostPublished
PostUnpublished
PostScheduled
PostDeleted
PostRestored
```

Events should contain useful domain information without leaking admin-specific implementation details.

Events must be documented if they become part of the public API.

## 19. Policies and authorization

At minimum, consider:

- view unpublished post
- create post
- update post
- publish post
- schedule post
- delete post
- restore post
- restore revision

Public frontend requests must never bypass publication visibility rules.

Preview functionality, if implemented, must use an explicit secure mechanism and must not accidentally expose unpublished content.

## 20. Security

Pay particular attention to:

- Draft leakage
- Scheduled post leakage
- Unauthorized publishing
- Unauthorized revision restoration
- Mass assignment
- Content rendering
- HTML sanitization boundaries
- Route model binding
- Preview URLs/tokens
- Cross-locale data leakage
- Admin authorization

## 21. Performance

Evaluate:

- Indexes
- N+1 queries
- Eager loading
- Pagination
- Search query performance
- Locale filtering
- Publication filtering
- Category/tag filtering
- Feed queries
- Sitemap discovery

Do not add aggressive caching before measuring actual query patterns.

## 22. Database design

Likely core tables:

```text
posts
post_translations
categories
category_translations
tags
tag_translations
category_post
post_tag
post_revisions
```

This is an architectural starting point, not a fixed schema. The final schema must be derived after evaluating localization.

Indexes should support locale, publication status, publication date, relationships, slug lookup, and search-related queries where practical.

## 23. Configuration

Configuration should cover, where justified:

- enabled/disabled package
- locales where necessary
- route prefix
- route names
- frontend routes enabled/disabled
- frontend view namespace
- pagination
- models
- User model
- feeds
- featured image handling
- default locale behavior
- Filament integration
- publication behavior

Avoid duplicating Laravel's existing localization configuration without a clear reason.

Use `app()->getLocale()` and `config('app.fallback_locale')` where appropriate.

## 24. Package translations

All package-facing strings must be translatable.

This includes admin labels, forms, validation, notifications, publication actions, search UI, empty states, pagination, revisions, frontend labels, and Filament resources/actions.

Use standard Laravel language files, for example:

```text
lang/
    en/
        blog.php
    hu/
        blog.php
    es/
        blog.php
```

## 25. Testing strategy

Tests must cover domain behavior and integration boundaries.

Minimum areas:

### Posts

- create
- update
- delete
- restore
- draft visibility
- published visibility
- scheduled visibility

### Localization

- create translation
- update translation
- missing translation
- multiple locales
- independent publication
- independent scheduling
- locale-aware queries
- locale-aware routes

### Slugs

- localized slug integration
- uniqueness
- localized route resolution

### Categories/tags

- CRUD
- translations
- relationships
- locale-aware filtering

### Revisions

- revision creation
- revision listing
- revision restoration

### Search

- title
- excerpt
- content
- locale filtering
- publication filtering

### Frontend

- index
- post page
- category
- tag
- author
- search
- feeds
- localized routes

### Security

- unpublished content cannot leak
- authorization works
- preview cannot be abused

### SEO

Test the data exposed to SEO rather than duplicating SEO implementation.

### Filament

Verify integration behavior without re-testing core domain logic.

## 26. Documentation

Eventually provide:

```text
README.md
CHANGELOG.md
LICENSE
docs/
    01_ARCHITECTURE.md
    02_INSTALLATION.md
    03_CONFIGURATION.md
    04_LOCALIZATION.md
    05_PUBLISHING.md
    06_SLUGS.md
    07_SEO.md
    08_FILAMENT.md
    09_FRONTEND.md
    10_EVENTS.md
    11_TESTING.md
```

## 27. Implementation phases

### Phase 0 — Audit and architecture decision

Do not implement features yet.

Inspect the repository, dependencies, package APIs, tests, docs, and Filament integration.

Produce:

1. Current-state audit
2. Gap analysis
3. Proposed domain model
4. Localization architecture comparison
5. Final localization recommendation
6. Database schema proposal
7. Public API proposal
8. Slugs integration plan
9. SEO integration plan
10. Filament integration plan
11. Test plan
12. Documentation plan
13. Ordered implementation phases

### Phase 1 — Core domain and localization

Implement Posts, translations, Categories, Tags, relationships, User/author relationship, configuration, localization infrastructure, and tests.

### Phase 2 — Publishing

Implement status, published state, scheduling, locale-specific publication, actions, authorization, events, and tests.

### Phase 3 — Slugs

Integrate localized slugs, generation, uniqueness, and localized route resolution through `basekit-laravel-slugs`.

### Phase 4 — Frontend

Implement blog index, post, category, tag, author, search, pagination, localized routes, replaceable views, and publication filtering.

### Phase 5 — SEO integration

Integrate canonical information, alternate locale URLs, hreflang data, article data, and sitemap URL discovery through `basekit-laravel-seo`.

### Phase 6 — Revisions

Implement revision creation, listing, restoration, localization-aware revisions, and tests.

### Phase 7 — Feeds

Implement RSS/Atom, locale-aware feeds, published-only filtering, and localized URLs.

### Phase 8 — Filament

Implement resources, forms, tables, translation management, publication actions, scheduling, revision management, filters, and notifications.

### Phase 9 — Documentation and release

Complete README, documentation, changelog, API documentation, localization/integration docs, tests, static analysis, formatting, and release checklist.

## 28. Definition of Done

The package is ready for release when:

- Core domain is independent of Filament.
- Localization is first-class.
- Posts, categories, and tags support translations.
- Each translation can have its own publication state.
- Localized slugs are provided through `basekit-laravel-slugs`.
- SEO is provided through `basekit-laravel-seo`.
- Draft and scheduled content cannot leak.
- Search respects locale and publication state.
- Feeds respect locale and publication state.
- Frontend routes are configurable.
- Views are replaceable.
- Filament is optional.
- Filament uses core domain operations.
- Revisions work.
- Tests cover multilingual behavior.
- Package translations exist.
- Documentation is complete.
- Public APIs are documented.
- No unnecessary third-party dependency is introduced.
- Static analysis and formatting pass.
- The package can be installed into a clean Laravel application.

## 29. OpenCode implementation rules

1. Read this document completely before making architectural changes.
2. Inspect the actual repository before assuming functionality exists.
3. Inspect the actual APIs of `basekit-laravel-seo` and `basekit-laravel-slugs`.
4. Do not invent APIs for related packages.
5. Do not create or suggest `basekit-laravel-content`.
6. Do not add Filament as a core dependency unless explicitly justified.
7. Do not introduce a translation package without documenting why it is required.
8. Do not implement localization as an afterthought.
9. Do not duplicate slug generation.
10. Do not duplicate SEO functionality.
11. Do not put business logic into Filament resources.
12. Prefer Laravel conventions.
13. Keep public APIs small and explicit.
14. Add tests with each feature.
15. Update documentation when public behavior changes.
16. Preserve backwards compatibility unless a deliberate breaking change is documented.
17. Avoid speculative abstractions.
18. Never expose unpublished content accidentally.
19. Stop after each requested phase and report what changed, what was verified, and what remains.

## 30. Phase completion report

At the end of each phase, report:

```text
Phase:
Status:

Implemented:
- ...

Tests:
- ...

Static analysis:
- ...

Documentation:
- ...

Public API changes:
- ...

Architectural decisions:
- ...

Known limitations:
- ...

Next phase:
- ...
```
