<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelBlog\Database\Factories;

use BasekitLaravel\BasekitLaravelBlog\Models\Category;
use BasekitLaravel\BasekitLaravelBlog\Models\CategoryTranslation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CategoryTranslation>
 */
class CategoryTranslationFactory extends Factory
{
    protected $model = CategoryTranslation::class;

    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'locale' => 'en',
            'name' => fake()->unique()->word(),
        ];
    }

    public function forLocale(string $locale): static
    {
        return $this->state(fn (array $attributes): array => ['locale' => $locale]);
    }
}
