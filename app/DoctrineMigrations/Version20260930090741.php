<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * card.deck_limit becomes NOT NULL, with 3 as its default (Card::setDeckLimit(null) stores 3).
 *
 * Before applying this migration, to make sure it won't break (must be 0):
 *
 * SELECT COUNT(*) FROM card WHERE deck_limit IS NULL;
 */
final class Version20260930090741 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Make card.deck_limit NOT NULL, 3 by default';
    }

    public function up(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE card CHANGE deck_limit deck_limit SMALLINT DEFAULT 3 NOT NULL');
    }

    public function down(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE card CHANGE deck_limit deck_limit SMALLINT DEFAULT NULL');
    }
}
