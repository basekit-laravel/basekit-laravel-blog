<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelBlog\Models;

use BasekitLaravel\BasekitLaravelBlog\Contracts\HasLocalizedName;
use BasekitLaravel\BasekitLaravelBlog\Contracts\HasLocalizedSlugs;
use BasekitLaravel\BasekitLaravelBlog\Database\Factories\CategoryFactory;
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
 * A category is a language independent identity, so it can be shared by every
 * locale of a post without duplicating assignments.
 *
 * @property int $id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, CategoryTranslation> $translations
 * @property-read Collection<int, Post> $posts
 * @property-read Collection<int, Slug> $slugs
 */
class Category extends Model implements HasLocalizedName, HasLocalizedSlugs
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

    use HasSlugs;
    use InteractsWithLocalizedNames;

    protected $guarded = [
        'id',
    ];

    /**
     * @return Factory<Category>
     */
    protected static function newFactory(): Factory
    {
        return CategoryFactory::new();
    }

    /**
     * @return HasMany<CategoryTranslation, $this>
     */
    public function translations(): HasMany
    {
        return $this->hasMany(CategoryTranslation::class);
    }

    /**
     * @return BelongsToMany<Post, $this>
     */
    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class, 'category_post');
    }

    public function translation(string $locale): ?CategoryTranslation
    {
        if ($this->relationLoaded('translations')) {
            $translation = $this->translations->firstWhere('locale', $locale);

            return $translation instanceof CategoryTranslation ? $translation : null;
        }

        return $this->translations()->where('locale', $locale)->first();
    }
}
