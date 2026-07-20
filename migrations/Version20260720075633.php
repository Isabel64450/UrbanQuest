<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260720075633 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE etape (id INT AUTO_INCREMENT NOT NULL, libelle VARCHAR(255) NOT NULL, consigne LONGTEXT NOT NULL, ordre INT NOT NULL, latitud NUMERIC(10, 7) NOT NULL, longitude NUMERIC(10, 7) NOT NULL, rayon_validation_metres INT NOT NULL, response_attendue LONGTEXT DEFAULT NULL, points INT NOT NULL, nombre_echecs_avant_indice INT NOT NULL, indice LONGTEXT NOT NULL, parcours_id INT NOT NULL, INDEX IDX_285F75DD6E38C0DB (parcours_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE etape ADD CONSTRAINT FK_285F75DD6E38C0DB FOREIGN KEY (parcours_id) REFERENCES parcours (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE etape DROP FOREIGN KEY FK_285F75DD6E38C0DB');
        $this->addSql('DROP TABLE etape');
    }
}
