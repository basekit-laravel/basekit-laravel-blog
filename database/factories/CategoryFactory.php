<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelBlog\Database\Factories;

use BasekitLaravel\BasekitLaravelBlog\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        return [];
    }
}
