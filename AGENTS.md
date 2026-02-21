# AGENTS.md - Better File Name AI

WordPress plugin that renames uploaded files and generates alt text using OpenAI GPT-4 Vision, plus DALL-E featured image generation.

## Architecture

-   **Language:** PHP 8.1+ (backend), JavaScript/JSX (frontend, WordPress block editor)
-   **Type:** WordPress plugin (`wordpress-plugin` composer type)
-   **Namespace:** `Better_File_Name_Ai\` (PSR-4 autoloaded from `src/`)
-   **Entry point:** `better-file-name.php` (plugin bootstrap)
-   **PHP source:** `src/` (8 classes: Admin, Settings, Openai_Wrapper, Image, File_Path, Alt_Text_Rest_Api, Dalle_Image_Generator, Generate_Alt_Text_Cli)
-   **JS source:** `js/` (index.js, media-alt-text.js) built with `@wordpress/scripts` into `build/`
-   **Tests:** `tests/` (PHPUnit with WordPress test framework)
-   **Text domain:** `better-file-name`

## Build & Dev Commands

```bash
# Install dependencies
composer install
npm install

# Build JS assets (required for plugin to work)
npm run build                    # Production build
npm run start                    # Dev watch mode

# Both build from js/* into build/ directory
```

## Lint Commands

```bash
# PHP lint (PHPCS + PHPStan) - run both
composer lint

# PHP lint individually
vendor/bin/phpcs --standard=.phpcs.xml.dist
vendor/bin/phpstan analyse

# PHP auto-fix formatting
composer format

# JS lint
npm run lint:js

# JS auto-format
npm run format
```

## Test Commands

```bash
# PHPUnit requires WordPress test framework setup first:
bash bin/install-wp-tests.sh wordpress_test root '' localhost latest

# Run all PHP tests
vendor/bin/phpunit

# Run a single PHP test file
vendor/bin/phpunit tests/test-sample.php

# Run a single test method
vendor/bin/phpunit --filter test_sample

# JS unit tests
npm run test:unit

