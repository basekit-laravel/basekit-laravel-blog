<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelBlog\Models;

use BasekitLaravel\BasekitLaravelBlog\Database\Factories\CategoryTranslationFactory;
use BasekitLaravel\BasekitLaravelBlog\Support\SlugSync;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $category_id
 * @property string $locale
 * @property string $name
 * @property string|null $description
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class CategoryTranslation extends Model
{
    /** @use HasFactory<CategoryTranslationFactory> */
    use HasFactory;

    protected $fillable = [
        'category_id',
        'locale',
        'name',
        'description',
        'meta_title',
        'meta_description',
    ];

    /**
     * @return Factory<CategoryTranslation>
     */
    protected static function newFactory(): Factory
    {
        return CategoryTranslationFactory::new();
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Give the parent category a slug for this locale the first time the
     * locale is saved, and release the slug again when the locale is removed.
     */
    protected static function booted(): void
    {
        static::saved(function (self $translation): void {
            $category = $translation->category;

            if ($category instanceof Category) {
                app(SlugSync::class)->ensureSlug($category, $translation->locale, $translation->name);
            }
        });

        static::deleted(function (self $translation): void {
            $category = $translation->category;

            if ($category instanceof Category) {
                app(SlugSync::class)->forgetSlug($category, $translation->locale);
            }
        });
    }

    /**
     * @param  Builder<CategoryTranslation>  $query
     * @return Builder<CategoryTranslation>
     */
    public function scopeForLocale(Builder $query, string $locale): Builder
    {
        return $query->where('locale', $locale);
    }
}
