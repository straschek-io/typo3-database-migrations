# database_migrations

Versioned database content migrations for TYPO3 via doctrine/migrations. Uses
the TYPO3 default connection, no separate database configuration needed.

Schema changes stay with TYPO3 (`ext_tables.sql` / `database:updateschema`) —
this extension is for data: content that has to change together with a code
deploy (template switches, CType renames, backfills).

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

## Deployment

Deployer: require `Resources/Private/Deployment/DeployerTasks.php` in your
`deploy.php` and add `typo3:migrations:migrate` to the deploy task list, after
`typo3:extension:setup` and before the cache tasks.
