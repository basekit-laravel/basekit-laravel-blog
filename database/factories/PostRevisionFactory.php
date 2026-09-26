<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelBlog\Database\Factories;

use BasekitLaravel\BasekitLaravelBlog\Enums\PostStatus;
use BasekitLaravel\BasekitLaravelBlog\Models\Post;
use BasekitLaravel\BasekitLaravelBlog\Models\PostRevision;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PostRevision>
 */
class PostRevisionFactory extends Factory
{
    protected $model = PostRevision::class;

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
}
