<?php

namespace SmartCms\Kit\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use SmartCms\Kit\Models\Page;

/**
 * @extends Factory<Page>
 */
class PageFactory extends Factory
{
    protected $model = Page::class;

    public function definition(): array
    {
        $name = $this->faker->sentence(3);
        $slug = Str::slug($name);

        return [
            'name' => [
                'en' => $name,
            ],
            'slug' => $slug,
            'status' => $this->faker->boolean(80), // 80% chance of being active
            'sorting' => $this->faker->numberBetween(1, 1000),
            'image' => null,
            'banner' => null,
            'views' => $this->faker->numberBetween(0, 10000),
            'depth' => 0,
            'parent_id' => null,
            'root_id' => null,
            'settings' => [
                'meta_title' => $this->faker->sentence(),
                'meta_description' => $this->faker->paragraph(),
                'is_categories' => false,
            ],
            'layout_id' => null,
            'layout_settings' => null,
            'is_system' => false,
            'is_root' => false,
            'published_at' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
            'created_by' => null,
            'updated_by' => null,
            'is_index' => true,
        ];
    }

    /**
     * Indicate that the page is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => true,
        ]);
    }

    /**
     * Indicate that the page is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => false,
        ]);
    }

    /**
     * Indicate that the page is published.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'published_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ]);
    }

    /**
     * Indicate that the page is unpublished.
     */
    public function unpublished(): static
    {
        return $this->state(fn (array $attributes) => [
            'published_at' => null,
        ]);
    }

    /**
     * Indicate that the page is a system page.
     */
    public function system(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_system' => true,
            'is_index' => false,
        ]);
    }

    /**
     * Indicate that the page is a root page.
     */
    public function root(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_root' => true,
            'parent_id' => null,
            'root_id' => null,
        ]);
    }

    /**
     * Indicate that the page is a category page.
     */
    public function category(): static
    {
        return $this->state(fn (array $attributes) => [
            'settings' => [
                'meta_title' => $this->faker->sentence(),
                'meta_description' => $this->faker->paragraph(),
                'is_categories' => true,
            ],
        ]);
    }

    /**
     * Indicate that the page has an image.
     */
    public function withImage(): static
    {
        return $this->state(fn (array $attributes) => [
            'image' => [
                'source' => '/images/' . $this->faker->image('public/storage/images', 640, 480, null, false),
                'alt' => $this->faker->sentence(),
            ],
        ]);
    }

    /**
     * Indicate that the page has a banner.
     */
    public function withBanner(): static
    {
        return $this->state(fn (array $attributes) => [
            'banner' => [
                'source' => '/images/' . $this->faker->image('public/storage/images', 1200, 400, null, false),
                'alt' => $this->faker->sentence(),
            ],
        ]);
    }

    /**
     * Indicate that the page has high views.
     */
    public function popular(): static
    {
        return $this->state(fn (array $attributes) => [
            'views' => $this->faker->numberBetween(10000, 100000),
        ]);
    }

    /**
     * Indicate that the page is not indexed.
     */
    public function noIndex(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_index' => false,
        ]);
    }

    /**
     * Create a page with a specific parent.
     */
    public function withParent(Page $parent): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => $parent->id,
            'root_id' => $parent->root_id ?? $parent->id,
            'depth' => $parent->depth + 1,
        ]);
    }

    /**
     * Create a child page under a root page.
     */
    public function asChildOf(Page $root): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => $root->id,
            'root_id' => $root->id,
            'depth' => 1,
        ]);
    }
}
