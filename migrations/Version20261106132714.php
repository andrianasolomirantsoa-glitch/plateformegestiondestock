<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261106132714 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE familles (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(100) NOT NULL, description LONGTEXT DEFAULT NULL, actif TINYINT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, categorie_id INT NOT NULL, INDEX IDX_9F94FD27BCF5E72D (categorie_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE familles ADD CONSTRAINT FK_9F94FD27BCF5E72D FOREIGN KEY (categorie_id) REFERENCES categories (id)');
        $this->addSql('ALTER TABLE produits ADD famille_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE produits ADD CONSTRAINT FK_BE2DDF8C97A77B84 FOREIGN KEY (famille_id) REFERENCES familles (id)');
        $this->addSql('CREATE INDEX IDX_BE2DDF8C97A77B84 ON produits (famille_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE familles DROP FOREIGN KEY FK_9F94FD27BCF5E72D');
        $this->addSql('DROP TABLE familles');
        $this->addSql('ALTER TABLE produits DROP FOREIGN KEY FK_BE2DDF8C97A77B84');
        $this->addSql('DROP INDEX IDX_BE2DDF8C97A77B84 ON produits');
        $this->addSql('ALTER TABLE produits DROP famille_id');
    }
}
