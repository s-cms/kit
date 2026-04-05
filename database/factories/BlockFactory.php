<?php

namespace SmartCms\Kit\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use SmartCms\Kit\Models\Block;

/**
 * @extends Factory<Block>
 */
class BlockFactory extends Factory
{
    protected $model = Block::class;

    public function definition(): array
    {
        return [
            'type' => 'TestBlock',
            'title' => $this->faker->words(3, true),
            'status' => $this->faker->boolean(80), // 80% chance of being active
            'schema' => [
                'type' => 'object',
                'properties' => [
                    'title' => [
                        'type' => 'string',
                        'default' => '',
                    ],
                    'subtitle' => [
                        'type' => 'string',
                        'default' => '',
                    ],
                ],
            ],
            'data' => [
                'en' => [
                    'title' => $this->faker->sentence(),
                    'subtitle' => $this->faker->sentence(),
                ],
            ],
        ];
    }

    /**
     * Indicate that the block is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => true,
        ]);
    }

    /**
     * Indicate that the block is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => false,
        ]);
    }

    /**
     * Create a block with a specific type.
     */
    public function type(string $type): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => $type,
        ]);
    }

    /**
     * Create a block with multilingual data.
     */
    public function withLanguages(array $languages = ['en', 'uk']): static
    {
        return $this->state(function (array $attributes) use ($languages) {
            $data = [];
            foreach ($languages as $lang) {
                $data[$lang] = [
                    'title' => $this->faker->sentence(),
                    'subtitle' => $this->faker->sentence(),
                ];
            }

            return ['data' => $data];
        });
    }

    /**
     * Create a header section block.
     */
    public function headerSection(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'HeaderSection',
            'title' => 'Header Section',
            'schema' => [
                'id' => 'HeaderSection',
                'type' => 'object',
                'properties' => [
                    'logo' => [
                        'type' => 'string',
                        'inputType' => 'image',
                        'default' => '',
                    ],
                    'main_navigation' => [
                        'type' => 'array',
                        'inputType' => 'menu',
                        'default' => [],
                    ],
                ],
            ],
            'data' => [
                'en' => [
                    'logo' => '/images/logo.png',
                    'main_navigation' => 1,
                ],
            ],
        ]);
    }

    /**
     * Create a footer section block.
     */
    public function footerSection(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'FooterSection',
            'title' => 'Footer Section',
            'schema' => [
                'id' => 'FooterSection',
                'type' => 'object',
                'properties' => [
                    'copyright' => [
                        'type' => 'string',
                        'default' => '',
                    ],
                    'footer_menu' => [
                        'type' => 'array',
                        'inputType' => 'menu',
                        'default' => [],
                    ],
                ],
            ],
            'data' => [
                'en' => [
                    'copyright' => '© 2024 Company Name',
                    'footer_menu' => 2,
                ],
            ],
        ]);
    }

    /**
     * Create a hero section block.
     */
    public function heroSection(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'HeroSection',
            'title' => 'Hero Section',
            'schema' => [
                'id' => 'HeroSection',
                'type' => 'object',
                'properties' => [
                    'title' => [
                        'type' => 'string',
                        'default' => '',
                    ],
                    'subtitle' => [
                        'type' => 'string',
                        'default' => '',
                    ],
                    'cta_text' => [
                        'type' => 'string',
                        'default' => '',
                    ],
                    'cta_link' => [
                        'type' => 'string',
                        'inputType' => 'link',
                        'default' => '',
                    ],
                    'background_image' => [
                        'type' => 'string',
                        'inputType' => 'image',
                        'default' => '',
                    ],
                ],
            ],
            'data' => [
                'en' => [
                    'title' => $this->faker->sentence(),
                    'subtitle' => $this->faker->paragraph(),
                    'cta_text' => 'Learn More',
                    'cta_link' => '/about',
                    'background_image' => '/images/hero-bg.jpg',
                ],
            ],
        ]);
    }

    /**
     * Create a block with custom schema.
     */
    public function withSchema(array $schema): static
    {
        return $this->state(fn (array $attributes) => [
            'schema' => $schema,
        ]);
    }

    /**
     * Create a block with custom data.
     */
    public function withData(array $data): static
    {
        return $this->state(fn (array $attributes) => [
            'data' => $data,
        ]);
    }

    /**
     * Create a block with menu field.
     */
    public function withMenuField(int $menuId = 1): static
    {
        return $this->state(function (array $attributes) use ($menuId) {
            return [
                'schema' => array_merge($attributes['schema'] ?? [], [
                    'properties' => [
                        'main_navigation' => [
                            'type' => 'array',
                            'inputType' => 'menu',
                            'default' => [],
                        ],
                    ],
                ]),
                'data' => [
                    'en' => [
                        'main_navigation' => $menuId,
                    ],
                ],
            ];
        });
    }

    /**
     * Create an empty block with minimal data.
     */
    public function empty(): static
    {
        return $this->state(fn (array $attributes) => [
            'data' => [
                'en' => [],
            ],
        ]);
    }

    /**
     * Create a block with complex nested structure.
     */
    public function withNestedData(): static
    {
        return $this->state(fn (array $attributes) => [
            'schema' => [
                'type' => 'object',
                'properties' => [
                    'sections' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'title' => ['type' => 'string', 'default' => ''],
                                'content' => ['type' => 'string', 'default' => ''],
                            ],
                        ],
                    ],
                ],
            ],
            'data' => [
                'en' => [
                    'sections' => [
                        [
                            'title' => $this->faker->sentence(),
                            'content' => $this->faker->paragraph(),
                        ],
                        [
                            'title' => $this->faker->sentence(),
                            'content' => $this->faker->paragraph(),
                        ],
                    ],
                ],
            ],
        ]);
    }
}
