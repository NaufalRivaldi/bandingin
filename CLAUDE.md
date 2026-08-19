# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

```bash
composer dev          # all dev processes (php artisan dev: server, queue, logs, vite)
composer test         # clears config, then php artisan test
php artisan test --compact --filter=testName   # single test / filtered run
php artisan pail      # tail application logs
vendor/bin/pint --dirty --format agent          # required after editing any PHP file
npm run build         # production asset build (npm run dev for the Vite watcher alone)
```

`composer setup` does a first-time install (deps, `.env`, key, migrate, npm build).

## State of the repository

This is an unmodified Laravel 13 skeleton on PHP 8.5 — no domain code exists yet. `app/` holds only the base `Controller`, the default `User` model, and `AppServiceProvider`; `routes/web.php` has a single closure returning the `welcome` view. Anything you build will be the first of its kind here, so there are no sibling files to copy conventions from. Establish patterns deliberately and record them with the Boost `record-rule` MCP tool (see below).

## Notable configuration

- **SQLite by default**, at `database/database.sqlite`. Cache, queue, and session all use the `database` driver, so their tables come from the three baseline migrations. Tests override this to an in-memory SQLite database (`phpunit.xml`).
- **`RefreshDatabase` is commented out** in `tests/Pest.php`. The moment a test touches the database, uncomment it rather than adding the trait per-file.
- **Exceptions render as JSON** for `api/*` paths and `expectsJson()` requests (`bootstrap/app.php`), even though no API routes file exists yet — add one via `withRouting(api: ...)` if you need it.
- **Health check** is exposed at `/up`.
- Served locally by Laravel Herd. Use the Boost `get-absolute-url` tool rather than guessing the host.

## Code standards

Non-negotiable for every PHP file written here. Where a rule is machine-checkable, the tool is the source of truth — don't argue with it.

### PSR-12 (style)

- Pint enforces PSR-12 with the `laravel` preset. Run `vendor/bin/pint --dirty --format agent` after editing any PHP file; never hand-format to fight it.
- What Pint won't catch, you own: `declare(strict_types=1);` is **not** in the Laravel preset — add it to every new class file, and keep it there.
- One class per file, filename matching the class, `StudlyCase` classes, `camelCase` methods, `SCREAMING_SNAKE_CASE` constants, `TitleCase` enum cases.

### Clean Code

- Names state intent: `isEligibleForRefund()`, not `check()`. Booleans read as predicates (`is`, `has`, `can`).
- Functions do one thing at one level of abstraction. A controller action that validates, computes, persists, and notifies is four things — extract three of them.
- Guard clauses over nested conditionals; return early. No `else` after a `return`.
- No flag parameters (`process($order, true)`) — split into two named methods.
- Comments explain *why*, never *what*. If a comment restates the code, delete it and rename the thing instead. Per the PHP rules below, prefer PHPDoc over inline comments.
- No dead code, no commented-out blocks, no speculative abstraction for a second use case that doesn't exist yet.

### SOLID

- **SRP** — Controllers coordinate HTTP only: resolve input, delegate, return a response. Business logic lives in Actions/Services; query logic in the model, a scope, or a query builder class. Models are persistence + relationships + casts, not a dumping ground.
- **OCP** — Extend behaviour by adding a class (a new Action, a new pipeline stage, a new listener), not by adding another branch to a growing `match`/`switch`.
- **LSP** — A subclass or contract implementation must not narrow accepted input or throw where the parent doesn't. Prefer composition over inheritance; the only base classes to extend are the framework's.
- **ISP** — Small, purpose-named interfaces (`SendsInvoices`), not a `ServiceInterface` with twelve methods. Don't add an interface until there is a real second implementation or a real test seam.
- **DIP** — Type-hint contracts, constructor-inject them (with promotion), and bind concretes in `AppServiceProvider`. Never `new` a dependency that has side effects; never reach for a facade inside a domain class where injection is possible.

### Laravel best practice

- **Do it the framework's way first.** Reach for a custom abstraction only after the built-in (Form Request, Policy, Resource, Job, Event, Notification, Scope, Cast, Rule) genuinely doesn't fit.
- **Validation** lives in Form Requests, not inline `$request->validate()` in the controller, once a rule set has more than a couple of fields or is reused.
- **Authorization** lives in Policies + `authorize()`/`can` middleware. No `if ($user->role === 'admin')` scattered through actions.
- **Never trust mass assignment.** Pass `$request->validated()`, not `$request->all()`. Keep `$fillable`/`$guarded` deliberate.
- **N+1 is a bug, not a nit.** Eager-load with `with()`/`load()`, use `withCount()`, and prefer `chunkById()`/`lazy()`/`cursor()` over `all()` for large sets. Never query inside a loop (including inside a Blade loop).
- **Push work to the database:** aggregates, `exists()`, `whereHas`, pagination. Don't pull collections into PHP to filter or count them.
- **Anything slow or third-party is a queued Job** — mail, webhooks, imports, image work. Make jobs idempotent and set `$tries`/`backoff`.
- **Wrap multi-write operations** in `DB::transaction()`, and dispatch side effects after commit (`afterCommit`), not inside the transaction.
- **Config over env:** call `env()` only inside `config/*.php`; everywhere else use `config()`, so config caching doesn't blank your values.
- **Routes** are named and reference controllers; generate URLs with `route()`. No closures with logic in `routes/web.php`.
- **APIs** return Eloquent API Resources, versioned, with the JSON exception rendering already wired in `bootstrap/app.php`.
- **Every behaviour ships with a Pest test** — feature tests by default, factories for data. See the Pest rules below.

