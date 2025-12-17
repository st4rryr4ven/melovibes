<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251217125643 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE artist (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE music (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, genre JSON DEFAULT NULL, picture VARCHAR(255) DEFAULT NULL, link VARCHAR(255) DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE music_artist (music_id INT NOT NULL, artist_id INT NOT NULL, INDEX IDX_9481557E399BBB13 (music_id), INDEX IDX_9481557EB7970CF8 (artist_id), PRIMARY KEY (music_id, artist_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE music_artist ADD CONSTRAINT FK_9481557E399BBB13 FOREIGN KEY (music_id) REFERENCES music (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE music_artist ADD CONSTRAINT FK_9481557EB7970CF8 FOREIGN KEY (artist_id) REFERENCES artist (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE music_artist DROP FOREIGN KEY FK_9481557E399BBB13');
        $this->addSql('ALTER TABLE music_artist DROP FOREIGN KEY FK_9481557EB7970CF8');
        $this->addSql('DROP TABLE artist');
        $this->addSql('DROP TABLE music');
        $this->addSql('DROP TABLE music_artist');
    }
}
