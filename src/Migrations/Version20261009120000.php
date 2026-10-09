<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Indexes date_creation on the comment and review tables: the home page reads the most recent rows
 * of each.
 */
final class Version20261009120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Index date_creation on comment, fellowshipcomment, reviewcomment and review';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), "Migration can only be executed safely on 'mysql'.");

        $this->addSql('CREATE INDEX idx_comment_date_creation ON comment (date_creation)');
        $this->addSql('CREATE INDEX idx_fellowshipcomment_date_creation ON fellowshipcomment (date_creation)');
        $this->addSql('CREATE INDEX idx_reviewcomment_date_creation ON reviewcomment (date_creation)');
        $this->addSql('CREATE INDEX idx_review_date_creation ON review (date_creation)');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), "Migration can only be executed safely on 'mysql'.");

        $this->addSql('DROP INDEX idx_comment_date_creation ON comment');
        $this->addSql('DROP INDEX idx_fellowshipcomment_date_creation ON fellowshipcomment');
        $this->addSql('DROP INDEX idx_reviewcomment_date_creation ON reviewcomment');
        $this->addSql('DROP INDEX idx_review_date_creation ON review');
    }
}
