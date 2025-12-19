<?php

namespace SmartCms\Kit\Mcp\Servers;

use Laravel\Mcp\Server;

class SmartCmsServer extends Server
{
    /**
     * The MCP server's name.
     */
    protected string $name = 'smart-cms';

    /**
     * The MCP server's version.
     */
    protected string $version = '0.0.1';

    /**
     * The MCP server's instructions for the LLM.
     */
    protected string $instructions = 'This server provides access to a SmartCms Kit Laravel CMS instance. SmartCms is a flexible, component-based content management system built on Laravel and Filament.';
    // <<<'MARKDOWN'
    //     # SmartCms Kit MCP Server

    //     This server provides access to a SmartCms Kit Laravel CMS instance. SmartCms is a flexible,
    //     component-based content management system built on Laravel and Filament.

    //     ## Core Concepts

    //     - **Pages**: Main content containers with multilingual support
    //     - **Blocks/Sections**: Reusable content components with typed schemas
    //     - **Variable Types**: Custom field types (string, image, menu, link, etc.)
    //     - **Schemas**: JSON Schema definitions for block structure and validation
    //     - **Layouts**: Page templates that define structure
    //     - **Menus**: Navigation structures with nested items

    //     ## Available Tools

    //     ### Read Operations
    //     - Variable type discovery and schema inspection
    //     - Page and block content retrieval
    //     - Site configuration (languages, menus, SEO)

    //     ### Write Operations
    //     - Page creation and updates
    //     - Block data editing with schema validation
    //     - Menu management

    //     ## Best Practices

    //     1. Always check block schemas before updating block data
    //     2. Use merge mode when updating blocks to preserve existing data
    //     3. Validate data structures against schemas
    //     4. Consider multilingual support when working with content

    //     ## Environment

    //     This MCP server is designed for local development environments only.
    // MARKDOWN;

    /**
     * The tools registered with this MCP server.
     *
     * @var array<int, class-string<\Laravel\Mcp\Server\Tool>>
     */
    protected array $tools = [
        // Variable & Type System
        \SmartCms\Kit\Mcp\Tools\GetVariableTypes::class,
        \SmartCms\Kit\Mcp\Tools\GetVariableTypeSchema::class,
        \SmartCms\Kit\Mcp\Tools\GetGlobalVariables::class,

        // // Content Structure
        \SmartCms\Kit\Mcp\Tools\GetPages::class,
        \SmartCms\Kit\Mcp\Tools\GetPage::class,
        \SmartCms\Kit\Mcp\Tools\GetPageBlocks::class,
        \SmartCms\Kit\Mcp\Tools\GetBlockSchema::class,
        \SmartCms\Kit\Mcp\Tools\GetBlock::class,
        \SmartCms\Kit\Mcp\Tools\ReviewBlockData::class,

        // // Site Configuration
        \SmartCms\Kit\Mcp\Tools\GetLanguages::class,
        \SmartCms\Kit\Mcp\Tools\GetMenus::class,
        \SmartCms\Kit\Mcp\Tools\GetMenu::class,
        \SmartCms\Kit\Mcp\Tools\GetBlockSchemas::class,

        // // Page Management (Write Operations)
        \SmartCms\Kit\Mcp\Tools\CreatePage::class,
        \SmartCms\Kit\Mcp\Tools\UpdatePage::class,
        \SmartCms\Kit\Mcp\Tools\DeletePage::class,
        \SmartCms\Kit\Mcp\Tools\PublishPage::class,
        \SmartCms\Kit\Mcp\Tools\UnpublishPage::class,

        // // SEO
        \SmartCms\Kit\Mcp\Tools\UpdatePageSeo::class,
    ];

    /**
     * The resources registered with this MCP server.
     *
     * @var array<int, class-string<\Laravel\Mcp\Server\Resource>>
     */
    protected array $resources = [
        //
    ];

    /**
     * The prompts registered with this MCP server.
     *
     * @var array<int, class-string<\Laravel\Mcp\Server\Prompt>>
     */
    protected array $prompts = [
        //
    ];
}
