<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelBlog\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Post extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'content',
        'category',
        'tags',
        'featured_image',
        'image_alt',
        'author',
        'reading_time',
        'featured',
        'is_published',
        'published_at',
        'seo_title',
        'seo_description',
    ];

    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'featured' => 'boolean',
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('is_published', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderByDesc('published_at');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function getPublishedShortAttribute(): string
    {
        return $this->published_at?->format('M j, Y') ?? '';
    }

    public function getSeoTitleAttribute(): string
    {
        return ($this->attributes['seo_title'] ?? null) ?: $this->title;
    }

    public function getSeoDescriptionAttribute(): string
    {
        return ($this->attributes['seo_description'] ?? null)
            ?: Str::of(strip_tags((string) $this->excerpt))->limit(160)->toString();
    }
}
