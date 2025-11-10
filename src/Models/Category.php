<?php

namespace SmartCms\Kit\Models;

/**
 * Category model - a page type that can contain other pages and categories.
 *
 * Categories provide a way to organize content hierarchically.
 * They can be nested up to 5 levels deep (configurable).
 *
 * Example usage:
 * ```php
 * $category = Category::create([
 *     'name' => 'Blog',
 *     'slug' => 'blog',
 * ]);
 *
 * // Create a subcategory
 * $subcategory = Category::create([
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
class Category extends Page
{
    protected static ?string $pageType = 'category';

    protected static ?bool $canHaveChildren = true;
}
