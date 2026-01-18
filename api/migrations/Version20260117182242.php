<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260117182242 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user ADD spotify_id VARCHAR(64) DEFAULT NULL, ADD spotify_access_token LONGTEXT DEFAULT NULL, ADD spotify_refresh_token LONGTEXT DEFAULT NULL, ADD spotify_access_token_expires_at DATETIME DEFAULT NULL, ADD spotify_display_name VARCHAR(120) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_USER_SPOTIFY_ID ON user (spotify_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_USER_SPOTIFY_ID ON user');
        $this->addSql('ALTER TABLE user DROP spotify_id, DROP spotify_access_token, DROP spotify_refresh_token, DROP spotify_access_token_expires_at, DROP spotify_display_name');
    }
}
