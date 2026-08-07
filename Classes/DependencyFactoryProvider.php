<?php

declare(strict_types=1);

namespace StraschekIo\DatabaseMigrations;

use Doctrine\Migrations\Configuration\Connection\ExistingConnection;
use Doctrine\Migrations\Configuration\Migration\ConfigurationArray;
use Doctrine\Migrations\DependencyFactory;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\ConnectionPool;

/**
 * Wires doctrine/migrations to the TYPO3 default database connection. TYPO3's
 * Connection extends Doctrine\DBAL\Connection, so ExistingConnection reuses it
 * as-is — credentials, port and charset come from the regular TYPO3
 * configuration, no second connection setup.
 *
 * Migrations live in <projectRoot>/migrations by convention (not
 * configurable) under the DoctrineMigrations namespace. Doctrine's finder
 * requires the files itself, so the project needs no autoload entry for them.
 */
final class DependencyFactoryProvider
{
    public function __construct(private readonly ConnectionPool $connectionPool) {}

    public function provide(?LoggerInterface $logger = null): DependencyFactory
    {
        $configuration = new ConfigurationArray([
            'migrations_paths' => [
                'DoctrineMigrations' => Environment::getProjectPath() . '/migrations',
            ],
            'custom_template' => dirname(__DIR__) . '/Resources/Private/Templates/Migration.tpl',
            'table_storage' => [
                'table_name' => 'doctrine_migration_versions',
            ],
            // MySQL DDL is not transactional and content migrations are
            // idempotent anyway — keep transaction handling off.
            'transactional' => false,
            'all_or_nothing' => false,
        ]);

        return DependencyFactory::fromConnection(
            $configuration,
            new ExistingConnection(
                $this->connectionPool->getConnectionByName(ConnectionPool::DEFAULT_CONNECTION_NAME)
            ),
            $logger
        );
    }
}
