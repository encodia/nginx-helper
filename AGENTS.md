# Repository Guidelines

## Project Structure & Module Organization

This repository is a WordPress plugin for purging nginx FastCGI/proxy and Redis caches. The plugin entry point is `nginx-helper.php`; shared bootstrap code lives in `includes/`, admin UI and purger implementations live in `admin/`, and WP-CLI support is in `class-nginx-helper-wp-cli-command.php`. Admin templates are under `admin/partials/`, browser assets under `admin/css/`, `admin/js/`, and `admin/icons/`, translations under `languages/`, and WordPress.org assets under `wpassets/`. End-to-end tests and their local utilities are in `tests/e2e-playwright/`.

## Build, Test, and Development Commands

- `composer install`: install PHP development dependencies.
- `vendor/bin/phpcs --standard=phpcs.xml`: run WordPress, WordPress VIP, and PHPCompatibility checks.
- `cd tests/e2e-playwright && npm ci`: install Playwright test dependencies.
- `cd tests/e2e-playwright && npm run build`: build the TypeScript test utility package and WordPress script assets.
- `cd tests/e2e-playwright && npm run test-e2e:playwright`: run Playwright e2e specs against a configured WordPress site.

For manual plugin testing, clone this repo into `wp-content/plugins/`, activate Nginx Helper, and use a FastCGI/Srcache or Redis-backed WordPress environment as described in `Development.md`.

## Coding Style & Naming Conventions

Follow WordPress Coding Standards as configured in `phpcs.xml`. Keep PHP compatible with PHP `5.3+`; avoid modern syntax that breaks the configured `PHPCompatibility` range. Use the existing plugin naming style: classes such as `Nginx_Helper_Admin`, files such as `class-nginx-helper-admin.php`, hooks and option keys prefixed with `nginx_helper`, and text domain `nginx-helper`. Keep JavaScript and CSS changes scoped to the admin assets unless the plugin surface requires otherwise.

## Testing Guidelines

Place Playwright specs in `tests/e2e-playwright/specs/` and keep the numbered naming pattern, for example `08_validate-new-setting-test.spec.js`. Prefer tests that exercise WordPress admin flows and cache purge behavior through the UI or WP APIs. Run PHPCS before opening a PR; run Playwright tests when changing settings screens, purge behavior, or Redis/FastCGI integration paths.

## Commit & Pull Request Guidelines

Recent history uses concise Conventional Commit-style prefixes such as `feat:`, `fix:`, and `chore:`. Keep subjects imperative and specific, for example `fix: remove purge from base URL`. Create feature branches from `master`, target PRs to `develop`, include a short behavior summary, testing notes, and screenshots for visible admin UI changes. Link related issues when available and call out cache backend requirements needed to reproduce the change.

## Security & Configuration Tips

Do not commit local WordPress, nginx, Redis, or EasyEngine credentials. Treat generated cache paths, Redis prefixes, and purge URLs as environment-specific configuration, and document any new constants or options in `README.md` or `Development.md`.
