<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260720084410 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE progression (id INT AUTO_INCREMENT NOT NULL, date_validation DATETIME NOT NULL, latitude_releve NUMERIC(10, 7) NOT NULL, longitude_releve NUMERIC(10, 7) NOT NULL, points_obtenus INT NOT NULL, equipe_id INT NOT NULL, etape_id INT NOT NULL, INDEX IDX_D5B250736D861B89 (equipe_id), INDEX IDX_D5B250734A8CA2AD (etape_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE progression ADD CONSTRAINT FK_D5B250736D861B89 FOREIGN KEY (equipe_id) REFERENCES equipe (id)');
        $this->addSql('ALTER TABLE progression ADD CONSTRAINT FK_D5B250734A8CA2AD FOREIGN KEY (etape_id) REFERENCES etape (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE progression DROP FOREIGN KEY FK_D5B250736D861B89');
        $this->addSql('ALTER TABLE progression DROP FOREIGN KEY FK_D5B250734A8CA2AD');
        $this->addSql('DROP TABLE progression');
    }
}
