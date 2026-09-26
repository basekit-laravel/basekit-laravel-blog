<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelBlog\Database\Factories;

use BasekitLaravel\BasekitLaravelBlog\Enums\PostStatus;
use BasekitLaravel\BasekitLaravelBlog\Models\Post;
use BasekitLaravel\BasekitLaravelBlog\Models\PostTranslation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PostTranslation>
 */
class PostTranslationFactory extends Factory
{
    protected $model = PostTranslation::class;

    public function definition(): array
    {
        return [
            'post_id' => Post::factory(),
            'locale' => 'en',
            'title' => fake()->sentence(4),
            'excerpt' => fake()->sentence(),
            'content' => '<p>'.fake()->paragraph().'</p>',
            'status' => PostStatus::Draft,
            'is_featured' => false,
        ];
    }

    public function forLocale(string $locale): static
    {
        return $this->state(fn (array $attributes): array => ['locale' => $locale]);
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => PostStatus::Published,
            'published_at' => now()->subMinute(),
        ]);
    }

    public function scheduled(?\DateTimeInterface $publishedAt = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => PostStatus::Scheduled,
            'published_at' => $publishedAt ?? now()->addDay(),
        ]);
    }
}
