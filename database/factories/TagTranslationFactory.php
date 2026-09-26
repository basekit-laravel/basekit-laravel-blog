<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelBlog\Database\Factories;

use BasekitLaravel\BasekitLaravelBlog\Models\Tag;
use BasekitLaravel\BasekitLaravelBlog\Models\TagTranslation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TagTranslation>
 */
class TagTranslationFactory extends Factory
{
    protected $model = TagTranslation::class;

    public function definition(): array
    {
        return [
            'tag_id' => Tag::factory(),
            'locale' => 'en',
            'name' => fake()->unique()->word(),
        ];
    }

    public function forLocale(string $locale): static
    {
        return $this->state(fn (array $attributes): array => ['locale' => $locale]);
    }
}
