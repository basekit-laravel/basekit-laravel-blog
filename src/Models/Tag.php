<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelBlog\Models;

use BasekitLaravel\BasekitLaravelBlog\Contracts\HasLocalizedName;
use BasekitLaravel\BasekitLaravelBlog\Contracts\HasLocalizedSlugs;
use BasekitLaravel\BasekitLaravelBlog\Database\Factories\TagFactory;
use BasekitLaravel\BasekitLaravelBlog\Support\InteractsWithLocalizedNames;
use BasekitLaravel\BasekitLaravelSlugs\HasSlugs;
use BasekitLaravel\BasekitLaravelSlugs\Models\Slug;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A tag is a language independent identity, shared across locales.
 *
 * @property int $id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, TagTranslation> $translations
 * @property-read Collection<int, Post> $posts
 * @property-read Collection<int, Slug> $slugs
 */
class Tag extends Model implements HasLocalizedName, HasLocalizedSlugs
{
    /** @use HasFactory<TagFactory> */
    use HasFactory;

    use HasSlugs;
    use InteractsWithLocalizedNames;

    protected $guarded = [
        'id',
    ];

    /**
     * @return Factory<Tag>
     */
    protected static function newFactory(): Factory
    {
        return TagFactory::new();
    }

    /**
     * @return HasMany<TagTranslation, $this>
     */
    public function translations(): HasMany
    {
        return $this->hasMany(TagTranslation::class);
    }

    /**
     * @return BelongsToMany<Post, $this>
     */
    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class, 'post_tag');
    }

    public function translation(string $locale): ?TagTranslation
    {
        if ($this->relationLoaded('translations')) {
            $translation = $this->translations->firstWhere('locale', $locale);

            return $translation instanceof TagTranslation ? $translation : null;
        }

        return $this->translations()->where('locale', $locale)->first();
    }
}
