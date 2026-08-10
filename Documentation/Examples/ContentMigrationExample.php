<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Example content migration: editorial data that belongs to a code deploy —
 * here a template relaunch that switches list plugins to a new layout and adds
 * a teaser element to a landing page. Works directly on the connection
 * (select-then-update does not fit addSql), therefore idempotent.
 */
final class Version20260812203000 extends AbstractMigration
{
    private const LANDING_PAGE_UID = 42;
    private const LIST_PLUGIN_CTYPE = 'example_newslist';
    private const TEASER_CTYPE = 'example_eventteaser';
    private const SIGNUP_CTYPE = 'example_signupform';
    private const TEASER_SORTING_FALLBACK = 256;

    public function getDescription(): string
    {
        return 'Template relaunch: switch news list plugins from template layout 101 '
            . 'to 102 (accordion) and add the event teaser element to the landing page';
    }

    public function up(Schema $schema): void
    {
        $this->switchListTemplateLayout();
        $this->addTeaserElementToLandingPage();
    }

    public function down(Schema $schema): void
    {
        $this->switchListTemplateLayoutBack();
        $this->removeTeaserElementFromLandingPage();
    }

    public function isTransactional(): bool
    {
        return false;
    }

    private function switchListTemplateLayout(): void
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT uid, pi_flexform FROM tt_content'
            . ' WHERE CType = ? AND deleted = 0 AND pi_flexform LIKE ?',
            [self::LIST_PLUGIN_CTYPE, '%settings.templateLayout%']
        );

        foreach ($rows as $row) {
            $updated = (string)preg_replace(
                '/(<field index="settings\.templateLayout">\s*<value index="vDEF">)101(<\/value>)/',
                '${1}102${2}',
                (string)$row['pi_flexform']
            );
            if ($updated !== (string)$row['pi_flexform']) {
                $this->connection->update('tt_content', ['pi_flexform' => $updated], ['uid' => (int)$row['uid']]);
            }
        }
    }

    private function switchListTemplateLayoutBack(): void
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT uid, pi_flexform FROM tt_content'
            . ' WHERE CType = ? AND deleted = 0 AND pi_flexform LIKE ?',
            [self::LIST_PLUGIN_CTYPE, '%settings.templateLayout%']
        );

        foreach ($rows as $row) {
            $updated = (string)preg_replace(
                '/(<field index="settings\.templateLayout">\s*<value index="vDEF">)102(<\/value>)/',
                '${1}101${2}',
                (string)$row['pi_flexform']
            );
            if ($updated !== (string)$row['pi_flexform']) {
                $this->connection->update('tt_content', ['pi_flexform' => $updated], ['uid' => (int)$row['uid']]);
            }
        }
    }

    /**
     * Soft delete so editorial changes made to the element in the meantime
     * survive in the recycler instead of being destroyed.
     */
    private function removeTeaserElementFromLandingPage(): void
    {
        $this->connection->executeStatement(
            'UPDATE tt_content SET deleted = 1, tstamp = ? WHERE pid = ? AND CType = ? AND deleted = 0',
            [time(), self::LANDING_PAGE_UID, self::TEASER_CTYPE]
        );
    }

    private function addTeaserElementToLandingPage(): void
    {
        $exists = (int)$this->connection->fetchOne(
            'SELECT COUNT(uid) FROM tt_content WHERE pid = ? AND CType = ? AND deleted = 0',
            [self::LANDING_PAGE_UID, self::TEASER_CTYPE]
        ) > 0;

        if ($exists) {
            return;
        }

        $signupSorting = $this->connection->fetchOne(
            'SELECT sorting FROM tt_content WHERE pid = ? AND CType = ? AND deleted = 0',
            [self::LANDING_PAGE_UID, self::SIGNUP_CTYPE]
        );

        $this->connection->insert('tt_content', [
            'pid' => self::LANDING_PAGE_UID,
            'CType' => self::TEASER_CTYPE,
            'header' => 'Upcoming events',
            'colPos' => 0,
            'sorting' => $signupSorting === false ? self::TEASER_SORTING_FALLBACK : (int)$signupSorting + 24,
            'crdate' => time(),
            'tstamp' => time(),
        ]);
    }
}
