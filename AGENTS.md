# Repository Guidelines

## Project Structure & Module Organization
- Core PHP package: `src/` (PSR-4: `SmartCms\Kit\…`), service wiring in `src/KitServiceProvider.php` and plugin in `src/KitPlugin.php`.
- HTTP & routing: `routes/web.php`, handlers in `src/Http/…`.
- Config, database, stubs: `config/`, `database/`, `stubs/`.
- Frontend assets: `resources/css`, `resources/js`, images in `resources/images`, compiled output in `resources/dist`, Blade views in `resources/views`.
- Tests: `tests/` (Pest + Testbench). Build artifacts and tooling: `build/`, PHPStan cache at `build/phpstan/`.

## Build, Test, and Development Commands
- PHP unit tests: `composer test` — runs Pest.
- Coverage: `composer test-coverage` — generates coverage locally.
- Static analysis: `composer analyse` — runs PHPStan (level 1).
- PHP formatting: `composer format` — runs Laravel Pint (Laravel preset).
- Asset dev: `npm run dev` — parallel Tailwind watch and dev build.
- Asset build: `npm run build` — builds CSS/JS and purges Filament classes.
- Targeted scripts: `npm run dev:styles`, `npm run dev:scripts`, `npm run build:styles`, `npm run build:scripts`.

## Coding Style & Naming Conventions
- Indentation: 4 spaces, LF line endings (`.editorconfig`).
- PHP: Laravel Pint (preset "laravel"); follow PSR-4, one class per file, namespaced under `SmartCms\Kit`.
- JS/CSS: Prettier with single quotes, no semicolons, trailing commas (`.prettierrc`).
- Tests: suffix files with `*Test.php` (e.g., `ExampleTest.php`).

## Testing Guidelines
- Frameworks: Pest + Orchestra Testbench.
- Location: put tests in `tests/`; bootstrap via `tests/Pest.php` and `tests/TestCase.php`.
- Run locally: `composer test` (CI runs matrix across PHP 8.2–8.4, Laravel 11–12).
- Aim for deterministic tests; avoid external I/O; prefer factories and in-memory SQLite where possible.

## Commit & Pull Request Guidelines
- Commits: short, imperative subject (e.g., "fix lang switcher", "add multilanguage route helper").
- Before opening a PR: run `composer analyse`, `composer format`, `composer test`, and `npm run build` if assets changed.
- PRs should include: clear description, linked issues, reproduction steps, and screenshots for UI/Blade changes.
- Do not commit `vendor/`; keep diffs focused and small; update docs if behavior changes.

## Security & Configuration Tips
- Never include secrets in code, tests, or fixtures.
- Routes are public by default; review middleware in `routes/web.php` when exposing new endpoints.
