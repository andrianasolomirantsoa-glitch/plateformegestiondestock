<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261109140629 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE stocks_magasins (id INT AUTO_INCREMENT NOT NULL, quantite INT DEFAULT 0 NOT NULL, seuil_alerte INT DEFAULT 0 NOT NULL, produit_id INT NOT NULL, magasin_id INT NOT NULL, INDEX IDX_5D6C95E9F347EFB (produit_id), INDEX IDX_5D6C95E920096AE3 (magasin_id), UNIQUE INDEX uniq_stock_produit_magasin (produit_id, magasin_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE stocks_magasins ADD CONSTRAINT FK_5D6C95E9F347EFB FOREIGN KEY (produit_id) REFERENCES produits (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE stocks_magasins ADD CONSTRAINT FK_5D6C95E920096AE3 FOREIGN KEY (magasin_id) REFERENCES magasins (id) ON DELETE RESTRICT');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE stocks_magasins DROP FOREIGN KEY FK_5D6C95E9F347EFB');
        $this->addSql('ALTER TABLE stocks_magasins DROP FOREIGN KEY FK_5D6C95E920096AE3');
        $this->addSql('DROP TABLE stocks_magasins');
    }
}
