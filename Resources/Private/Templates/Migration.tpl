<?php

declare(strict_types=1);

namespace <namespace>;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * TODO Describe which code change this data migration belongs to.
 */
final class <className> extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // Keep it idempotent: guard every change with a SELECT first.
        <up>
    }

    public function down(Schema $schema): void
    {
        // Implement when cleanly reversible, otherwise:
        // $this->throwIrreversibleMigrationException();
        <down>
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
