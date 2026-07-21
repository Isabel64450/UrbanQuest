<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260721085602 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE tentative_validation (id INT AUTO_INCREMENT NOT NULL, response_saisie VARCHAR(255) NOT NULL, reussie TINYINT NOT NULL, latitude VARCHAR(255) NOT NULL, longuitude VARCHAR(255) NOT NULL, distance_calculee INT NOT NULL, date_tentative DATETIME NOT NULL, equipe_id INT NOT NULL, etape_id INT NOT NULL, INDEX IDX_BFA47E046D861B89 (equipe_id), INDEX IDX_BFA47E044A8CA2AD (etape_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE tentative_validation ADD CONSTRAINT FK_BFA47E046D861B89 FOREIGN KEY (equipe_id) REFERENCES equipe (id)');
        $this->addSql('ALTER TABLE tentative_validation ADD CONSTRAINT FK_BFA47E044A8CA2AD FOREIGN KEY (etape_id) REFERENCES etape (id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_equipe_pseudo ON joueur (equipe_id, pseudo)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE tentative_validation DROP FOREIGN KEY FK_BFA47E046D861B89');
        $this->addSql('ALTER TABLE tentative_validation DROP FOREIGN KEY FK_BFA47E044A8CA2AD');
        $this->addSql('DROP TABLE tentative_validation');
        $this->addSql('DROP INDEX uniq_equipe_pseudo ON joueur');
    }
}
