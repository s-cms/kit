<?php

namespace SmartCms\Kit\Models\Pages;

use SmartCms\Kit\Models\Page;

/**
 * CategoryPage model - a page type that can contain other pages and categories.
 *
 * Categories provide a way to organize content hierarchically.
 * They can be nested up to 5 levels deep (configurable).
 *
 * Example usage:
 * ```php
 * $category = CategoryPage::create([
 *     'name' => 'Blog',
 *     'slug' => 'blog',
 * ]);
 *
 * // Create a subcategory
 * $subcategory = CategoryPage::create([
 *     'name' => 'News',
 *     'slug' => 'news',
 *     'parent_id' => $category->id,
 * ]);
 *
 * // Create a page in the category
 * $page = SimplePage::create([
 *     'name' => 'Article',
 *     'slug' => 'article',
 *     'parent_id' => $subcategory->id,
 * ]);
 * ```
 */
class CategoryPage extends Page
{
    protected static ?string $pageType = 'category';

    protected static ?bool $canHaveChildren = true;

    /**
     * Get the class name for polymorphic relations.
     * Use parent Page class to maintain compatibility with existing tags and other polymorphic relations.
     */
    public function getMorphClass(): string
    {
        return Page::class;
    }
}
