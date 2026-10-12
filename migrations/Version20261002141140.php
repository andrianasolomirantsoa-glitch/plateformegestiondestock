<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261002141140 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE produit_images (id INT AUTO_INCREMENT NOT NULL, filename VARCHAR(255) NOT NULL, ordre INT NOT NULL, produit_id INT NOT NULL, INDEX IDX_174D7550F347EFB (produit_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('ALTER TABLE produit_images ADD CONSTRAINT FK_174D7550F347EFB FOREIGN KEY (produit_id) REFERENCES produits (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE produit_images DROP FOREIGN KEY FK_174D7550F347EFB');
        $this->addSql('DROP TABLE produit_images');
    }
}
