<?php

namespace SmartCms\Kit\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use SmartCms\Menu\Models\Menu;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\SmartCms\Menu\Models\Menu>
 */
class MenuFactory extends Factory
{
    protected $model = Menu::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(2, true) . ' Menu',
            'items' => [],
        ];
    }

    /**
     * Create a header menu.
     */
    public function header(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Header Menu',
        ]);
    }

    /**
     * Create a footer menu.
     */
    public function footer(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Footer Menu',
        ]);
    }

    /**
     * Create a sidebar menu.
     */
    public function sidebar(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Sidebar Menu',
        ]);
    }

    /**
     * Create a menu with basic items.
     */
    public function withBasicItems(int $count = 3): static
    {
        return $this->state(function (array $attributes) use ($count) {
            $items = [];
            for ($i = 0; $i < $count; $i++) {
                $items[] = [
                    'title' => $this->faker->words(2, true),
                    'url' => '/' . $this->faker->slug(),
                    'icon' => null,
                    'target' => '_self',
                ];
            }

            return ['items' => $items];
        });
    }

    /**
     * Create a menu with nested items (children).
     */
    public function withNestedItems(): static
    {
        return $this->state(fn (array $attributes) => [
            'items' => [
                [
                    'title' => 'Home',
                    'url' => '/',
                    'icon' => 'home',
                ],
                [
                    'title' => 'About',
                    'url' => '/about',
                    'icon' => 'info',
                ],
                [
                    'title' => 'Services',
                    'url' => '/services',
                    'icon' => 'briefcase',
                    'children' => [
                        [
                            'title' => 'Web Development',
                            'url' => '/services/web-development',
                        ],
                        [
                            'title' => 'Mobile Apps',
                            'url' => '/services/mobile-apps',
                        ],
                        [
                            'title' => 'Consulting',
                            'url' => '/services/consulting',
                        ],
                    ],
                ],
                [
                    'title' => 'Contact',
                    'url' => '/contact',
                    'icon' => 'mail',
                ],
            ],
        ]);
    }

    /**
     * Create a menu with external links.
     */
    public function withExternalLinks(): static
    {
        return $this->state(fn (array $attributes) => [
            'items' => [
                [
                    'title' => 'Facebook',
                    'url' => 'https://facebook.com',
                    'icon' => 'facebook',
                    'target' => '_blank',
                ],
                [
                    'title' => 'Twitter',
                    'url' => 'https://twitter.com',
                    'icon' => 'twitter',
                    'target' => '_blank',
                ],
                [
                    'title' => 'LinkedIn',
                    'url' => 'https://linkedin.com',
                    'icon' => 'linkedin',
                    'target' => '_blank',
                ],
            ],
        ]);
    }

    /**
     * Create a social media menu.
     */
    public function social(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Social Media',
        ])->withExternalLinks();
    }

    /**
     * Create a menu with multilingual items.
     */
    public function withMultilingualItems(array $languages = ['en', 'uk']): static
    {
        return $this->state(function (array $attributes) use ($languages) {
            $items = [];

            foreach ($languages as $lang) {
                $items[$lang] = [
                    [
                        'title' => $lang === 'en' ? 'Home' : 'Головна',
                        'url' => '/',
                    ],
                    [
                        'title' => $lang === 'en' ? 'About' : 'Про нас',
                        'url' => '/about',
                    ],
                    [
                        'title' => $lang === 'en' ? 'Contact' : 'Контакти',
                        'url' => '/contact',
                    ],
                ];
            }

            return ['items' => $items];
        });
    }

    /**
     * Create a menu with specific items.
     */
    public function withItems(array $items): static
    {
        return $this->state(fn (array $attributes) => [
            'items' => $items,
        ]);
    }

    /**
     * Create an empty menu.
     */
    public function empty(): static
    {
        return $this->state(fn (array $attributes) => [
            'items' => [],
        ]);
    }

    /**
     * Create a menu with icons.
     */
    public function withIcons(): static
    {
        return $this->state(fn (array $attributes) => [
            'items' => [
                [
                    'title' => 'Dashboard',
                    'url' => '/dashboard',
                    'icon' => 'dashboard',
                ],
                [
                    'title' => 'Settings',
                    'url' => '/settings',
                    'icon' => 'settings',
                ],
                [
                    'title' => 'Profile',
                    'url' => '/profile',
                    'icon' => 'user',
                ],
            ],
        ]);
    }

    /**
     * Create a breadcrumb menu.
     */
    public function breadcrumb(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Breadcrumb',
            'items' => [
                [
                    'title' => 'Home',
                    'url' => '/',
                ],
                [
                    'title' => 'Category',
                    'url' => '/category',
                ],
                [
                    'title' => 'Current Page',
                    'url' => '#',
                ],
            ],
        ]);
    }

    /**
     * Create a menu with many items for testing pagination/scrolling.
     */
    public function withManyItems(int $count = 20): static
    {
        return $this->state(function (array $attributes) use ($count) {
            $items = [];
            for ($i = 1; $i <= $count; $i++) {
                $items[] = [
                    'title' => "Item {$i}",
                    'url' => "/item-{$i}",
                ];
            }

            return ['items' => $items];
        });
    }

    /**
     * Create a menu with active state on items.
     */
    public function withActiveItem(int $activeIndex = 0): static
    {
        return $this->state(function (array $attributes) use ($activeIndex) {
            $items = [
                [
                    'title' => 'Home',
                    'url' => '/',
                    'active' => $activeIndex === 0,
                ],
                [
                    'title' => 'About',
                    'url' => '/about',
                    'active' => $activeIndex === 1,
                ],
                [
                    'title' => 'Contact',
                    'url' => '/contact',
                    'active' => $activeIndex === 2,
                ],
            ];

            return ['items' => $items];
        });
    }
}
