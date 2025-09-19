# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Development Commands

### PHP Development
- **Run tests**: `composer test` (Pest framework)
- **Test coverage**: `composer test-coverage`
- **Static analysis**: `composer analyse` (PHPStan level 1)
- **Code formatting**: `composer format` (Laravel Pint)

### Frontend Development
- **Development mode**: `npm run dev` (parallel Tailwind watch + JS dev build)
- **Production build**: `npm run build` (minified CSS/JS + Filament purge)
- **CSS only**: `npm run dev:styles` / `npm run build:styles`
- **JS only**: `npm run dev:scripts` / `npm run build:scripts`

### Laravel Commands
- **Package installation**: `php artisan vendor:publish --tag="kit-migrations"` then `php artisan migrate`
- **Create admin**: `php artisan make:admin`
- **Create languages**: `php artisan kit:create-languages`
- **Make home page**: `php artisan make:home-page`
- **Update system**: `php artisan kit:update`

## Architecture Overview

### Core Package Structure
This is a Laravel package (`smart-cms/kit`) that provides CMS functionality to Laravel applications. Built on Filament v4 for the admin interface, it offers a complete content management system that developers can integrate into their Laravel projects.

**Main Service Providers:**
- `KitServiceProvider`: Core package setup, middleware registration, shared variables, Blade components
- `KitPlugin`: Filament admin panel configuration with dashboard, resources, and widgets

### Key Architectural Components

**CMS Page Rendering System:**
- **Sections & Layouts**: Page content is built using reusable sections within layouts
- **Variable Parsing**: Developers define variables that are automatically parsed and made available to frontend templates
- **Template Builder Integration**: Dynamic page construction through visual template builder
- **Component System**: Blade components in `src/Components/` (Layout, Header, Footer, Theme, etc.)

**Variable Types System (Developer Interface):**
- 20+ variable types in `src/VariableTypes/` for different content needs
- Examples: `StringType`, `ImageType`, `MenuType`, `FormType`, `LinkType`, `SocialsType`
- Developers can register custom variable types for specific project needs
- Variables are automatically parsed and injected into views

**Admin Panel (Filament-based):**
- Complete admin interface for content management
- Custom authentication using `Admin` model with 'admin' guard
- Page management, menu configuration, SEO settings
- Contact form management, translations, system settings

**Page Management System:**
- `Page` model with multilingual support via `spatie/laravel-translatable`
- SEO management with metadata and structured data
- Menu system with configurable menu types
- Status management and publishing workflow

**Smart CMS Ecosystem Integration:**
This package coordinates with multiple Smart CMS packages:
- `smart-cms/template-builder`: Visual page layout construction
- `smart-cms/forms`: Contact form management and processing
- `smart-cms/lang`: Multilingual content support
- `smart-cms/menu`: Navigation management
- `smart-cms/seo`: SEO optimization tools
- `smart-cms/theme`: Theme and styling management

**Frontend Integration:**
- **Sections**: Reusable content blocks that can be combined to build pages
- **Layouts**: Page templates that define structure (header, footer, content areas)
- **Variable Injection**: Automatic parsing of developer-defined variables into templates
- **Asset Management**: CSS/JS compilation and optimization via `AssetManager`

### Middleware & Routing
- Custom middleware: `Maintenance`, `HtmlMinifier`, `UserIdentifierMiddleware`
- Multilingual routing with `Route::multilingual()` macro
- SEO-friendly URLs with automatic sitemap/robots.txt generation

## Code Conventions

**PHP Styling:**
- PSR-4 autoloading under `SmartCms\Kit\` namespace
- Laravel Pint formatting (Laravel preset)
- 4 spaces indentation, LF line endings

**JavaScript/CSS:**
- Prettier with single quotes, no semicolons, trailing commas
- Tailwind CSS utility-first approach

**Testing:**
- Pest framework with Orchestra Testbench
- Tests in `tests/` directory
- Prefer factories and in-memory SQLite for database tests

## Important Development Notes

- Run `composer analyse && composer format && composer test` before commits
- Asset changes require `npm run build`
- Package uses aggressive eager loading and strict Eloquent mode
- Models are unguarded by default
- Production environment forces HTTPS and prohibits destructive DB commands