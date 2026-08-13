<?php

/**
 * TYPO3 Surf task registration for EXT:database_migrations.
 *
 * Usage in a Surf deployment definition (requires typo3/surf in the project):
 *
 *   require __DIR__ . '/vendor/straschek-io/typo3-database-migrations/Resources/Private/Deployment/SurfTasks.php';
 *   \StraschekIo\DatabaseMigrations\Deployment\registerDatabaseMigrationsTask($deployment);
 *
 * The task runs after the TYPO3 Surf SetUpExtensionsTask (extension:setup) in
 * the "migrate" stage; pass another $afterTask if your workflow differs.
 */

namespace StraschekIo\DatabaseMigrations\Deployment;

use TYPO3\Surf\Domain\Model\Deployment;

function registerDatabaseMigrationsTask(
    Deployment $deployment,
    string $afterTask = 'TYPO3\\Surf\\Task\\TYPO3\\CMS\\SetUpExtensionsTask',
    string $phpBinary = 'php'
): void {
    $deployment->onInitialize(function () use ($deployment, $afterTask, $phpBinary): void {
        $workflow = $deployment->getWorkflow();
        $workflow->defineTask(
            'StraschekIo\\DatabaseMigrations\\MigrateTask',
            \TYPO3\Surf\Task\ShellTask::class,
            [
                'command' => 'cd {releasePath} && '
                    . $phpBinary . ' vendor/bin/typo3 migrations:migrate --no-interaction --allow-no-migration',
            ]
        );
        $workflow->afterTask($afterTask, 'StraschekIo\\DatabaseMigrations\\MigrateTask');
    });
}
