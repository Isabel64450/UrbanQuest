<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260720115231 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE UNIQUE INDEX UNIQ_2449BA153CA1225C ON equipe (code_acces)');
        $this->addSql('ALTER TABLE etape CHANGE rayon_validation_metres rayon_validation_metres INT DEFAULT 20 NOT NULL, CHANGE indice indice LONGTEXT DEFAULT NULL, CHANGE latitud latitude NUMERIC(10, 7) NOT NULL, CHANGE response_attendue reponse_attendue LONGTEXT DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX uniq_parcours_ordre ON etape (parcours_id, ordre)');
        $this->addSql('ALTER TABLE parcours CHANGE status statut VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_2449BA153CA1225C ON equipe');
        $this->addSql('DROP INDEX uniq_parcours_ordre ON etape');
        $this->addSql('ALTER TABLE etape CHANGE rayon_validation_metres rayon_validation_metres INT NOT NULL, CHANGE indice indice LONGTEXT NOT NULL, CHANGE latitude latitud NUMERIC(10, 7) NOT NULL, CHANGE reponse_attendue response_attendue LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE parcours CHANGE statut status VARCHAR(255) NOT NULL');
    }
}
