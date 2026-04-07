<?php

namespace SmartCms\Kit\Mcp\Servers;

use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Tool;
use SmartCms\Kit\Mcp\Tools\CreatePage;
use SmartCms\Kit\Mcp\Tools\DeletePage;
use SmartCms\Kit\Mcp\Tools\GetBlock;
use SmartCms\Kit\Mcp\Tools\GetBlockSchema;
use SmartCms\Kit\Mcp\Tools\GetBlockSchemas;
use SmartCms\Kit\Mcp\Tools\GetGlobalVariables;
use SmartCms\Kit\Mcp\Tools\GetLanguages;
use SmartCms\Kit\Mcp\Tools\GetMenu;
use SmartCms\Kit\Mcp\Tools\GetMenus;
use SmartCms\Kit\Mcp\Tools\GetPage;
use SmartCms\Kit\Mcp\Tools\GetPageBlocks;
use SmartCms\Kit\Mcp\Tools\GetPages;
use SmartCms\Kit\Mcp\Tools\GetVariableTypes;
use SmartCms\Kit\Mcp\Tools\GetVariableTypeSchema;
use SmartCms\Kit\Mcp\Tools\PublishPage;
use SmartCms\Kit\Mcp\Tools\ReviewBlockData;
use SmartCms\Kit\Mcp\Tools\UnpublishPage;
use SmartCms\Kit\Mcp\Tools\UpdatePage;
use SmartCms\Kit\Mcp\Tools\UpdatePageSeo;

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
     * @var array<int, class-string<Tool>>
     */
    protected array $tools = [
        // Variable & Type System
        GetVariableTypes::class,
        GetVariableTypeSchema::class,
        GetGlobalVariables::class,

        // // Content Structure
        GetPages::class,
        GetPage::class,
        GetPageBlocks::class,
        GetBlockSchema::class,
        GetBlock::class,
        ReviewBlockData::class,

        // // Site Configuration
        GetLanguages::class,
        GetMenus::class,
        GetMenu::class,
        GetBlockSchemas::class,

        // // Page Management (Write Operations)
        CreatePage::class,
        UpdatePage::class,
        DeletePage::class,
        PublishPage::class,
        UnpublishPage::class,

        // // SEO
        UpdatePageSeo::class,
    ];

    /**
     * The resources registered with this MCP server.
     *
     * @var array<int, class-string<Server\Resource>>
     */
    protected array $resources = [
        //
    ];

    /**
     * The prompts registered with this MCP server.
     *
     * @var array<int, class-string<Prompt>>
     */
    protected array $prompts = [
        //
    ];
}
