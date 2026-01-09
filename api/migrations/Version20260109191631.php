<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260109191631 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE user_music (user_id INT NOT NULL, music_id INT NOT NULL, INDEX IDX_2F90D912A76ED395 (user_id), INDEX IDX_2F90D912399BBB13 (music_id), PRIMARY KEY (user_id, music_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE user_music ADD CONSTRAINT FK_2F90D912A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_music ADD CONSTRAINT FK_2F90D912399BBB13 FOREIGN KEY (music_id) REFERENCES music (id) ON DELETE CASCADE');
        $this->addSql('DROP INDEX UNIQ_SPOTIFY_ID ON artist');
        $this->addSql('ALTER TABLE artist DROP spotify_id');
        $this->addSql('DROP INDEX UNIQ_SPOTIFY_ID ON music');
        $this->addSql('ALTER TABLE music DROP spotify_id, DROP import_source, DROP imported_at');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user_music DROP FOREIGN KEY FK_2F90D912A76ED395');
        $this->addSql('ALTER TABLE user_music DROP FOREIGN KEY FK_2F90D912399BBB13');
        $this->addSql('DROP TABLE user_music');
        $this->addSql('ALTER TABLE artist ADD spotify_id VARCHAR(64) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_SPOTIFY_ID ON artist (spotify_id)');
        $this->addSql('ALTER TABLE music ADD spotify_id VARCHAR(64) DEFAULT NULL, ADD import_source VARCHAR(64) DEFAULT NULL, ADD imported_at DATETIME DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_SPOTIFY_ID ON music (spotify_id)');
    }
}
