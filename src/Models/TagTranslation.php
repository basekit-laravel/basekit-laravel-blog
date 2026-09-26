<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelBlog\Models;

use BasekitLaravel\BasekitLaravelBlog\Database\Factories\TagTranslationFactory;
use BasekitLaravel\BasekitLaravelBlog\Support\SlugSync;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $tag_id
 * @property string $locale
 * @property string $name
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class TagTranslation extends Model
{
    /** @use HasFactory<TagTranslationFactory> */
    use HasFactory;

    protected $fillable = [
        'tag_id',
        'locale',
        'name',
        'meta_title',
        'meta_description',
    ];

    /**
     * @return Factory<TagTranslation>
     */
    protected static function newFactory(): Factory
    {
        return TagTranslationFactory::new();
    }

    /**
     * @return BelongsTo<Tag, $this>
     */
    public function tag(): BelongsTo
    {
        return $this->belongsTo(Tag::class);
    }

    /**
     * Give the parent tag a slug for this locale the first time the locale is
     * saved.
     */
    protected static function booted(): void
    {
        static::saved(function (self $translation): void {
            $tag = $translation->tag;

            if ($tag instanceof Tag) {
                app(SlugSync::class)->ensureSlug($tag, $translation->locale, $translation->name);
            }
        });

        static::deleted(function (self $translation): void {
            $tag = $translation->tag;

            if ($tag instanceof Tag) {
                app(SlugSync::class)->forgetSlug($tag, $translation->locale);
            }
        });
    }

    /**
     * @param  Builder<TagTranslation>  $query
     * @return Builder<TagTranslation>
     */
    public function scopeForLocale(Builder $query, string $locale): Builder
    {
        return $query->where('locale', $locale);
    }
}