### Design patterns

Use them where they reduce complexity; a pattern applied to a two-line problem is itself the problem.

- **Action / Single-purpose class** — the default home for a use case (`app/Actions/CreateInvoice.php`, one public `handle()`). Prefer this over a fat `Service` god-class.
- **Repository** — only when you need to swap or fake the persistence layer. Eloquent is already a data-access layer; wrapping it "for testability" alone is a net loss here.
- **Strategy** — for interchangeable algorithms (payment gateways, pricing rules): an interface plus implementations, selected by a factory or container binding.
- **Observer / Events & Listeners** — for decoupled side effects. Keep model observers to persistence concerns; use domain events for anything a reader would be surprised to find in a model hook.
- **Pipeline** — for ordered, composable steps (filters, request processing) instead of chained conditionals.
- **Factory / Builder** — for complex object assembly; model factories are the testing equivalent and are mandatory for test data.
- **DTOs** — typed, readonly objects to move data across boundaries rather than untyped arrays, once the array shape has more than a few keys.

## Agent configuration

- Everything inside the generated guidelines block below is **generated**, and mirrored verbatim into `AGENTS.md` — `composer update` runs `boost:update`, which rewrites both. Put project-specific guidance above that block (like this section) or it will be overwritten; mirror it into `AGENTS.md` if non-Claude agents need it too. Do not write the block's opening tag name in prose up here: `boost:update` matches on it and will truncate this section.
- Skills are duplicated into `.claude/skills/` and `.cursor/skills/` by Boost (`boost.json` lists which). Edit skills through Boost, not by hand in one copy.
- `.ai/rules/` holds committed, area-grouped rules recorded with the Boost `record-rule` tool. Read the ones matching the paths you touch before editing.

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application running on PHP 8.4. You are an expert with the Laravel ecosystem. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Always use `search-docs` before making code changes. Do not skip this step. It returns version-specific docs based on installed packages automatically.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists (settled decisions, non-obvious traps, standing constraints). Framework and package guidelines that only apply to specific paths (testing, frontend, components) also live there, under `.ai/rules/boost` — this is not just recorded decisions, it is load-bearing guidance you have not seen inline. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.
- Record durable rules with `record-rule` so the next agent or teammate inherits them instead of working them out again. Pass a `glob` (e.g. `app/Http/Controllers/**`), a short `title`, and a few-line `note`. Always use `record-rule`, never your native memory or notes tool — native memory is personal and session-scoped; only `.ai/rules` is shared with the team and persists in the repo.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== herd rules ===

# Laravel Herd

- The application is served by Laravel Herd at `https?://[kebab-case-project-dir].test`. Use the `get-absolute-url` tool to generate valid URLs. Never run commands to serve the site. It is always available.
- Use the `herd` CLI to manage services, PHP versions, and sites (e.g. `herd sites`, `herd services:start <service>`, `herd php:list`). Run `herd list` to discover all available commands.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== livewire/core rules ===

# Livewire

- Livewire allow to build dynamic, reactive interfaces in PHP without writing JavaScript.
- You can use Alpine.js for client-side interactions instead of JavaScript frameworks.
- Keep state server-side so the UI reflects it. Validate and authorize in actions as you would in HTTP requests.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

## Pest

- This project uses Pest for testing. Create tests: `php artisan make:test --pest {name}`.
- The `{name}` argument should not include the test suite directory. Use `php artisan make:test --pest SomeFeatureTest` instead of `php artisan make:test --pest Feature/SomeFeatureTest`.
- Run tests: `php artisan test --compact` or filter: `php artisan test --compact --filter=testName`.
- Do NOT delete tests without approval.

</laravel-boost-guidelines>

<!-- code-review-graph MCP tools -->
## MCP Tools: code-review-graph

**IMPORTANT: This project has a knowledge graph. ALWAYS use the
code-review-graph MCP tools BEFORE using Grep/Glob/Read to explore
the codebase.** The graph is faster, cheaper (fewer tokens), and gives
you structural context (callers, dependents, test coverage) that file
scanning cannot.

### When to use graph tools FIRST

- **Exploring code**: `semantic_search_nodes_tool` or `query_graph_tool` instead of Grep
- **Understanding impact**: `get_impact_radius_tool` instead of manually tracing imports
- **Code review**: `detect_changes_tool` + `get_review_context_tool` instead of reading entire files
- **Finding relationships**: `query_graph_tool` with callers_of/callees_of/imports_of/tests_for
- **Architecture questions**: `get_architecture_overview_tool` + `list_communities_tool`

Fall back to Grep/Glob/Read **only** when the graph doesn't cover what you need.

### Key Tools

| Tool | Use when |
| ------ | ---------- |
| `detect_changes_tool` | Reviewing code changes — gives risk-scored analysis |
| `get_review_context_tool` | Need source snippets for review — token-efficient |
| `get_impact_radius_tool` | Understanding blast radius of a change |
| `get_affected_flows_tool` | Finding which execution paths are impacted |
| `query_graph_tool` | Tracing callers, callees, imports, tests, dependencies |
| `semantic_search_nodes_tool` | Finding functions/classes by name or keyword |
| `get_architecture_overview_tool` | Understanding high-level codebase structure |
| `refactor_tool` | Planning renames, finding dead code |

### Workflow

1. The graph auto-updates on file changes (via hooks).
2. Use `detect_changes_tool` for code review.
3. Use `get_affected_flows_tool` to understand impact.
4. Use `query_graph_tool` pattern="tests_for" to check coverage.
