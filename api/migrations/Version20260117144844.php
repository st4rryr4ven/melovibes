<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260117144844 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX unique_user_music_review ON review');
        $this->addSql('ALTER TABLE review ADD melody_rating INT DEFAULT NULL, ADD lyrics_rating INT DEFAULT NULL, ADD vocals_rating INT DEFAULT NULL, ADD impact_rating INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE review DROP melody_rating, DROP lyrics_rating, DROP vocals_rating, DROP impact_rating');
        $this->addSql('CREATE UNIQUE INDEX unique_user_music_review ON review (author_id, music_id)');
    }
}
