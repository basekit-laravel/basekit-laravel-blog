<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelBlog\Database\Factories;

use BasekitLaravel\BasekitLaravelBlog\Models\Tag;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tag>
 */
class TagFactory extends Factory
{
    protected $model = Tag::class;

    public function definition(): array
    {
        return [];
    }
}
