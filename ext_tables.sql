#
# doctrine/migrations creates and owns this table (TableMetadataStorage);
# declaring it here makes TYPO3 treat it as managed, so the Install Tool
# Database Compare never offers to drop it. Column definitions mirror what
# doctrine/migrations 3.x creates — keep them in sync.
#
CREATE TABLE doctrine_migration_versions (
	version varchar(191) NOT NULL,
	executed_at datetime DEFAULT NULL,
	execution_time int(11) DEFAULT NULL,
	PRIMARY KEY (version)
);
