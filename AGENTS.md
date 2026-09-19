# basekit-laravel-blog

This package provides Basekit Laravel Blog is a reusable, optional feature package for publishing and rendering blog posts on Basekit-powered Laravel websites..

It targets PHP ^8.3|^8.4|^8.5 and Laravel ^13 and is distributed on Composer as
`basekit-laravel/basekit-laravel-blog`. All package source code lives under the `BasekitLaravel\BasekitLaravelBlog` namespace.

## What this project is

This repository is a **Laravel package**, not a standalone Laravel application. The package is
consumed by other Laravel applications, so the code must never assume application-only
scaffolding such as `app/`, a booted authentication system, `.env` files, or a local
`config/app.php`. Everything the package needs must come from its own service provider,
configuration, and published resources.

Keep this file focused on **this package**. For framework-level knowledge, consult the official
Laravel documentation, Laravel Boost (if configured), or the available documentation MCP tools —
do not embed generic Laravel guidance here.

## Repository structure

- `src/` — package source code. The service provider (`BasekitLaravel\BasekitLaravelBlog\BasekitLaravelBlogServiceProvider`) is
  registered automatically through Composer package discovery.
- `config/basekit-laravel-blog.php` — package configuration, published with
  `php artisan vendor:publish --tag="basekit-laravel-blog-config"`.
- `database/migrations/` — package migrations, published with
  `php artisan vendor:publish --tag="basekit-laravel-blog-migrations"`.
- `routes/web.php` — web routes loaded by the service provider.

- `resources/views/` — Blade views exposed under the `basekit-laravel-blog` view namespace
  (`view('basekit-laravel-blog::view-name')`).




- `tests/` — Pest feature and unit tests running against Orchestra Testbench
  (11.*).

## Development commands

Install dependencies with `composer install`.

### Tests

```bash
composer test
```
### Code style (Laravel Pint)

```bash
composer format
```
### Static analysis (PHPStan / Larastan)

```bash
composer analyse
```

### Refactoring (Rector)

```bash
composer refactor       # apply listed sets
composer refactor-dry   # preview changes
```


## Package development

When working on this package, treat it as any other piece of distributed software: the
public API you expose today is a contract your consumers rely on.

### Configuration

Extend `config/basekit-laravel-blog.php` for new options, and always read them with `config('basekit-laravel-blog.key')`
using sensible defaults. Changes to publishable config go through the service provider's
`publishes` call with the `basekit-laravel-blog-config` tag.
### Migrations

New tables and columns live in `database/migrations/`. Name files with the
`YYYY_MM_DD_HHMMSS_` prefix and follow standard Laravel migration ordering. Migrations are
published by consumers — make them forwards-compatible and avoid destructive irreversible
changes without a documented upgrade path.
### Web routes

Define web routes in `routes/web.php`.

### Views

Add Blade templates under `resources/views/`. Reference them from the consumer's app with the
namespace syntax `view('basekit-laravel-blog::name')`. Views should render standalone and never assume
the consumer's layout.



### Testing

Write Pest tests under `tests/`. Prefer Tests\TestCase when the test needs the framework
container; keep pure logic tests under `tests/Unit/`. Verify behavior from the consumer's
perspective where appropriate instead of asserting implementation details.

## Compatibility

- Respect the Composer constraints in `composer.json`: PHP ^8.3|^8.4|^8.5 and Laravel
  ^13. Do not introduce syntax, APIs, or dependencies that break the declared
  minimum versions.
- Classify dependencies correctly in `composer.json`: runtime needs go into `require`;
  development-only tooling goes into `require-dev`.
- Prefer requiring interfaces and small, well-maintained packages. Avoid adding a dependency
  where a few lines of stdlib or Illuminate code suffice.
- Package APIs are contracts. Avoid breaking changes; when they are unavoidable, follow the
  release workflow and document a migration path.
- New public classes, methods, config keys, and commands are public API — document them in the
  README and changelog.

## Agent rules

These rules apply to every AI agent working in this repository:

1. **Inspect first.** Before editing anything, read the relevant package structure, related
   classes, tests, configuration, Composer constraints, and existing patterns.
2. **Search before creating.** Before creating a new class, component, or config option, look
   for an existing equivalent.
3. **Prefer existing patterns.** Follow the package's existing architecture and conventions.
4. **Minimal changes.** Implement the smallest correct change that satisfies the request.
5. **Write tests.** Every meaningful behavior change should come with tests.
6. **Run the relevant checks.** Actually run the tests/analysis documented above, and the
   targeted subset when a full run is impractical.
7. **Format your changes.** Run the configured formatter on the files you touched.
8. **Inspect your diff.** Review what you changed before reporting completion.
9. **Do not modify generated or vendor files.** Files under `vendor/`, published resources that
   are not yours, and generated artifacts must never be hand-edited.
10. **Report honestly.** Never claim "tests pass" or "analysis is clean" unless you actually ran
    the commands and they succeeded.