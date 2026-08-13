<?php

/**
 * Deployer recipe for EXT:database_migrations.
 *
 * Usage in deploy.php:
 *
 *   require __DIR__ . '/vendor/straschek-io/typo3-database-migrations/Resources/Private/Deployment/DeployerTasks.php';
 *
 * and add 'typo3:migrations:migrate' to the deploy task list, after the
 * schema tasks (typo3:database:updateschema / typo3:extension:setup) and
 * before the cache tasks.
 */

namespace Deployer;

task('typo3:migrations:migrate', function () {
    run('{{bin/php}} {{release_path}}/vendor/bin/typo3 migrations:migrate --no-interaction --allow-no-migration');
})->desc('Run doctrine content/data migrations');