# E2E tests
npm run test:e2e
```

Test files live in `tests/`, prefixed with `test-` and suffixed with `.php`. The bootstrap at `tests/bootstrap.php` loads the WordPress test environment and the plugin.

## Code Style - PHP

### Standards & Tooling

-   **PHPCS** with WordPress Coding Standards (WPCS 3.x) via `.phpcs.xml.dist`
-   **PHPStan** level 4 with `szepeviktor/phpstan-wordpress` extension
-   PHP 8.1+ required; minimum WordPress 6.0

### Formatting

-   **Indentation:** Tabs (size 4) for PHP files, spaces (size 2) for JSON/YAML
-   **Line endings:** LF (Unix-style)
-   **Final newline:** Always insert
-   **Trailing whitespace:** Always trim
-   Every PHP file starts with `<?php` followed by `declare( strict_types=1 );`

### Naming Conventions

-   **Namespace:** `Better_File_Name_Ai\` for all classes in `src/`
-   **Classes:** `Upper_Snake_Case` (e.g., `Openai_Wrapper`, `Alt_Text_Rest_Api`, `File_Path`)
-   **Methods/functions:** `snake_case` (e.g., `get_filename`, `resize_image`, `register_routes`)
-   **Constants:** `UPPER_SNAKE_CASE` (e.g., `OPENAI_API_KEY`, `RENAME_NEW_FILE`)
-   **Variables:** `$snake_case` (e.g., `$post_id`, `$open_ai_wrapper`, `$attachment_ids`)
-   **Global prefixes:** All globals must use `Better_File_Name_Ai` or `better_file_name` prefix (enforced by PHPCS)
-   **Hooks/filters:** Use array callable syntax `[ $this, 'method_name' ]` or first-class callable `$this->method( ... )`

### Type Declarations

-   Use `declare( strict_types=1 )` in every PHP file
-   Type-hint method parameters and return types (`:void`, `:bool`, `:string`, `:array`, `:?string`)
-   Use typed class properties (`public bool $should_rename_file`, `private string $openai_api_key`)
-   Use PHP 8.1 features: `readonly` properties, first-class callables (`$this->method(...)`)

### WordPress Patterns

-   Use WordPress HTTP API (`wp_remote_request`, `wp_remote_post`) instead of cURL
-   Use `wp_json_encode()` instead of `json_encode()`
-   Escape all output: `esc_html()`, `esc_attr()`, `esc_html__()`, `wp_kses()`
-   Use `__()` / `esc_html__()` with text domain `better-file-name` for all translatable strings
-   Settings API for options: `get_option()`, `register_setting()`, `add_settings_field()`
-   REST API: `register_rest_route()` with `permission_callback` and `validate_callback`
-   Check `defined( 'ABSPATH' )` at entry point; check `WP_IMPORTING` / `WP_CLI` as needed

### Error Handling

-   Wrap OpenAI API calls in `try/catch` blocks catching `\Exception`
-   Use `error_log()` for non-fatal logging (with phpcs:ignore comment)
-   Throw `\Exception` with `esc_html__()` messages for critical failures
-   Check `is_wp_error()` on all WordPress API responses before using them
-   REST endpoints return `WP_REST_Response` with appropriate HTTP status codes (200, 404, 500)

### PHPCS Exclusions (already configured)

-   `WordPress.Files.FileName.InvalidClassFileName` - PSR-4 autoloading
-   `WordPress.Files.FileName.NotHyphenatedLowercase` - PSR-4 autoloading
-   `WordPress.PHP.YodaConditions.NotYoda` - Non-Yoda comparisons allowed
-   `Universal.Arrays.DisallowShortArraySyntax.Found` - Short array syntax `[]` is used
-   File/class/function/variable comment sniffs are relaxed

## Code Style - JavaScript

### Standards & Tooling

-   **@wordpress/scripts** (v26.19) for build, lint, and format
-   Lint with `wp-scripts lint-js` (ESLint with WordPress config)
-   Format with `wp-scripts format`

### Patterns

-   WordPress imports: `@wordpress/components`, `@wordpress/data`, `@wordpress/element`, `@wordpress/hooks`, `@wordpress/i18n`, `@wordpress/api-fetch`, `@wordpress/dom-ready`
-   Use `__()` from `@wordpress/i18n` with text domain `better-file-name`
-   JSX for React components (block editor integrations)
-   Use `useState` from `@wordpress/element` (not direct React import)
-   Use `apiFetch` from `@wordpress/api-fetch` for REST calls in editor context
-   Use native `fetch` for media library context (with `X-WP-Nonce` header)
-   async/await for API calls with try/catch/finally
-   WordPress hooks: `addFilter` from `@wordpress/hooks` for editor extension points

## CI/CD

-   **GitHub Actions** on push/PR to `main`:
    -   `composer lint` (PHPCS + PHPStan)
    -   `npm run lint:js`
-   **Deploy** on GitHub release: builds and deploys to WordPress.org SVN
-   Legacy `.travis.yml` exists but CI runs on GitHub Actions

## Key Files

| File                            | Purpose                                                          |
| ------------------------------- | ---------------------------------------------------------------- |
| `better-file-name.php`          | Plugin entry point, bootstraps all classes                       |
| `src/Settings.php`              | WordPress Settings API integration, option management            |
| `src/Admin.php`                 | Hook registration, file rename on upload, alt text on attachment |
| `src/Openai_Wrapper.php`        | OpenAI API communication (GPT-4 Vision, DALL-E)                  |
| `src/Image.php`                 | Image validation and resizing via WP image editor                |
| `src/File_Path.php`             | Resolves attachment file paths (local vs production)             |
| `src/Alt_Text_Rest_Api.php`     | REST endpoint for alt text generation                            |
| `src/Dalle_Image_Generator.php` | REST endpoint for DALL-E image generation                        |
| `src/Generate_Alt_Text_Cli.php` | WP-CLI command for batch alt text generation                     |
| `js/index.js`                   | Block editor DALL-E integration (featured image panel)           |
| `js/media-alt-text.js`          | Media library alt text generation button                         |
