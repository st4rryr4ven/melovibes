<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260110113823 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE artist RENAME INDEX uniq_1599687a905fc5c TO UNIQ_SPOTIFY_ID');
        $this->addSql('ALTER TABLE music RENAME INDEX uniq_cd52224aa905fc5c TO UNIQ_SPOTIFY_ID');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE artist RENAME INDEX uniq_spotify_id TO UNIQ_1599687A905FC5C');
        $this->addSql('ALTER TABLE music RENAME INDEX uniq_spotify_id TO UNIQ_CD52224AA905FC5C');
    }
}
