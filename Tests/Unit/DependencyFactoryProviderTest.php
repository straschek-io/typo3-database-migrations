<?php

declare(strict_types=1);

namespace StraschekIo\DatabaseMigrations\Tests\Unit;

use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Metadata\Storage\TableMetadataStorageConfiguration;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use StraschekIo\DatabaseMigrations\DependencyFactoryProvider;
use TYPO3\CMS\Core\Core\ApplicationContext;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;

final class DependencyFactoryProviderTest extends TestCase
{
    private const PROJECT_PATH = '/project';

    private Connection&MockObject $connection;
    private DependencyFactoryProvider $subject;

    protected function setUp(): void
    {
        // create() resolves the migrations path from the TYPO3 Environment;
        // initialize it with a fixed project path. setUp() overwrites the
        // static state per test, so no tearDown reset is needed.
        Environment::initialize(
            new ApplicationContext('Testing'),
            true,
            true,
            self::PROJECT_PATH,
            self::PROJECT_PATH . '/public',
            self::PROJECT_PATH . '/var',
            self::PROJECT_PATH . '/config',
            self::PROJECT_PATH . '/vendor/bin/typo3',
            'UNIX'
        );

        $this->connection = $this->createMock(Connection::class);
        $connectionPool = $this->createMock(ConnectionPool::class);
        $connectionPool->method('getConnectionByName')
            ->with(ConnectionPool::DEFAULT_CONNECTION_NAME)
            ->willReturn($this->connection);

        $this->subject = new DependencyFactoryProvider($connectionPool);
    }

    public function testProvideConfiguresMigrationsPathInsideProjectRoot(): void
    {
        self::assertSame(
            ['DoctrineMigrations' => self::PROJECT_PATH . '/migrations'],
            $this->subject->provide()->getConfiguration()->getMigrationDirectories()
        );
    }

    public function testProvideConfiguresBoilerplateTemplateInsideExtensionDirectory(): void
    {
        $template = $this->subject->provide()->getConfiguration()->getCustomTemplate();

        self::assertSame(
            dirname(__DIR__, 2) . '/Resources/Private/Templates/Migration.tpl',
            $template
        );
        self::assertFileExists((string)$template);
    }

    public function testProvideConfiguresDoctrineVersionTable(): void
    {
        $storageConfiguration = $this->subject->provide()->getConfiguration()->getMetadataStorageConfiguration();

        self::assertInstanceOf(TableMetadataStorageConfiguration::class, $storageConfiguration);
        self::assertSame('doctrine_migration_versions', $storageConfiguration->getTableName());
    }

    public function testProvideDisablesTransactionalExecution(): void
    {
        self::assertFalse($this->subject->provide()->getConfiguration()->isTransactional());
    }

    public function testProvideDisablesAllOrNothing(): void
    {
        self::assertFalse($this->subject->provide()->getConfiguration()->isAllOrNothing());
    }

    public function testProvideReusesTypo3DefaultConnection(): void
    {
        self::assertSame($this->connection, $this->subject->provide()->getConnection());
    }

    public function testProvidePassesLoggerThrough(): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        self::assertSame($logger, $this->subject->provide($logger)->getLogger());
    }

    public function testProvideDefaultsToNullLogger(): void
    {
        self::assertInstanceOf(NullLogger::class, $this->subject->provide()->getLogger());
    }

    public function testProvideReturnsDependencyFactory(): void
    {
        self::assertInstanceOf(DependencyFactory::class, $this->subject->provide());
    }
}
