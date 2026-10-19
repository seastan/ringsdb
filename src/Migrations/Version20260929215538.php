<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * The foreign keys of the required associations become NOT NULL: the mappings declared nullable:
 * false on the associations, where Doctrine ignores it, instead of on their join columns.
 *
 * Before applying this migration, to make sure it won't break (every count must be 0, or NULL for
 * an empty table):
 *
 * SELECT 'fellowshipcomment', SUM(user_id IS NULL) + SUM(fellowship_id IS NULL) FROM fellowshipcomment UNION ALL
 * SELECT 'decksideslot', SUM(deck_id IS NULL) + SUM(card_id IS NULL) FROM decksideslot UNION ALL
 * SELECT 'card', SUM(type_id IS NULL) + SUM(sphere_id IS NULL) FROM card UNION ALL
 * SELECT 'decklist', SUM(user_id IS NULL) FROM decklist UNION ALL
 * SELECT 'review', SUM(card_id IS NULL) + SUM(user_id IS NULL) FROM review UNION ALL
 * SELECT 'questlog_comment', SUM(user_id IS NULL) + SUM(questlog_id IS NULL) FROM questlog_comment UNION ALL
 * SELECT 'fellowship', SUM(user_id IS NULL) FROM fellowship UNION ALL
 * SELECT 'questlog_deck', SUM(questlog_id IS NULL) FROM questlog_deck UNION ALL
 * SELECT 'decklistsideslot', SUM(decklist_id IS NULL) + SUM(card_id IS NULL) FROM decklistsideslot UNION ALL
 * SELECT 'pack', SUM(cycle_id IS NULL) FROM pack UNION ALL
 * SELECT 'comment', SUM(user_id IS NULL) + SUM(decklist_id IS NULL) FROM comment UNION ALL
 * SELECT 'fellowship_deck', SUM(fellowship_id IS NULL) + SUM(deck_id IS NULL) FROM fellowship_deck UNION ALL
 * SELECT 'deck', SUM(user_id IS NULL) FROM deck UNION ALL
 * SELECT 'reviewcomment', SUM(user_id IS NULL) + SUM(review_id IS NULL) FROM reviewcomment UNION ALL
 * SELECT 'fellowship_decklist', SUM(fellowship_id IS NULL) + SUM(decklist_id IS NULL) FROM fellowship_decklist UNION ALL
 * SELECT 'questlog', SUM(user_id IS NULL) + SUM(scenario_id IS NULL) FROM questlog UNION ALL
 * SELECT 'decklistslot', SUM(decklist_id IS NULL) + SUM(card_id IS NULL) FROM decklistslot UNION ALL
 * SELECT 'deckchange', SUM(deck_id IS NULL) FROM deckchange UNION ALL
 * SELECT 'deckslot', SUM(deck_id IS NULL) + SUM(card_id IS NULL) FROM deckslot;
 */
final class Version20260929215538 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Make the foreign keys of the required associations NOT NULL';
    }

    public function up(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE fellowshipcomment CHANGE user_id user_id INT NOT NULL, CHANGE fellowship_id fellowship_id INT NOT NULL');
        $this->addSql('ALTER TABLE decksideslot CHANGE deck_id deck_id INT NOT NULL, CHANGE card_id card_id INT NOT NULL');
        $this->addSql('ALTER TABLE card CHANGE type_id type_id INT NOT NULL, CHANGE sphere_id sphere_id INT NOT NULL');
        $this->addSql('ALTER TABLE decklist CHANGE user_id user_id INT NOT NULL');
        $this->addSql('ALTER TABLE review CHANGE card_id card_id INT NOT NULL, CHANGE user_id user_id INT NOT NULL');
        $this->addSql('ALTER TABLE questlog_comment CHANGE user_id user_id INT NOT NULL, CHANGE questlog_id questlog_id INT NOT NULL');
        $this->addSql('ALTER TABLE fellowship CHANGE user_id user_id INT NOT NULL');
        $this->addSql('ALTER TABLE questlog_deck CHANGE questlog_id questlog_id INT NOT NULL');
        $this->addSql('ALTER TABLE decklistsideslot CHANGE decklist_id decklist_id INT NOT NULL, CHANGE card_id card_id INT NOT NULL');
        $this->addSql('ALTER TABLE pack CHANGE cycle_id cycle_id INT NOT NULL');
        $this->addSql('ALTER TABLE comment CHANGE user_id user_id INT NOT NULL, CHANGE decklist_id decklist_id INT NOT NULL');
        $this->addSql('ALTER TABLE fellowship_deck CHANGE fellowship_id fellowship_id INT NOT NULL, CHANGE deck_id deck_id INT NOT NULL');
        $this->addSql('ALTER TABLE deck CHANGE user_id user_id INT NOT NULL');
        $this->addSql('ALTER TABLE reviewcomment CHANGE user_id user_id INT NOT NULL, CHANGE review_id review_id INT NOT NULL');
        $this->addSql('ALTER TABLE fellowship_decklist CHANGE fellowship_id fellowship_id INT NOT NULL, CHANGE decklist_id decklist_id INT NOT NULL');
        $this->addSql('ALTER TABLE questlog CHANGE user_id user_id INT NOT NULL, CHANGE scenario_id scenario_id INT NOT NULL');
        $this->addSql('ALTER TABLE decklistslot CHANGE decklist_id decklist_id INT NOT NULL, CHANGE card_id card_id INT NOT NULL');
        $this->addSql('ALTER TABLE deckchange CHANGE deck_id deck_id INT NOT NULL');
        $this->addSql('ALTER TABLE deckslot CHANGE deck_id deck_id INT NOT NULL, CHANGE card_id card_id INT NOT NULL');
    }

    public function down(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE card CHANGE type_id type_id INT DEFAULT NULL, CHANGE sphere_id sphere_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE comment CHANGE user_id user_id INT DEFAULT NULL, CHANGE decklist_id decklist_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE deck CHANGE user_id user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE deckchange CHANGE deck_id deck_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE decklist CHANGE user_id user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE decklistsideslot CHANGE decklist_id decklist_id INT DEFAULT NULL, CHANGE card_id card_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE decklistslot CHANGE decklist_id decklist_id INT DEFAULT NULL, CHANGE card_id card_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE decksideslot CHANGE deck_id deck_id INT DEFAULT NULL, CHANGE card_id card_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE deckslot CHANGE deck_id deck_id INT DEFAULT NULL, CHANGE card_id card_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE fellowship CHANGE user_id user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE fellowship_deck CHANGE fellowship_id fellowship_id INT DEFAULT NULL, CHANGE deck_id deck_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE fellowship_decklist CHANGE fellowship_id fellowship_id INT DEFAULT NULL, CHANGE decklist_id decklist_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE fellowshipcomment CHANGE user_id user_id INT DEFAULT NULL, CHANGE fellowship_id fellowship_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE pack CHANGE cycle_id cycle_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE questlog CHANGE user_id user_id INT DEFAULT NULL, CHANGE scenario_id scenario_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE questlog_comment CHANGE user_id user_id INT DEFAULT NULL, CHANGE questlog_id questlog_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE questlog_deck CHANGE questlog_id questlog_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE review CHANGE card_id card_id INT DEFAULT NULL, CHANGE user_id user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE reviewcomment CHANGE user_id user_id INT DEFAULT NULL, CHANGE review_id review_id INT DEFAULT NULL');
    }
}
