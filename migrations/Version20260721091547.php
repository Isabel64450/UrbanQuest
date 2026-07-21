<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260721091547 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE tentative_validation ADD reponse_saisie VARCHAR(255) DEFAULT NULL, ADD longitude NUMERIC(10, 7) NOT NULL, DROP response_saisie, DROP longuitude, CHANGE latitude latitude NUMERIC(10, 7) NOT NULL');
        $this->addSql('CREATE INDEX idx_equipe_etape ON tentative_validation (equipe_id, etape_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX idx_equipe_etape ON tentative_validation');
        $this->addSql('ALTER TABLE tentative_validation ADD response_saisie VARCHAR(255) NOT NULL, ADD longuitude VARCHAR(255) NOT NULL, DROP reponse_saisie, DROP longitude, CHANGE latitude latitude VARCHAR(255) NOT NULL');
    }
}
