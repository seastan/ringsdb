<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Drops sphere.octgnid: used by nothing but its own admin form and pages (the OCTGN exports and
 * imports use the octgnid of the card printings), and NULL for every sphere. down() recreates it
 * empty.
 */
final class Version20260930102154 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop the unused sphere.octgnid';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), "Migration can only be executed safely on 'mysql'.");

        $this->addSql('ALTER TABLE sphere DROP octgnid');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), "Migration can only be executed safely on 'mysql'.");

        $this->addSql('ALTER TABLE sphere ADD octgnid VARCHAR(255) CHARACTER SET utf8mb3 DEFAULT NULL COLLATE `utf8mb3_unicode_ci`');
    }
}
