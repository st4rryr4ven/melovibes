<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260109131701 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE artist ADD spotify_id VARCHAR(64) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1599687A905FC5C ON artist (spotify_id)');
        $this->addSql('ALTER TABLE music ADD spotify_id VARCHAR(64) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_CD52224AA905FC5C ON music (spotify_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_1599687A905FC5C ON artist');
        $this->addSql('ALTER TABLE artist DROP spotify_id');
        $this->addSql('DROP INDEX UNIQ_CD52224AA905FC5C ON music');
        $this->addSql('ALTER TABLE music DROP spotify_id');
    }
}
