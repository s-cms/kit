<?php

namespace SmartCms\Kit\Models\Pages;

use SmartCms\Kit\Models\Page;

/**
 * SimplePage model - a leaf page type that cannot have children.
 *
 * Simple pages are the basic content pages in your site.
 * They can have a parent (category or another page type that allows children),
 * but cannot themselves have children.
 *
 * Example usage:
 * ```php
 * // Standalone page
 * $page = SimplePage::create([
 *     'name' => 'About Us',
 *     'slug' => 'about',
 * ]);
 *
 * // Page within a category
 * $page = SimplePage::create([
 *     'name' => 'Blog Post',
 *     'slug' => 'my-first-post',
 *     'parent_id' => $category->id,
 * ]);
 * ```
 *
 * Note: This is aliased to 'page' type in the database for backward compatibility.
 */
class SimplePage extends Page
{
    protected static ?string $pageType = 'page';

    protected static ?bool $canHaveChildren = false;
}
