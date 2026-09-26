<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelBlog\Data;

use BasekitLaravel\BasekitLaravelBlog\Enums\PostStatus;
use BasekitLaravel\BasekitLaravelBlog\Support\LocaleRegistry;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * The localized content of a post for a single locale.
 */
final readonly class PostTranslationData
{
    public function __construct(
        public string $locale,
        public string $title,
        public ?string $excerpt,
        public ?string $content,
        public PostStatus $status,
        public ?CarbonInterface $publishedAt,
        public bool $isFeatured,
        public ?string $featuredImage,
        public ?string $imageAlt,
        public ?string $metaTitle,
        public ?string $metaDescription,
        public ?string $slug = null,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public static function fromArray(array $input, string $locale): self
    {
        $status = $input['status'] ?? PostStatus::Draft;

        return new self(
            locale: LocaleRegistry::normalize($locale),
            title: (string) $input['title'],
            excerpt: self::nullableString($input['excerpt'] ?? null),
            content: self::nullableString($input['content'] ?? null),
            status: $status instanceof PostStatus ? $status : PostStatus::from((string) $status),
            publishedAt: self::toDate($input['published_at'] ?? null),
            isFeatured: filter_var($input['is_featured'] ?? false, FILTER_VALIDATE_BOOL),
            featuredImage: self::nullableString($input['featured_image'] ?? null),
            imageAlt: self::nullableString($input['image_alt'] ?? null),
            metaTitle: self::nullableString($input['meta_title'] ?? null),
            metaDescription: self::nullableString($input['meta_description'] ?? null),
            slug: self::nullableString($input['slug'] ?? null),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toAttributes(): array
    {
        return [
            'locale' => $this->locale,
            'title' => $this->title,
            'excerpt' => $this->excerpt,
            'content' => $this->content,
            'status' => $this->status,
            'published_at' => $this->publishedAt,
            'is_featured' => $this->isFeatured,
            'featured_image' => $this->featuredImage,
            'image_alt' => $this->imageAlt,
            'meta_title' => $this->metaTitle,
            'meta_description' => $this->metaDescription,
        ];
    }

    private static function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private static function toDate(mixed $value): ?CarbonInterface
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof CarbonInterface) {
            return $value;
        }

        return Carbon::parse((string) $value);
    }
}
