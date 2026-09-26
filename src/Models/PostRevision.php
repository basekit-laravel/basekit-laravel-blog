<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelBlog\Models;

use BasekitLaravel\BasekitLaravelBlog\Database\Factories\PostRevisionFactory;
use BasekitLaravel\BasekitLaravelBlog\Enums\PostStatus;
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
 * @property int|null $created_by
 * @property Carbon|null $created_at
 */
class PostRevision extends Model
{
    /** @use HasFactory<PostRevisionFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

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
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => PostStatus::class,
            'published_at' => 'datetime',
            'is_featured' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return Factory<PostRevision>
     */
    protected static function newFactory(): Factory
    {
        return PostRevisionFactory::new();
    }

    /**
     * @return BelongsTo<Post, $this>
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }
}
