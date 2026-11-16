# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Drall is a PHP CLI tool for running Drush commands on multi-site Drupal installations. It uses a placeholder system (`@@dir`, `@@key`, `@@ukey`, `@@site`) to execute commands across multiple sites with parallel execution support.

## Development Commands

```bash
# Manage the Docker dev environment (run on the host)
make build     # Build images; the next "make start" recreates containers
make start     # Create/start containers
make stop      # Stop containers, keeping provisioned state
make restart   # Restart containers

# Enter Docker dev environment (required for most commands)
make ssh

# Run linter (Drupal coding standard via PHPCS)
make lint
# or directly: composer run lint

# Run tests with code coverage
make test
# or directly: composer run test

# View coverage report
make coverage-report/text   # Text output
make coverage-report/html   # Opens HTML report

# Full environment setup
make provision

# Provision the fixture sites with a specific Drush major version
DRUSH_VERSION=12 make provision
```

Except for `make build`, `start`, `stop`, `restart` and `ssh`, run these
inside the container.

### Development Environment Gotchas

- `.docker/main/drupal/composer.json` and `Makefile` are bind-mounted into
  the container as single files (see `docker-compose.yml`). Edit them in
  place. Tools that replace the file (e.g. `sed -i`, `perl -i`) and branch
  switches break the container's view of it; `make restart` fixes that.
- `make provision` (via `provision/drall`) deletes and regenerates
  `/opt/drall/composer.lock` and `vendor/`, which is the project checkout.

## Architecture

**Entry Point:** `bin/drall` → `src/Drall.php` (Symfony Console Application)

**Commands** (`src/Command/`):
- `BaseCommand` - Abstract base with common options (`--group`, `--filter`, `--offset`, `--limit`)
- `ExecCommand` - Main command for running commands across sites; implements
  `SignalableCommandInterface` for graceful SIGINT handling
- `SiteDirectoriesCommand`, `SiteKeysCommand`, `SiteAliasesCommand` - List sites
- `StopCommand` - Stops running `exec` commands (see `StoppableCommandTrait`)

**Core Abstractions:**
- `Placeholder` enum (`src/Model/Placeholder.php`) - Type-safe placeholder values
- `SiteDetector` service - Discovers sites via `sites.php` or Drush aliases
- `SiteDetectorOptions` - Encapsulates filtering/grouping options from input

**Service Injection Pattern:**
Traits in `src/Trait/` provide service access (e.g., `SiteDetectorAwareTrait`):
```php
use SiteDetectorAwareTrait;
// Then use: $this->siteDetector()
```

**Parallel Execution:** Uses `amphp/pipeline` for worker-based parallelization

**Batches** (`src/Batch/`): `BatchInterface` with `FileBatch` and
`MemoryBatch` implementations track per-site progress; `FileBatch` backs
`exec --batch-file` for resuming interrupted runs.

**Drush dependency:** Drall's only direct use of Drush is
`Drush\SiteAlias\SiteAliasFileLoader` (in `SiteDetector`). At runtime, Drall
is installed inside a Drupal project and uses that project's `vendor/`
(Drush, Symfony Console, etc.), not its own.

## Testing

Tests extend `Drall\TestCase` which provides:
- `createTempFile()` - Create temp files for tests
- `createDrupalFinderStub()` - Mock DrupalFinder
- `assertOutputEquals()`, `assertOutputContainsString()` - Normalized output assertions
- Constants: `PATH_PROJECT`, `PATH_FIXTURES`, `PATH_DRUPAL`, etc.

All tests require a `@covers` annotation (`forceCoversAnnotation` in
`phpunit.xml`).

Integration tests run Drall against fixture sites built by `make provision`:
`/opt/drupal` (multi-site), `/opt/empty-drupal` and `/opt/no-drupal`. Their
`composer.json` files live in `.docker/main/`. The fixtures' `conflict`
on `consolidation/robo` 5.2.1–5.2.3 is intentional: those releases call
`Application::addCommand()`, which doesn't exist in Symfony Console 6.4
(Drupal 10), so Drush crashes. Remove it once a fixed Robo is released.

CI (`.github/workflows/validation.yml`) runs a PHP × Drush matrix; the Drush
version is passed to `make provision` via `DRUSH_VERSION`.

## Code Style

- Drupal coding standard (PHPCS)
- Max line length: 120 characters (relaxed in tests)
- Class/function/variable doc comments not required
- New test methods use `snake_case` (e.g., `test_it_works`); older tests use
  camelCase

## Commit Convention

- Max subject width: 60 characters
- Max body width: 72 characters
- No trailing period on subject
- GrumPHP validates on commit (runs via Docker)