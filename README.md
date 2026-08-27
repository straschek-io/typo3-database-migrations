# database_migrations

Versioned database content migrations via doctrine/migrations, wired to the
TYPO3 default connection (ConnectionPool). Ships deployment recipes for
Deployer and TYPO3 Surf so pending migrations run automatically on every
deployment.

## What this is — and what it is not

This extension does **not** do schema or TCA migrations. Tables and columns
stay entirely with TYPO3's own mechanisms (`ext_tables.sql`,
`database:updateschema`, core upgrade wizards).

It is for the changes those mechanisms cannot express: versioned **data**
migrations that belong to a specific code change and must run exactly once per
environment, in guaranteed order, on every stage. Realistic examples:

- A relaunch switches list plugins to a new template layout. The deploy that
  ships the new Fluid template also has to flip `settings.templateLayout` in
  the FlexForm of every affected content element — on staging, production and
  every developer machine, without manual clicks (this is what the bundled
  example migration does).
- A refactoring renames a CType or moves a plugin option to another field. All
  existing tt_content rows must be rewritten in the same release that removes
  the old rendering definition — otherwise the elements silently disappear
  from the frontend.
- A column is split (one address field into street, zip and city). The new
  columns come from `ext_tables.sql` as usual, but the one-time backfill that
  parses the existing rows into them is a migration — tracked per environment
  and visible in `migrations:status`.

Unlike upgrade wizards, migrations are plain project-versioned PHP classes that
run automatically during deployment — no Install Tool interaction, a defined
execution order, and a rollback path via `down()`.

## Installation

```
composer require straschek-io/typo3-database-migrations
```

## Commands

```
vendor/bin/typo3 migrations:status
vendor/bin/typo3 migrations:list
vendor/bin/typo3 migrations:migrate
vendor/bin/typo3 migrations:version
vendor/bin/typo3 migrations:generate
```

In deployments: `migrations:migrate --no-interaction --allow-no-migration`

## Rollback

`migrations:migrate` can also migrate downwards — `down()` of the affected
migrations is executed:

```
vendor/bin/typo3 migrations:migrate prev --no-interaction    # one version back
vendor/bin/typo3 migrations:migrate first --no-interaction   # all the way back
vendor/bin/typo3 migrations:migrate 'DoctrineMigrations\VersionYYYYMMDDHHMMSS' --no-interaction
```

A complete release rollback consists of two steps that belong together:
rolling back the code release with the deployment tool (`dep rollback` with
Deployer; with Surf, switch the release symlink back to the previous release)
**and** `migrations:migrate prev` on the target system — otherwise old code
runs against migrated data. `down()` is best effort for content migrations (it
cannot reconstruct editorial changes made in the meantime); the robust safety
net remains a database dump before the deployment.

## Writing migrations

`migrations:generate` creates `migrations/Version<timestamp>.php` in the
project root (namespace `DoctrineMigrations`, no autoload entry needed).
Create the directory once and keep it in git.

Rules:

- migrations must be idempotent — guard every change with a SELECT first
- work directly on `$this->connection` instead of `addSql()`
- never change an executed migration, write a new one instead
- one migration per logical change

Example: `Documentation/Examples/ContentMigrationExample.php`

## Deployment recipes

**Deployer** (in `deploy.php`):

```php
require __DIR__ . '/vendor/straschek-io/typo3-database-migrations/Resources/Private/Deployment/DeployerTasks.php';
// add 'typo3:migrations:migrate' to the deploy task list,
// after typo3:extension:setup and before the cache tasks
```

**TYPO3 Surf** (in the deployment definition, requires typo3/surf in the project):

```php
require __DIR__ . '/vendor/straschek-io/typo3-database-migrations/Resources/Private/Deployment/SurfTasks.php';
\StraschekIo\DatabaseMigrations\Deployment\registerDatabaseMigrationsTask($deployment);
```
