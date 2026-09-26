<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelBlog\Models;

use BasekitLaravel\BasekitLaravelBlog\Database\Factories\PostTranslationFactory;
use BasekitLaravel\BasekitLaravelBlog\Enums\PostStatus;
use BasekitLaravel\BasekitLaravelBlog\Support\SlugSync;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $post_id
 * @property string $locale
 * @property string $title
 * @property string|null $excerpt
 * @property string|null $content
 * @property PostStatus $status
 * @property Carbon|null $published_at
 * @property bool $is_featured
 * @property string|null $featured_image
 * @property string|null $image_alt
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class PostTranslation extends Model
{
    /** @use HasFactory<PostTranslationFactory> */
    use HasFactory;

    protected $fillable = [
        'post_id',
        'locale',
        'title',
        'excerpt',
        'content',
        'status',
        'published_at',
        'is_featured',
        'featured_image',
        'image_alt',
        'meta_title',
        'meta_description',
    ];

    protected function casts(): array
    {
        return [
            'status' => PostStatus::class,
            'published_at' => 'datetime',
            'is_featured' => 'boolean',
        ];
    }

    /**
     * Give the parent post a slug for this locale the first time the locale is
     * saved, so a published post is always addressable, and release the slug
     * again when the locale is removed.
     */
    protected static function booted(): void
    {
        static::saved(function (self $translation): void {
            $post = $translation->post;

            if ($post instanceof Post) {
                app(SlugSync::class)->ensureSlug($post, $translation->locale, $translation->title);
            }
        });

        static::deleted(function (self $translation): void {
            $post = $translation->post;

            if ($post instanceof Post) {
                app(SlugSync::class)->forgetSlug($post, $translation->locale);
            }
        });
    }

    /**
     * @return Factory<PostTranslation>
     */
    protected static function newFactory(): Factory
    {
        return PostTranslationFactory::new();
    }

    /**
     * @return BelongsTo<Post, $this>
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * @param  Builder<PostTranslation>  $query
     * @return Builder<PostTranslation>
     */
    public function scopeForLocale(Builder $query, string $locale): Builder
    {
        return $query->where('locale', $locale);
    }

    /**
     * Translations that are publicly readable at the given moment.
     *
     * Scheduled translations become visible as soon as their publication date
     * has passed, which keeps the visibility rule a pure query with no job or
     * scheduled command involved.
     *
     * @param  Builder<PostTranslation>  $query
     * @return Builder<PostTranslation>
     */
    public function scopePubliclyVisible(Builder $query, ?CarbonInterface $at = null): Builder
    {
        return $query
            ->whereIn('status', array_map(
                static fn (PostStatus $status): string => $status->value,
                PostStatus::publiclyVisible(),
            ))
            ->whereNotNull('published_at')
            ->where('published_at', '<=', $at ?? now());
    }

    public function isPubliclyVisible(?CarbonInterface $at = null): bool
    {
        $publishedAt = $this->published_at;

        return in_array($this->status, PostStatus::publiclyVisible(), true)
            && $publishedAt instanceof CarbonInterface
            && $publishedAt->lessThanOrEqualTo($at ?? now());
    }
}
