# Upgrading

## To the clean-slate schema

The localized schema replaces the transitional one. `posts` is now an identity with nothing but an
author and its timestamps, and every translatable field lives on a translation table.

### What was removed

| Removed from `posts` | Replaced by |
| --- | --- |
| `title` | `post_translations.title`, one row per locale |
| `slug` | The `slugs` table of `basekit-laravel-slugs`, one row per locale |
| `excerpt`, `content` | `post_translations.excerpt`, `post_translations.content` |
| `is_published`, `published_at` | `post_translations.status`, `post_translations.published_at` |
| `featured`, `featured_image`, `image_alt` | `post_translations.is_featured`, `.featured_image`, `.image_alt` |
| `seo_title`, `seo_description` | `post_translations.meta_title`, `post_translations.meta_description` |
| `category` | A `categories` row, joined through the `category_post` pivot |
| `tags` | `tags` rows, joined through the `post_tag` pivot |
| `author` | The `author_id` relation, resolved from `models.user` |
| `reading_time` | Nothing — it is derived from the content by the theme |

`PostService` is gone as well: `SavePost`, `DeletePost`, `RestorePost` and `RestoreRevision` replace
it, and they accept a validated DTO instead of a loose array.

### Migrate an existing installation

1. Update the package and publish the new migrations:

   ```bash
   composer update basekit-laravel/basekit-laravel-blog
   php artisan vendor:publish --tag="basekit-laravel-blog-migrations"
   ```

2. Migrate the data, locale by locale. One row per existing post and locale — repeat the block for
   every locale you publish:

   ```sql
   INSERT INTO post_translations (
       post_id, locale, title, excerpt, content, status,
       published_at, is_featured, featured_image, image_alt,
       meta_title, meta_description, created_at, updated_at
   )
   SELECT
       id, 'en', title, excerpt, content,
       CASE WHEN is_published = 1 THEN 'published' ELSE 'draft' END,
       published_at, featured, featured_image, image_alt,
       seo_title, seo_description, created_at, updated_at
   FROM posts
   WHERE title IS NOT NULL;
   ```

3. Convert the taxonomy. A category and a tag are an identity plus a translated name, so both need
   one row and one translation per distinct name — and `tags` was a JSON array, which makes a script
   the honest tool:

   ```php
   use BasekitLaravel\BasekitLaravelBlog\Models\Category;
   use BasekitLaravel\BasekitLaravelBlog\Models\Post;
   use BasekitLaravel\BasekitLaravelBlog\Models\Tag;

   $categories = [];
   $tags = [];

   foreach (Post::query()->cursor() as $post) {
       $names = array_filter([
           trim((string) $post->category),
           ...array_map('trim', json_decode((string) $post->tags, true) ?: []),
       ]);

       foreach ($names as $name) {
           if ($name === '') {
               continue;
           }

           $isCategory = $name === trim((string) $post->category);

           $term = $isCategory
               ? ($categories[$name] ??= Category::query()->create())
               : ($tags[$name] ??= Tag::query()->create());

           $term->translations()->updateOrCreate(['locale' => 'en'], ['name' => $name]);

           $isCategory
               ? $post->categories()->syncWithoutDetaching([$term->id])
               : $post->tags()->syncWithoutDetaching([$term->id]);
       }
   }
   ```

   Repeat the `translations()` call for every other locale the site publishes. The pivots are
   `category_post` and `post_tag`.

4. Drop the legacy columns once the data has been verified:

   ```php
   Schema::table('posts', function (Blueprint $table): void {
       $table->dropColumn([
           'title', 'slug', 'excerpt', 'content', 'is_published', 'published_at',
           'featured', 'featured_image', 'image_alt', 'seo_title', 'seo_description',
           'category', 'tags', 'author', 'reading_time',
       ]);
   });
   ```

5. Update the write path. Anything that created or updated a post through `PostService` now builds
   a `PostData` and calls `SavePost::execute()`. If the caller shows every locale at once, set
   `replaceTranslations`, so a locale the payload omits is removed instead of kept.

6. Update the templates. A view that read `$post->title` reads `$translation->title` instead, and a
   view that pointed at `/blog/{slug}` can use `BlogUrl::post($post, $locale)`, which never builds a
   URL for a locale that has no slug.

## Routes

The routes are now named per locale, so `route('blog.index')` and `route('blog.show', $post)` no
longer exist:

| Before | Now |
| --- | --- |
| `blog.index` | `blog.index.{locale}` |
| `blog.show` | `blog.show.{locale}` |
| `blog.rss` | `blog.rss.{locale}` |

With `locale_prefix` disabled only the default locale is served, on the plain `/blog` URI; with it
enabled every supported locale is served under `/{locale}/blog`.

If your application linked to the blog by hand, replace those links with the `BlogUrl` helper:

```php
$url = app(\BasekitLaravel\BasekitLaravelBlog\Support\BlogUrl::class);

$url->index('hu');
$url->post($post, 'hu');
$url->feed('hu');
```

## Slugs

Slugs are no longer columns of `posts`, `categories` or `tags`: they are rows of the `slugs` table
owned by `basekit-laravel-slugs`: one slug per model and locale, and no two rows of the same model
type may share a slug in one locale. Move an existing slug column into it, per model and locale:

```php
use BasekitLaravel\BasekitLaravelBlog\Models\Post;
use Illuminate\Support\Str;

foreach (Post::query()->whereNotNull('slug')->cursor() as $post) {
    $post->setSlug(Str::slug($post->slug), 'en');
}
```

Or in SQL, before the legacy column is dropped:

```sql
INSERT INTO slugs (sluggable_type, sluggable_id, locale, slug, created_at, updated_at)
SELECT 'App\Models\Post', id, 'en', slug, NOW(), NOW()
FROM posts
WHERE slug IS NOT NULL;
```

A slug is generated the first time a locale is saved and is never rewritten afterwards, so a rename
no longer changes the URL of a published post. Changing one is explicit:

```php
$post->translation('en')->update(['slug' => 'a-new-url']);
```

## Filament admin

The package now ships the admin, so an application that built its own blog screens can drop them and
register the plugin instead:

```php
public function panel(Panel $panel): Panel
{
    return $panel->plugin(new \BasekitLaravel\BasekitLaravelBlog\Filament\BlogPlugin);
}
```

Filament `^5.8` is now a dependency of the consuming application. Publish the translations to
translate the navigation group, the buttons and the table columns:

```bash
php artisan vendor:publish --tag="basekit-laravel-blog-translations"
```

The translations of a post are edited as one repeater on a single form rather than through a
separate relation manager, so a save is always a complete save of the locales on the form.
