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
vendor/bin/typo3 migrations:status    # overview: available/executed migrations
vendor/bin/typo3 migrations:list      # list all migrations with their status
vendor/bin/typo3 migrations:migrate   # execute pending migrations
vendor/bin/typo3 migrations:version   # manually mark a version as (not) executed
vendor/bin/typo3 migrations:generate  # create a new boilerplate migration
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

## Creating a new migration

```
vendor/bin/typo3 migrations:generate
```

creates `migrations/Version<YmdHis>.php` (current UTC timestamp) in the
**project root** from the bundled boilerplate template
(`Resources/Private/Templates/Migration.tpl`): `getDescription()` stub, empty
`up()`/`down()` with the idempotency reminder in place, and `isTransactional()`
already disabled. Fill in the description and the guarded data changes — see
`Documentation/Examples/ContentMigrationExample.php` for a filled-in example.

Migrations live in `<projectRoot>/migrations` by convention — fixed, not
configurable — so they are versioned with the project instead of being wiped
with `vendor/` on the next install. Create the directory once (with a
`.gitkeep`) and commit it. The classes use the `DoctrineMigrations` namespace;
no composer autoload entry is needed, doctrine's finder loads the files
itself.

## Ground rules

- One migration per logical change; `getDescription()` names the ticket.
- **Content migrations are always idempotent** (SELECT-guarded): deployments
  repeat, and manually prepared environments must not be migrated twice.
- Select-then-update logic runs directly on `$this->connection`
  (`fetchAllAssociative`, `fetchOne`, `update`, `insert`) instead of `addSql()`.
  The warning "migration did not result in any SQL statements" is expected then.
- Never modify an executed migration afterwards — corrections are new
  migrations.
- Implement `down()` when the change is cleanly reversible (enables real
  rollback); otherwise `throwIrreversibleMigrationException`.
- Schema changes stay with TYPO3 (`ext_tables.sql` + `database:updateschema`);
  this extension is for content/data only.
