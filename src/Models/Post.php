<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelBlog\Models;

use BasekitLaravel\BasekitLaravelBlog\Contracts\HasLocalizedSlugs;
use BasekitLaravel\BasekitLaravelBlog\Database\Factories\PostFactory;
use BasekitLaravel\BasekitLaravelBlog\Support\LocaleRegistry;
use BasekitLaravel\BasekitLaravelSlugs\HasSlugs;
use BasekitLaravel\BasekitLaravelSlugs\Models\Slug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * A post is a language independent identity: it owns the author, the
 * publication records, the taxonomy assignments, the revisions and one slug per
 * locale. Everything a reader sees lives on its translations.
 *
 * @property int $id
 * @property int|null $author_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, PostTranslation> $translations
 * @property-read Collection<int, Category> $categories
 * @property-read Collection<int, Tag> $tags
 * @property-read Collection<int, PostRevision> $revisions
 * @property-read Collection<int, Slug> $slugs
 */
class Post extends Model implements HasLocalizedSlugs
{
    /** @use HasFactory<PostFactory> */
    use HasFactory;

    use HasSlugs;
    use SoftDeletes;

    /**
     * The blog has no public write endpoint, so only trusted admin writes
     * (package actions and the Filament resource) reach these attributes.
     *
     * @var list<string>
     */
    protected $fillable = [
        'author_id',
    ];

    /**
     * @return Factory<Post>
     */
    protected static function newFactory(): Factory
    {
        return PostFactory::new();
    }

    /**
     * @return BelongsTo<Model, $this>
     */
    public function author(): BelongsTo
    {
        /** @var class-string<Model> $userModel */
        $userModel = (string) config('basekit-laravel-blog.models.user', 'App\\Models\\User');

        return $this->belongsTo($userModel, 'author_id');
    }

    /**
     * @return HasMany<PostTranslation, $this>
     */
    public function translations(): HasMany
    {
        return $this->hasMany(PostTranslation::class);
    }

    /**
     * @return BelongsToMany<Category, $this>
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_post');
    }

    /**
     * @return BelongsToMany<Tag, $this>
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'post_tag');
    }

    /**
     * @return HasMany<PostRevision, $this>
     */
    public function revisions(): HasMany
    {
        return $this->hasMany(PostRevision::class);
    }

    /**
     * The translation of a locale, or null when the post is not translated
     * into it. Callers decide what a missing translation means: the show
     * action answers 404, the feed simply omits the post.
     */
    public function translation(string $locale): ?PostTranslation
    {
        $locale = LocaleRegistry::normalize($locale);

        if ($this->relationLoaded('translations')) {
            $translation = $this->translations->firstWhere('locale', $locale);

            return $translation instanceof PostTranslation ? $translation : null;
        }

        return $this->translations()->where('locale', $locale)->first();
    }

    /**
     * The publicly readable translations at the given moment, newest first.
     *
     * @return Collection<int, PostTranslation>
     */
    public function publiclyVisibleTranslations(?string $locale = null): Collection
    {
        $query = $this->translations()->publiclyVisible();

        if ($locale !== null) {
            $query->forLocale($locale);
        }

        return $query->orderByDesc('published_at')->get();
    }

    /**
     * The locales this post is publicly readable in.
     *
     * @return list<string>
     */
    public function publishedLocales(): array
    {
        return $this->publiclyVisibleTranslations()
            ->pluck('locale')
            ->all();
    }

    /**
     * Whether the post is readable by the public in the given locale.
     */
    public function isPublishedIn(string $locale): bool
    {
        return $this->translation($locale)?->isPubliclyVisible() ?? false;
    }

    /**
     * @param  Builder<Post>  $query
     * @return Builder<Post>
     */
    public function scopeWithTranslationIn(Builder $query, string $locale): Builder
    {
        return $query->whereHas('translations', static function (Builder $translations) use ($locale): void {
            $translations->forLocale($locale);
        });
    }
}
